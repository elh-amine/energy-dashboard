<?php
//views/data_entry.php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

$current_user = getCurrentUser();

// Vérifier si l'utilisateur est admin
if ($current_user['role'] !== 'admin') {
    header('Location: dashboard_grid.php');
    exit;
}

$db = getDBConnection();
$message = '';
$messageType = '';

// Récupérer toutes les maisons
$stmt = $db->query("SELECT * FROM houses ORDER BY name");
$houses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $month = $_POST['month'] ?? '';
// Convertir YYYY-MM en YYYY-MM-01 pour MySQL DATE
        $month_date = $month . '-01';
        
        if (empty($month)) {
            throw new Exception("Veuillez sélectionner un mois.");
        }
        
        $db->beginTransaction();
        
        foreach ($houses as $house) {
            $house_id = $house['id'];
            $energy_produced = floatval($_POST["energy_produced_{$house_id}"] ?? 0);
            $energy_consumed = floatval($_POST["energy_consumed_{$house_id}"] ?? 0);
            $energy_injected = floatval($_POST["energy_injected_{$house_id}"] ?? 0);
            $energy_taken = floatval($_POST["energy_taken_{$house_id}"] ?? 0);
            
            // Insérer ou mettre à jour les données
            $stmt = $db->prepare("
                INSERT INTO energy_readings 
                (house_id, month, energy_produced, energy_consumed, energy_injected, energy_taken, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                energy_produced = VALUES(energy_produced),
                energy_consumed = VALUES(energy_consumed),
                energy_injected = VALUES(energy_injected),
                energy_taken = VALUES(energy_taken),
                recorded_by = VALUES(recorded_by),
                updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->execute([
                $house_id,
                $month_date,
                $energy_produced,
                $energy_consumed,
                $energy_injected,
                $energy_taken,
                $current_user['id']
            ]);
        }
        
        $db->commit();
        $message = "Données enregistrées avec succès pour le mois de " . $month;
        $messageType = 'success';
        
    } catch (Exception $e) {
        $db->rollBack();
        $message = "Erreur : " . $e->getMessage();
        $messageType = 'error';
    }
}

// Récupérer le mois sélectionné pour pré-remplir
$selectedMonth = $_GET['month'] ?? date('Y-m');

// Récupérer les données existantes pour ce mois
$existingData = [];
$stmt = $db->prepare("SELECT * FROM energy_readings WHERE month = ?");
$stmt->execute([$selectedMonth]);
$readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($readings as $reading) {
    $existingData[$reading['house_id']] = $reading;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saisie des Données - Energy Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            color: #eee;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #16213e 0%, #1a1a2e 100%);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo i {
            font-size: 2rem;
            color: #f39c12;
        }

        .logo h1 {
            font-size: 1.5rem;
            background: linear-gradient(135deg, #f39c12 0%, #e74c3c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 10px;
        }

        .nav-link {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link:hover, .nav-link.active {
            background: #3498db;
            color: white;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
        }

        .logout-btn {
            padding: 10px 15px;
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #e74c3c;
            color: white;
        }

        /* Main Content */
        .main-content {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-title {
            margin-bottom: 30px;
        }

        .page-title h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title h2 i {
            color: #3498db;
        }

        .page-title p {
            color: rgba(255, 255, 255, 0.6);
        }

        /* Message */
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .message.success {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
            color: #2ecc71;
        }

        .message.error {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }

        /* Month Selector */
        .month-selector {
            background: #16213e;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .month-selector label {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .month-selector label i {
            color: #f39c12;
        }

        .month-input {
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            cursor: pointer;
        }

        .month-input:focus {
            outline: none;
            border-color: #3498db;
        }

        .load-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .load-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(155, 89, 182, 0.4);
        }

        /* Houses Grid */
        .houses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .house-card {
            background: #16213e;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s;
        }

        .house-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .house-card.producer {
            border-top: 4px solid #2ecc71;
        }

        .house-card.consumer {
            border-top: 4px solid #e74c3c;
        }

        .card-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .house-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .house-card.producer .house-icon {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }

        .house-card.consumer .house-icon {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }

        .house-info h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .house-type {
            font-size: 0.85rem;
            padding: 3px 10px;
            border-radius: 20px;
            display: inline-block;
        }

        .house-card.producer .house-type {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }

        .house-card.consumer .house-type {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .card-body {
            padding: 20px;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .input-group label i {
            font-size: 0.85rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 15px;
            padding-right: 50px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #3498db;
            background: rgba(255, 255, 255, 0.1);
        }

        .input-unit {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.9rem;
        }

        /* Submit Section */
        .submit-section {
            background: #16213e;
            padding: 25px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .submit-info {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255, 255, 255, 0.6);
        }

        .submit-info i {
            color: #f39c12;
            font-size: 1.2rem;
        }

        .submit-btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(46, 204, 113, 0.4);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .month-selector {
                flex-direction: column;
                align-items: stretch;
            }

            .houses-grid {
                grid-template-columns: 1fr;
            }

            .submit-section {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <div class="logo">
                <i class="fas fa-bolt"></i>
                <h1>Energy Management</h1>
            </div>
            <nav class="nav-links">
                <a href="dashboard_grid.php" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    Dashboards
                </a>
                <a href="data_entry.php" class="nav-link active">
                    <i class="fas fa-edit"></i>
                    Saisie Données
                </a>
                <a href="billing.php" class="nav-link">
                    <i class="fas fa-file-invoice-dollar"></i>
                    Facturation
                </a>
            </nav>
        </div>
        <div class="header-right">
            <div class="user-info">
                <i class="fas fa-user-shield"></i>
                <span><?php echo htmlspecialchars($current_user['username']); ?></span>
                <span style="color: #f39c12; font-size: 0.8rem;">(Admin)</span>
            </div>
            <a href="../auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-title">
            <h2><i class="fas fa-edit"></i> Saisie des Données Énergétiques</h2>
            <p>Entrez les relevés mensuels pour chaque maison de la communauté</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="GET" class="month-selector">
            <label>
                <i class="fas fa-calendar-alt"></i>
                Sélectionner le mois :
            </label>
            <input type="month" name="month" class="month-input" value="<?php echo $selectedMonth; ?>">
            <button type="submit" class="load-btn">
                <i class="fas fa-search"></i>
                Charger les données
            </button>
        </form>

        <form method="POST" id="dataEntryForm">
            <input type="hidden" name="month" value="<?php echo $selectedMonth; ?>">
            
            <div class="houses-grid">
                <?php foreach ($houses as $house): 
                    $data = $existingData[$house['id']] ?? [];
                    $isProducer = $house['type'] === 'productrice';
                ?>
                    <div class="house-card <?php echo $isProducer ? 'producer' : 'consumer'; ?>">
                        <div class="card-header">
                            <div class="house-icon">
                                <i class="fas <?php echo $isProducer ? 'fa-solar-panel' : 'fa-plug'; ?>"></i>
                            </div>
                            <div class="house-info">
                                <h3><?php echo htmlspecialchars($house['name']); ?></h3>
                                <span class="house-type">
                                    <?php echo $isProducer ? 'Productrice' : 'Consommatrice'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="input-group">
                                <label>
                                    <i class="fas fa-sun" style="color: #f39c12;"></i>
                                    Énergie Produite
                                </label>
                                <div class="input-wrapper">
                                    <input type="number" 
                                           name="energy_produced_<?php echo $house['id']; ?>" 
                                           step="0.01" 
                                           min="0"
                                           value="<?php echo $data['energy_produced'] ?? 0; ?>"
                                           placeholder="0.00">
                                    <span class="input-unit">kWh</span>
                                </div>
                            </div>
                            <div class="input-group">
                                <label>
                                    <i class="fas fa-bolt" style="color: #e74c3c;"></i>
                                    Énergie Consommée
                                </label>
                                <div class="input-wrapper">
                                    <input type="number" 
                                           name="energy_consumed_<?php echo $house['id']; ?>" 
                                           step="0.01" 
                                           min="0"
                                           value="<?php echo $data['energy_consumed'] ?? 0; ?>"
                                           placeholder="0.00">
                                    <span class="input-unit">kWh</span>
                                </div>
                            </div>
                            <div class="input-group">
                                <label>
                                    <i class="fas fa-arrow-up" style="color: #2ecc71;"></i>
                                    Énergie Injectée (vers batterie)
                                </label>
                                <div class="input-wrapper">
                                    <input type="number" 
                                           name="energy_injected_<?php echo $house['id']; ?>" 
                                           step="0.01" 
                                           min="0"
                                           value="<?php echo $data['energy_injected'] ?? 0; ?>"
                                           placeholder="0.00">
                                    <span class="input-unit">kWh</span>
                                </div>
                            </div>
                            <div class="input-group">
                                <label>
                                    <i class="fas fa-arrow-down" style="color: #3498db;"></i>
                                    Énergie Soutirée (depuis batterie)
                                </label>
                                <div class="input-wrapper">
                                    <input type="number" 
                                           name="energy_taken_<?php echo $house['id']; ?>" 
                                           step="0.01" 
                                           min="0"
                                           value="<?php echo $data['energy_taken'] ?? 0; ?>"
                                           placeholder="0.00">
                                    <span class="input-unit">kWh</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="submit-section">
                <div class="submit-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Les données seront enregistrées pour le mois de <strong><?php echo $selectedMonth; ?></strong></span>
                </div>
                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i>
                    Enregistrer les données
                </button>
            </div>
        </form>
    </div>

    <script>
        // Validation du formulaire
        document.getElementById('dataEntryForm').addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input[type="number"]');
            let hasData = false;
            
            inputs.forEach(input => {
                if (parseFloat(input.value) > 0) {
                    hasData = true;
                }
            });
            
            if (!hasData) {
                if (!confirm('Aucune donnée saisie. Voulez-vous vraiment enregistrer des valeurs à zéro ?')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>