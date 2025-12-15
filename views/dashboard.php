<?php
require_once '../auth/check_auth.php';
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Energy Management</title>
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
            color: #333;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px 0;
            z-index: 1000;
            overflow-y: auto;
        }

        .logo {
            padding: 0 20px 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo i {
            font-size: 2rem;
            color: #3498db;
        }

        .logo h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .nav-menu {
            margin-top: 30px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-item.active {
            background: rgba(52, 152, 219, 0.3);
            color: white;
            border-left: 4px solid #3498db;
        }

        .nav-item i {
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 10px;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            padding: 20px;
        }

        /* Header */
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left h1 {
            font-size: 1.8rem;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .subtitle {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .header-right {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .last-update {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: #ecf0f1;
            border-radius: 20px;
        }

        .user-info i {
            color: #3498db;
            font-size: 1.2rem;
        }

        /* Content Sections */
        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .section-title {
            font-size: 1.6rem;
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 3px solid #3498db;
        }

        /* KPI Cards */
        .kpi-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .kpi-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .kpi-injected .kpi-icon {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }

        .kpi-taken .kpi-icon {
            background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
            color: white;
        }

        .kpi-balance .kpi-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .kpi-battery .kpi-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .kpi-content h3 {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .kpi-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .kpi-label {
            font-size: 0.85rem;
            color: #95a5a6;
        }

        /* Charts */
        .charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .chart-container h3 {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        /* Activity Panel */
        .activity-panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .activity-panel h3 {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .activity-feed {
            max-height: 300px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 15px;
            border-left: 3px solid #3498db;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .activity-item .time {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .activity-item .message {
            color: #2c3e50;
        }

        /* Houses Grid */
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-group label {
            font-weight: 500;
            color: #2c3e50;
        }

        .filter-group select {
            padding: 8px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .houses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .house-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            transition: all 0.3s;
        }

        .house-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .house-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .house-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .house-type {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .house-type.productrice {
            background: #d4edda;
            color: #155724;
        }

        .house-type.consommatrice {
            background: #f8d7da;
            color: #721c24;
        }

        .house-stats {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .stat-row:last-child {
            border-bottom: none;
        }

        .stat-row .label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .stat-row .value {
            font-weight: 600;
            color: #2c3e50;
        }

        /* Battery Section */
        .battery-overview {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 40px;
        }

        .battery-visual {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .battery-icon-large {
            width: 150px;
            height: 250px;
            border: 4px solid #2c3e50;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
            background: #ecf0f1;
        }

        .battery-icon-large::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 15px;
            background: #2c3e50;
            border-radius: 5px 5px 0 0;
        }

        .battery-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 0%;
            background: linear-gradient(180deg, #11998e 0%, #38ef7d 100%);
            transition: height 0.5s ease;
        }

        .battery-percentage {
            margin-top: 20px;
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .battery-stats {
            display: flex;
            flex-direction: column;
            gap: 20px;
            justify-content: center;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .battery-chart {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .battery-chart h3 {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        /* Statistics Section */
        .period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .period-btn {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .period-btn:hover {
            border-color: #3498db;
            color: #3498db;
        }

        .period-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        /* Billing Section */
        .billing-controls {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .billing-controls select {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 0.95rem;
        }

        .btn-primary {
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .billing-table-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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

        /* Loading Spinner */
        .loading {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }

            .main-content {
                margin-left: 220px;
            }

            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .header {
                flex-direction: column;
                gap: 15px;
            }

            .kpi-container {
                grid-template-columns: 1fr;
            }

            .battery-overview {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="logo">
            <i class="fas fa-bolt"></i>
            <h2>Energy Hub</h2>
        </div>
        <nav class="nav-menu">
            <a class="nav-item active" data-section="overview">
                <i class="fas fa-home"></i>
                <span>Vue d'ensemble</span>
            </a>
            <a class="nav-item" data-section="houses">
                <i class="fas fa-house-user"></i>
                <span>Maisons</span>
            </a>
            <a class="nav-item" data-section="battery">
                <i class="fas fa-battery-three-quarters"></i>
                <span>Batterie Centrale</span>
            </a>
            <a class="nav-item" data-section="statistics">
                <i class="fas fa-chart-line"></i>
                <span>Statistiques</span>
            </a>
            <a class="nav-item" data-section="billing">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Facturation</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <h1>Tableau de Bord Énergétique</h1>
                <p class="subtitle">Système de partage communautaire</p>
            </div>
            <div class="header-right">
                <div class="last-update">
                    <i class="fas fa-clock"></i>
                    <span>Dernière mise à jour : <span id="lastUpdate">Chargement...</span></span>
                </div>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span id="userName"><?php echo htmlspecialchars($current_user['username']); ?></span>
                </div>
            </div>
        </header>

        <!-- Vue d'ensemble Section -->
        <section id="overview" class="content-section active">
            <h2 class="section-title">Vue d'ensemble du système</h2>
            
            <!-- KPI Cards -->
            <div class="kpi-container">
                <div class="kpi-card kpi-injected">
                    <div class="kpi-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Énergie Injectée</h3>
                        <p class="kpi-value" id="totalInjected">0 kWh</p>
                        <span class="kpi-label">Aujourd'hui</span>
                    </div>
                </div>

                <div class="kpi-card kpi-taken">
                    <div class="kpi-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Énergie Soutirée</h3>
                        <p class="kpi-value" id="totalTaken">0 kWh</p>
                        <span class="kpi-label">Aujourd'hui</span>
                    </div>
                </div>

                <div class="kpi-card kpi-balance">
                    <div class="kpi-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Balance Énergétique</h3>
                        <p class="kpi-value" id="energyBalance">0 kWh</p>
                        <span class="kpi-label">Net</span>
                    </div>
                </div>

                <div class="kpi-card kpi-battery">
                    <div class="kpi-icon">
                        <i class="fas fa-battery-full"></i>
                    </div>
                    <div class="kpi-content">
                        <h3>Maisons Actives</h3>
                        <p class="kpi-value" id="activeHouses">0</p>
                        <span class="kpi-label">Sur 5 maisons</span>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="charts-row">
                <div class="chart-container">
                    <h3>Échanges énergétiques (24h)</h3>
                    <canvas id="energyFlowChart"></canvas>
                </div>
                <div class="chart-container">
                    <h3>Répartition par type</h3>
                    <canvas id="housesDistributionChart"></canvas>
                </div>
            </div>

            <!-- Real-time Activity -->
            <div class="activity-panel">
                <h3>Activité récente</h3>
                <div id="activityFeed" class="activity-feed">
                    <div class="loading">
                        <div class="spinner"></div>
                        <p>Chargement de l'activité...</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Houses Section -->
        <section id="houses" class="content-section">
            <h2 class="section-title">Gestion des maisons</h2>
            
            <div class="filter-bar">
                <div class="filter-group">
                    <label>Type :</label>
                    <select id="houseTypeFilter">
                        <option value="all">Toutes</option>
                        <option value="productrice">Productrices</option>
                        <option value="consommatrice">Consommatrices</option>
                    </select>
                </div>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="houseSearch" placeholder="Rechercher une maison...">
                </div>
            </div>

            <div id="housesGrid" class="houses-grid">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Chargement des maisons...</p>
                </div>
            </div>
        </section>

        <!-- Battery Section -->
        <section id="battery" class="content-section">
            <h2 class="section-title">Batterie Centrale</h2>
            
            <div class="battery-overview">
                <div class="battery-visual">
                    <div class="battery-icon-large">
                        <div id="batteryFillLevel" class="battery-fill"></div>
                    </div>
                    <p class="battery-percentage" id="batteryPercentage">--</p>
                </div>
                
                <div class="battery-stats">
                    <div class="stat-item">
                        <span class="stat-label">Énergie reçue (total)</span>
                        <span class="stat-value" id="batteryReceived">0 kWh</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Énergie distribuée (total)</span>
                        <span class="stat-value" id="batteryDistributed">0 kWh</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Efficacité du système</span>
                        <span class="stat-value" id="systemEfficiency">--</span>
                    </div>
                </div>
            </div>

            <div class="battery-chart">
                <h3>Historique de la batterie (7 jours)</h3>
                <canvas id="batteryHistoryChart"></canvas>
            </div>
        </section>

        <!-- Statistics Section -->
        <section id="statistics" class="content-section">
            <h2 class="section-title">Statistiques détaillées</h2>
            
            <div class="period-selector">
                <button class="period-btn active" data-period="today">Aujourd'hui</button>
                <button class="period-btn" data-period="week">7 jours</button>
                <button class="period-btn" data-period="month">30 jours</button>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Tendance mensuelle</h3>
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
                <div class="stat-card">
                    <h3>Comparaison des maisons</h3>
                    <canvas id="houseComparisonChart"></canvas>
                </div>
            </div>
        </section>

        <!-- Billing Section -->
        <section id="billing" class="content-section">
            <h2 class="section-title">Facturation mensuelle</h2>
            
            <div class="billing-controls">
                <select id="billingMonth">
                    <option value="">Sélectionner un mois</option>
                </select>
                <button id="generateBilling" class="btn-primary">
                    <i class="fas fa-calculator"></i>
                    Générer la facturation
                </button>
            </div>

            <div id="billingTable" class="billing-table-container">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Sélectionnez un mois pour afficher la facturation</p>
                </div>
            </div>
        </section>
    </div>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>