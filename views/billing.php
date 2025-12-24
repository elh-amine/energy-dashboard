<?php
//views/billing.php
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

// Récupérer le mois sélectionné
$selectedMonth = $_GET['month'] ?? date('Y-m');

// Traitement de la génération de facture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invoice'])) {
    $house_id = intval($_POST['house_id']);
    $month = $_POST['month'];
    
    try {
        // Récupérer les données de la maison pour ce mois
        $stmt = $db->prepare("
            SELECT er.*, h.name, h.type 
            FROM energy_readings er
            JOIN houses h ON er.house_id = h.id
            WHERE er.house_id = ? AND er.month = ?
        ");
        $stmt->execute([$house_id, $month]);
        $reading = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reading) {
            throw new Exception("Aucune donnée trouvée pour cette maison ce mois.");
        }
        
        // Tarifs (modifiables)
        $price_injected = 0.10; // €/kWh pour énergie injectée (crédit)
        $price_taken = 0.15;    // €/kWh pour énergie soutirée (débit)
        
        $credit = $reading['energy_injected'] * $price_injected;
        $debit = $reading['energy_taken'] * $price_taken;
        $net = $debit - $credit;
        
        // Insérer ou mettre à jour la facture
        $stmt = $db->prepare("
            INSERT INTO invoices 
            (house_id, month, total_injected, total_taken, price_per_kwh_injected, price_per_kwh_taken, credit_amount, debit_amount, net_amount, status, generated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'generated', NOW())
            ON DUPLICATE KEY UPDATE
            total_injected = VALUES(total_injected),
            total_taken = VALUES(total_taken),
            credit_amount = VALUES(credit_amount),
            debit_amount = VALUES(debit_amount),
            net_amount = VALUES(net_amount),
            status = 'generated',
            generated_at = NOW()
        ");
        
        $stmt->execute([
            $house_id, $month,
            $reading['energy_injected'], $reading['energy_taken'],
            $price_injected, $price_taken,
            $credit, $debit, $net
        ]);
        
        $message = "Facture générée avec succès pour " . $reading['name'];
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
        $messageType = 'error';
    }
}

// Récupérer toutes les maisons avec leurs données
$stmt = $db->prepare("
    SELECT h.*, 
           er.energy_produced, er.energy_consumed, er.energy_injected, er.energy_taken,
           i.id as invoice_id, i.credit_amount, i.debit_amount, i.net_amount, i.status as invoice_status, i.generated_at
    FROM houses h
    LEFT JOIN energy_readings er ON h.id = er.house_id AND er.month = ?
    LEFT JOIN invoices i ON h.id = i.house_id AND i.month = ?
    ORDER BY h.name
");
$stmt->execute([$selectedMonth, $selectedMonth]);
$houses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les totaux
$totals = [
    'energy_produced' => 0,
    'energy_consumed' => 0,
    'energy_injected' => 0,
    'energy_taken' => 0,
    'credit' => 0,
    'debit' => 0
];

foreach ($houses as $house) {
    $totals['energy_produced'] += floatval($house['energy_produced'] ?? 0);
    $totals['energy_consumed'] += floatval($house['energy_consumed'] ?? 0);
    $totals['energy_injected'] += floatval($house['energy_injected'] ?? 0);
    $totals['energy_taken'] += floatval($house['energy_taken'] ?? 0);
    $totals['credit'] += floatval($house['credit_amount'] ?? 0);
    $totals['debit'] += floatval($house['debit_amount'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturation - Energy Management</title>
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
            max-width: 1600px;
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
            color: #2ecc71;
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

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: #16213e;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .summary-card i {
            font-size: 2rem;
            margin-bottom: 15px;
        }

        .summary-card.produced i { color: #f39c12; }
        .summary-card.consumed i { color: #e74c3c; }
        .summary-card.injected i { color: #2ecc71; }
        .summary-card.taken i { color: #3498db; }
        .summary-card.credit i { color: #2ecc71; }
        .summary-card.debit i { color: #e74c3c; }

        .summary-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .summary-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        /* Houses Table */
        .billing-section {
            background: #16213e;
            border-radius: 16px;
            overflow: hidden;
        }

        .section-header {
            padding: 20px 25px;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h3 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.2rem;
        }

        .section-header h3 i {
            color: #3498db;
        }

        .export-all-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .export-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(46, 204, 113, 0.4);
        }

        /* Houses Grid for Billing */
        .houses-billing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 25px;
            padding: 25px;
        }

        .billing-card {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s;
        }

        .billing-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .billing-card.producer {
            border-left: 4px solid #2ecc71;
        }

        .billing-card.consumer {
            border-left: 4px solid #e74c3c;
        }

        .billing-card-header {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0, 0, 0, 0.2);
        }

        .house-identity {
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
            font-size: 1.3rem;
            color: white;
        }

        .billing-card.producer .house-icon {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }

        .billing-card.consumer .house-icon {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }

        .house-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .house-type-badge {
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .billing-card.producer .house-type-badge {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }

        .billing-card.consumer .house-type-badge {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }

        .invoice-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .invoice-status.generated {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }

        .invoice-status.pending {
            background: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
        }

        .invoice-status.no-data {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
        }

        .billing-card-body {
            padding: 20px;
        }

        .data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .data-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 12px 15px;
            border-radius: 8px;
        }

        .data-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .data-value {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .data-item.credit .data-value { color: #2ecc71; }
        .data-item.debit .data-value { color: #e74c3c; }

        .billing-summary {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .net-label {
            color: rgba(255, 255, 255, 0.7);
        }

        .net-amount {
            font-size: 1.4rem;
            font-weight: 700;
        }

        .net-amount.positive { color: #e74c3c; }
        .net-amount.negative { color: #2ecc71; }

        .billing-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            flex: 1;
            padding: 12px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .generate-btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .generate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .pdf-btn {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .pdf-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .excel-btn {
            background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%);
            color: white;
        }

        .excel-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
        }

        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .no-data-message {
            text-align: center;
            padding: 30px;
            color: rgba(255, 255, 255, 0.5);
        }

        .no-data-message i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
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

            .summary-cards {
                grid-template-columns: repeat(2, 1fr);
            }

            .houses-billing-grid {
                grid-template-columns: 1fr;
            }

            .billing-actions {
                flex-direction: column;
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
                <a href="data_entry.php" class="nav-link">
                    <i class="fas fa-edit"></i>
                    Saisie Données
                </a>
                <a href="billing.php" class="nav-link active">
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
            <h2><i class="fas fa-file-invoice-dollar"></i> Facturation Mensuelle</h2>
            <p>Gérez et téléchargez les factures pour chaque maison de la communauté</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Month Selector -->
        <form method="GET" class="month-selector">
            <label>
                <i class="fas fa-calendar-alt"></i>
                Période de facturation :
            </label>
            <input type="month" name="month" class="month-input" value="<?php echo $selectedMonth; ?>">
            <button type="submit" class="load-btn">
                <i class="fas fa-search"></i>
                Afficher
            </button>
        </form>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card produced">
                <i class="fas fa-sun"></i>
                <div class="summary-value"><?php echo number_format($totals['energy_produced'], 2); ?> kWh</div>
                <div class="summary-label">Production Totale</div>
            </div>
            <div class="summary-card consumed">
                <i class="fas fa-bolt"></i>
                <div class="summary-value"><?php echo number_format($totals['energy_consumed'], 2); ?> kWh</div>
                <div class="summary-label">Consommation Totale</div>
            </div>
            <div class="summary-card injected">
                <i class="fas fa-arrow-up"></i>
                <div class="summary-value"><?php echo number_format($totals['energy_injected'], 2); ?> kWh</div>
                <div class="summary-label">Énergie Injectée</div>
            </div>
            <div class="summary-card taken">
                <i class="fas fa-arrow-down"></i>
                <div class="summary-value"><?php echo number_format($totals['energy_taken'], 2); ?> kWh</div>
                <div class="summary-label">Énergie Soutirée</div>
            </div>
            <div class="summary-card credit">
                <i class="fas fa-plus-circle"></i>
                <div class="summary-value"><?php echo number_format($totals['credit'], 2); ?> €</div>
                <div class="summary-label">Crédits Totaux</div>
            </div>
            <div class="summary-card debit">
                <i class="fas fa-minus-circle"></i>
                <div class="summary-value"><?php echo number_format($totals['debit'], 2); ?> €</div>
                <div class="summary-label">Débits Totaux</div>
            </div>
        </div>

        <!-- Billing Section -->
        <div class="billing-section">
            <div class="section-header">
                <h3>
                    <i class="fas fa-home"></i>
                    Factures par Maison - <?php echo date('F Y', strtotime($selectedMonth . '-01')); ?>
                </h3>
                <button class="export-all-btn" onclick="exportAllInvoices()">
                    <i class="fas fa-download"></i>
                    Exporter Tout (Excel)
                </button>
            </div>

            <div class="houses-billing-grid">
                <?php foreach ($houses as $house): 
                    $isProducer = $house['type'] === 'productrice';
                    $hasData = !empty($house['energy_produced']) || !empty($house['energy_consumed']) || 
                               !empty($house['energy_injected']) || !empty($house['energy_taken']);
                    $hasInvoice = !empty($house['invoice_id']);
                    $netAmount = floatval($house['net_amount'] ?? 0);
                ?>
                    <div class="billing-card <?php echo $isProducer ? 'producer' : 'consumer'; ?>">
                        <div class="billing-card-header">
                            <div class="house-identity">
                                <div class="house-icon">
                                    <i class="fas <?php echo $isProducer ? 'fa-solar-panel' : 'fa-plug'; ?>"></i>
                                </div>
                                <div>
                                    <div class="house-name"><?php echo htmlspecialchars($house['name']); ?></div>
                                    <span class="house-type-badge">
                                        <?php echo $isProducer ? 'Productrice' : 'Consommatrice'; ?>
                                    </span>
                                </div>
                            </div>
                            <?php if ($hasInvoice): ?>
                                <span class="invoice-status generated">
                                    <i class="fas fa-check"></i> Générée
                                </span>
                            <?php elseif ($hasData): ?>
                                <span class="invoice-status pending">
                                    <i class="fas fa-clock"></i> En attente
                                </span>
                            <?php else: ?>
                                <span class="invoice-status no-data">
                                    <i class="fas fa-minus"></i> Pas de données
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="billing-card-body">
                            <?php if ($hasData): ?>
                                <div class="data-grid">
                                    <div class="data-item">
                                        <div class="data-label">
                                            <i class="fas fa-sun" style="color: #f39c12;"></i>
                                            Production
                                        </div>
                                        <div class="data-value"><?php echo number_format($house['energy_produced'] ?? 0, 2); ?> kWh</div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">
                                            <i class="fas fa-bolt" style="color: #e74c3c;"></i>
                                            Consommation
                                        </div>
                                        <div class="data-value"><?php echo number_format($house['energy_consumed'] ?? 0, 2); ?> kWh</div>
                                    </div>
                                    <div class="data-item credit">
                                        <div class="data-label">
                                            <i class="fas fa-arrow-up" style="color: #2ecc71;"></i>
                                            Injecté (Crédit)
                                        </div>
                                        <div class="data-value">
                                            <?php echo number_format($house['energy_injected'] ?? 0, 2); ?> kWh
                                            <?php if ($hasInvoice): ?>
                                                <br><small>(<?php echo number_format($house['credit_amount'], 2); ?> €)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="data-item debit">
                                        <div class="data-label">
                                            <i class="fas fa-arrow-down" style="color: #e74c3c;"></i>
                                            Soutiré (Débit)
                                        </div>
                                        <div class="data-value">
                                            <?php echo number_format($house['energy_taken'] ?? 0, 2); ?> kWh
                                            <?php if ($hasInvoice): ?>
                                                <br><small>(<?php echo number_format($house['debit_amount'], 2); ?> €)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($hasInvoice): ?>
                                    <div class="billing-summary">
                                        <span class="net-label">Solde Net :</span>
                                        <span class="net-amount <?php echo $netAmount >= 0 ? 'positive' : 'negative'; ?>">
                                            <?php echo $netAmount >= 0 ? '+' : ''; ?><?php echo number_format($netAmount, 2); ?> €
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="billing-actions">
                                    <?php if (!$hasInvoice): ?>
                                        <form method="POST" style="flex: 1; display: flex;">
                                            <input type="hidden" name="house_id" value="<?php echo $house['id']; ?>">
                                            <input type="hidden" name="month" value="<?php echo $selectedMonth; ?>">
                                            <button type="submit" name="generate_invoice" class="action-btn generate-btn" style="width: 100%;">
                                                <i class="fas fa-calculator"></i>
                                                Générer Facture
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="../api/generate_invoice.php?house_id=<?php echo $house['id']; ?>&month=<?php echo $selectedMonth; ?>&format=pdf" 
                                           class="action-btn pdf-btn">
                                            <i class="fas fa-file-pdf"></i>
                                            PDF
                                        </a>
                                        <a href="../api/generate_invoice.php?house_id=<?php echo $house['id']; ?>&month=<?php echo $selectedMonth; ?>&format=excel" 
                                           class="action-btn excel-btn">
                                            <i class="fas fa-file-excel"></i>
                                            Excel
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-data-message">
                                    <i class="fas fa-database"></i>
                                    <p>Aucune donnée pour ce mois</p>
                                    <a href="data_entry.php?month=<?php echo $selectedMonth; ?>" style="color: #3498db;">
                                        Saisir les données →
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function exportAllInvoices() {
            const month = '<?php echo $selectedMonth; ?>';
            window.location.href = '../api/generate_invoice.php?month=' + month + '&format=excel&all=1';
        }
    </script>
</body>
</html>