<?php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

$house_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($house_id === 0) {
    header('Location: dashboard.php');
    exit;
}

// Récupérer les informations de la maison
$database = new Database();
$db = $database->connect();

$query = "SELECT * FROM houses WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $house_id);
$stmt->execute();
$house = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$house) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($house['name']); ?> - Détails</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            text-decoration: none;
            color: #2c3e50;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            border-color: #3498db;
            color: #3498db;
        }

        .house-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .house-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .house-title h1 {
            font-size: 2rem;
            color: #2c3e50;
        }

        .house-type {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 600;
        }

        .house-type.productrice {
            background: #d4edda;
            color: #155724;
        }

        .house-type.consommatrice {
            background: #f8d7da;
            color: #721c24;
        }

        .house-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .info-value {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .stat-card h3 {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .chart-container h3 {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
            color: #2c3e50;
        }

        tr:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Retour au dashboard
        </a>

        <div class="house-header">
            <div class="house-title">
                <h1><?php echo htmlspecialchars($house['name']); ?></h1>
                <span class="house-type <?php echo $house['type']; ?>">
                    <?php echo ucfirst($house['type']); ?>
                </span>
            </div>
            
            <div class="house-info">
                <div class="info-item">
                    <span class="info-label">Device ID ThingsBoard</span>
                    <span class="info-value"><?php echo htmlspecialchars($house['thingsboard_device_id']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date d'ajout</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($house['created_at'])); ?></span>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Énergie Injectée (Aujourd'hui)</h3>
                <div class="info-value" id="todayInjected">-- kWh</div>
            </div>
            <div class="stat-card">
                <h3>Énergie Soutirée (Aujourd'hui)</h3>
                <div class="info-value" id="todayTaken">-- kWh</div>
            </div>
            <div class="stat-card">
                <h3>Balance (Aujourd'hui)</h3>
                <div class="info-value" id="todayBalance">-- kWh</div>
            </div>
        </div>

        <div class="chart-container">
            <h3>Historique énergétique (7 jours)</h3>
            <canvas id="houseHistoryChart"></canvas>
        </div>

        <div class="chart-container">
            <h3>Historique détaillé</h3>
            <table id="historyTable">
                <thead>
                    <tr>
                        <th>Date/Heure</th>
                        <th>Énergie Injectée (kWh)</th>
                        <th>Énergie Soutirée (kWh)</th>
                        <th>Balance (kWh)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px;">Chargement...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const houseId = <?php echo $house_id; ?>;
        let houseHistoryChart = null;

        // Charger les données de la maison
        async function loadHouseData() {
            try {
                const response = await fetch(`../api/get_energy_data.php?action=house_details&house_id=${houseId}`);
                const data = await response.json();
                
                if (data.success) {
                    updateHouseStats(data.data);
                    updateHistoryTable(data.data.history || []);
                    updateHistoryChart(data.data.chart_data || []);
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Mettre à jour les statistiques
        function updateHouseStats(data) {
            document.getElementById('todayInjected').textContent = (data.today_injected || 0).toFixed(2) + ' kWh';
            document.getElementById('todayTaken').textContent = (data.today_taken || 0).toFixed(2) + ' kWh';
            
            const balance = (data.today_injected || 0) - (data.today_taken || 0);
            document.getElementById('todayBalance').textContent = balance.toFixed(2) + ' kWh';
        }

        // Mettre à jour le tableau d'historique
        function updateHistoryTable(history) {
            const tbody = document.querySelector('#historyTable tbody');
            
            if (history.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 40px;">Aucune donnée disponible</td></tr>';
                return;
            }
            
            tbody.innerHTML = history.map(row => {
                const balance = (parseFloat(row.energy_injected) - parseFloat(row.energy_taken)).toFixed(2);
                return `
                    <tr>
                        <td>${new Date(row.timestamp).toLocaleString('fr-FR')}</td>
                        <td>${parseFloat(row.energy_injected).toFixed(2)}</td>
                        <td>${parseFloat(row.energy_taken).toFixed(2)}</td>
                        <td>${balance}</td>
                    </tr>
                `;
            }).join('');
        }

        // Mettre à jour le graphique
        function updateHistoryChart(chartData) {
            const ctx = document.getElementById('houseHistoryChart');
            
            if (houseHistoryChart) {
                houseHistoryChart.destroy();
            }
            
            houseHistoryChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels || [],
                    datasets: [
                        {
                            label: 'Énergie Injectée',
                            data: chartData.injected || [],
                            borderColor: '#38ef7d',
                            backgroundColor: 'rgba(56, 239, 125, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Énergie Soutirée',
                            data: chartData.taken || [],
                            borderColor: '#ff6a00',
                            backgroundColor: 'rgba(255, 106, 0, 0.1)',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Énergie (kWh)'
                            }
                        }
                    }
                }
            });
        }

        // Charger au démarrage
        document.addEventListener('DOMContentLoaded', loadHouseData);
        
        // Rafraîchir toutes les 60 secondes
        setInterval(loadHouseData, 60000);
    </script>
</body>
</html>