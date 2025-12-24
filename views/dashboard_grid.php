<?php
//views/dashboard_grid.php
require_once '../auth/check_auth.php';
$current_user = getCurrentUser();

// Configuration des dashboards ThingsBoard
// ⚠️ IMPORTANT : Remplacez les URLs par vos vraies URLs de dashboards ThingsBoard publics
$dashboards = [
    'p2p_community' => [
        'id' => 'p2p_community',
        'title' => 'P2P Energy Community',
        'description' => 'Vue d\'ensemble des échanges P2P',
        'icon' => 'fa-solar-panel',
        'url' => 'https://demo.thingsboard.io/dashboard/45589d70-d9ec-11f0-869d-9726f60f35d2?publicId=170a5520-dce5-11f0-869d-9726f60f35d2', // À remplacer
        'color' => '#3498db',
        'category' => 'overview'
    ],
    'maison_productrice' => [
        'id' => 'maison_productrice',
        'title' => 'Maison Productrice',
        'description' => 'Production vs Consommation',
        'icon' => 'fa-home',
        'url' => 'https://demo.thingsboard.io/dashboard/716588e0-db33-11f0-869d-9726f60f35d2?publicId=170a5520-dce5-11f0-869d-9726f60f35d2', // À remplacer
        'color' => '#2ecc71',
        'category' => 'houses'
    ],
    'maison_consommatrice' => [
        'id' => 'maison_consommatrice',
        'title' => 'Maison Consommatrice',
        'description' => 'Consommation et couverture P2P',
        'icon' => 'fa-plug',
        'url' => 'https://demo.thingsboard.io/dashboard/8a9bbd10-db34-11f0-869d-9726f60f35d2?publicId=170a5520-dce5-11f0-869d-9726f60f35d2', // À remplacer
        'color' => '#e74c3c',
        'category' => 'houses'
    ],
    'batterie_centrale' => [
        'id' => 'batterie_centrale',
        'title' => 'Batterie Centrale',
        'description' => 'État de charge de la batterie',
        'icon' => 'fa-battery-three-quarters',
        'url' => 'https://demo.thingsboard.io/dashboard/a3c3a930-df42-11f0-869d-9726f60f35d2?publicId=170a5520-dce5-11f0-869d-9726f60f35d2', // À remplacer
        'color' => '#f39c12',
        'category' => 'battery'
    ]
];

// Mode d'affichage (grid ou focus)
$view_mode = isset($_GET['mode']) ? $_GET['mode'] : 'grid';
$focused_dashboard = isset($_GET['focus']) ? $_GET['focus'] : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Management - Vue Modulaire</title>
    
    <!-- Font Awesome -->
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

        /* ========== HEADER ========== */
        .header {
            background: linear-gradient(135deg, #16213e 0%, #1a1a2e 100%);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
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
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
            background: linear-gradient(135deg, #f39c12 0%, #e74c3c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Navigation */
        .nav-links {
            display: flex;
            gap: 10px;
            margin-left: 20px;
        }

        .nav-btn {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .nav-btn:hover, .nav-btn.active {
            background: #3498db;
            color: white;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .view-toggle {
            display: flex;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .view-btn {
            padding: 10px 20px;
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .view-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .view-btn.active {
            background: #3498db;
            color: white;
        }

        .refresh-btn {
            padding: 10px 20px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(52, 152, 219, 0.4);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
        }

        .user-info i {
            color: #3498db;
        }

        .logout-btn {
            padding: 10px 15px;
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logout-btn:hover {
            background: #e74c3c;
            color: white;
        }

        /* ========== CATEGORY TABS ========== */
        .category-tabs {
            display: flex;
            gap: 10px;
            padding: 20px 30px;
            background: rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            overflow-x: auto;
        }

        .category-tab {
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }

        .category-tab:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .category-tab.active {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-color: transparent;
            color: white;
        }

        .category-tab i {
            font-size: 1.1rem;
        }

        /* ========== DASHBOARD GRID ========== */
        .dashboard-container {
            padding: 30px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            max-width: 1800px;
            margin: 0 auto;
        }

        .dashboard-grid.single-column {
            grid-template-columns: 1fr;
        }

        /* ========== DASHBOARD CARD ========== */
        .dashboard-card {
            background: #16213e;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .dashboard-card.focused {
            grid-column: 1 / -1;
        }

        .card-header {
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(0, 0, 0, 0.2);
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .card-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }

        .card-title-text h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            margin-bottom: 3px;
        }

        .card-title-text p {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.5);
        }

        .card-actions {
            display: flex;
            gap: 8px;
        }

        .card-action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .card-action-btn.expand-btn:hover {
            background: #3498db;
        }

        .card-action-btn.refresh-card-btn:hover {
            background: #2ecc71;
        }

        /* ========== IFRAME CONTAINER ========== */
        .iframe-wrapper {
            position: relative;
            height: 450px;
            background: #0f0f1a;
        }

        .dashboard-card.focused .iframe-wrapper {
            height: 700px;
        }

        .iframe-wrapper iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #0f0f1a;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 10;
            transition: opacity 0.3s;
        }

        .loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top-color: #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.9rem;
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            text-align: center;
        }

        .empty-state i {
            font-size: 4rem;
            color: rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.9rem;
        }

        .empty-state code {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            color: #e74c3c;
            font-size: 0.85rem;
        }

        /* ========== STATUS BAR ========== */
        .status-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #16213e 0%, #1a1a2e 100%);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 100;
        }

        .status-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.85rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2ecc71;
            animation: blink 2s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .status-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .last-update {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
        }

        /* ========== FULLSCREEN MODE ========== */
        .fullscreen-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #1a1a2e;
            z-index: 1000;
            display: none;
            flex-direction: column;
        }

        .fullscreen-overlay.active {
            display: flex;
        }

        .fullscreen-header {
            padding: 15px 25px;
            background: #16213e;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .fullscreen-title {
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
        }

        .fullscreen-title i {
            font-size: 1.5rem;
        }

        .fullscreen-close {
            padding: 10px 20px;
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .fullscreen-close:hover {
            background: #e74c3c;
            color: white;
        }

        .fullscreen-content {
            flex: 1;
            padding: 20px;
        }

        .fullscreen-content iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 12px;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .header-left {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }

            .nav-links {
                margin-left: 0;
                justify-content: center;
            }

            .header-right {
                flex-wrap: wrap;
                justify-content: center;
            }

            .category-tabs {
                padding: 15px;
            }

            .dashboard-container {
                padding: 15px;
            }

            .status-bar {
                flex-direction: column;
                gap: 10px;
                padding: 10px 15px;
            }
        }

        /* ========== ADD BOTTOM PADDING FOR STATUS BAR ========== */
        .dashboard-container {
            padding-bottom: 80px;
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
                <a href="dashboard_grid.php" class="nav-btn active">
                    <i class="fas fa-th-large"></i>
                    Dashboards
                </a>
                <?php if ($current_user['role'] === 'admin'): ?>
                    <a href="billing.php" class="nav-btn">
                        <i class="fas fa-file-invoice-dollar"></i>
                        Facturation
                    </a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="header-right">
            <button class="refresh-btn" onclick="refreshAllDashboards()">
                <i class="fas fa-sync-alt"></i>
                Actualiser tout
            </button>
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($current_user['username']); ?></span>
                <?php if ($current_user['role'] === 'admin'): ?>
                    <span style="color: #f39c12; font-size: 0.8rem;">(Admin)</span>
                <?php endif; ?>
            </div>
            <a href="../auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </header>

    <!-- Category Tabs -->
    <div class="category-tabs">
        <button class="category-tab active" onclick="filterDashboards('all')">
            <i class="fas fa-th"></i>
            Tous les dashboards
        </button>
        <button class="category-tab" onclick="filterDashboards('overview')">
            <i class="fas fa-chart-pie"></i>
            Vue d'ensemble
        </button>
        <button class="category-tab" onclick="filterDashboards('houses')">
            <i class="fas fa-home"></i>
            Maisons
        </button>
        <button class="category-tab" onclick="filterDashboards('battery')">
            <i class="fas fa-battery-three-quarters"></i>
            Batterie
        </button>
         <div class="view-toggle">
                <button class="view-btn <?php echo $view_mode === 'grid' ? 'active' : ''; ?>" onclick="setViewMode('grid')">
                    <i class="fas fa-th-large"></i>
                    Grille
                </button>
                <button class="view-btn <?php echo $view_mode === 'list' ? 'active' : ''; ?>" onclick="setViewMode('list')">
                    <i class="fas fa-bars"></i>
                    Liste
                </button>
            </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-container">
        <div class="dashboard-grid <?php echo $view_mode === 'list' ? 'single-column' : ''; ?>" id="dashboardGrid">
            <?php foreach ($dashboards as $key => $dashboard): ?>
                <div class="dashboard-card" 
                     data-dashboard-id="<?php echo $key; ?>"
                     data-category="<?php echo $dashboard['category']; ?>"
                     id="card-<?php echo $key; ?>">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-icon" style="background: <?php echo $dashboard['color']; ?>;">
                                <i class="fas <?php echo $dashboard['icon']; ?>"></i>
                            </div>
                            <div class="card-title-text">
                                <h3><?php echo htmlspecialchars($dashboard['title']); ?></h3>
                                <p><?php echo htmlspecialchars($dashboard['description']); ?></p>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button class="card-action-btn refresh-card-btn" 
                                    onclick="refreshDashboard('<?php echo $key; ?>')"
                                    title="Actualiser">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button class="card-action-btn expand-btn" 
                                    onclick="openFullscreen('<?php echo $key; ?>')"
                                    title="Plein écran">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    <div class="iframe-wrapper">
                        <?php 
                        $isConfigured = !empty($dashboard['url']) && 
                                       strpos($dashboard['url'], 'VOTRE_URL') === false;
                        ?>
                        <?php if ($isConfigured): ?>
                            <div class="loading-overlay" id="loading-<?php echo $key; ?>">
                                <div class="spinner"></div>
                                <div class="loading-text">Chargement...</div>
                            </div>
                            <iframe 
                                id="iframe-<?php echo $key; ?>"
                                src="<?php echo htmlspecialchars($dashboard['url']); ?>"
                                onload="hideLoading('<?php echo $key; ?>')">
                            </iframe>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-link-slash"></i>
                                <h4>Dashboard non configuré</h4>
                                <p>Remplacez <code><?php echo $dashboard['url']; ?></code><br>par l'URL publique de votre dashboard ThingsBoard</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Status Bar -->
    <div class="status-bar">
        <div class="status-left">
            <div class="status-item">
                <span class="status-dot"></span>
                <span>Connexion ThingsBoard active</span>
            </div>
            <div class="status-item">
                <i class="fas fa-th-large"></i>
                <span><?php echo count($dashboards); ?> dashboards</span>
            </div>
        </div>
        <div class="status-right">
            <span class="last-update">
                <i class="fas fa-clock"></i>
                Dernière mise à jour : <span id="lastUpdate"><?php echo date('H:i:s'); ?></span>
            </span>
        </div>
    </div>

    <!-- Fullscreen Overlay -->
    <div class="fullscreen-overlay" id="fullscreenOverlay">
        <div class="fullscreen-header">
            <div class="fullscreen-title">
                <i class="fas fa-chart-line" id="fullscreenIcon"></i>
                <span id="fullscreenTitle">Dashboard</span>
            </div>
            <button class="fullscreen-close" onclick="closeFullscreen()">
                <i class="fas fa-times"></i>
                Fermer
            </button>
        </div>
        <div class="fullscreen-content">
            <iframe id="fullscreenIframe" src=""></iframe>
        </div>
    </div>

    <script>
        // Configuration des dashboards (pour JavaScript)
        const dashboardsConfig = <?php echo json_encode($dashboards); ?>;

        // Cacher le loading overlay
        function hideLoading(dashboardId) {
            const overlay = document.getElementById('loading-' + dashboardId);
            if (overlay) {
                overlay.classList.add('hidden');
            }
            updateLastUpdate();
        }

        // Actualiser un dashboard spécifique
        function refreshDashboard(dashboardId) {
            const iframe = document.getElementById('iframe-' + dashboardId);
            const overlay = document.getElementById('loading-' + dashboardId);
            
            if (iframe) {
                if (overlay) {
                    overlay.classList.remove('hidden');
                }
                iframe.src = iframe.src;
            }
        }

        // Actualiser tous les dashboards
        function refreshAllDashboards() {
            Object.keys(dashboardsConfig).forEach(key => {
                refreshDashboard(key);
            });
        }

        // Changer le mode d'affichage
        function setViewMode(mode) {
            const grid = document.getElementById('dashboardGrid');
            const buttons = document.querySelectorAll('.view-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.closest('.view-btn').classList.add('active');
            
            if (mode === 'list') {
                grid.classList.add('single-column');
            } else {
                grid.classList.remove('single-column');
            }
        }

        // Filtrer les dashboards par catégorie
        function filterDashboards(category) {
            const cards = document.querySelectorAll('.dashboard-card');
            const tabs = document.querySelectorAll('.category-tab');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.closest('.category-tab').classList.add('active');
            
            cards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Ouvrir en plein écran
        function openFullscreen(dashboardId) {
            const dashboard = dashboardsConfig[dashboardId];
            const overlay = document.getElementById('fullscreenOverlay');
            const iframe = document.getElementById('fullscreenIframe');
            const title = document.getElementById('fullscreenTitle');
            const icon = document.getElementById('fullscreenIcon');
            
            if (dashboard && dashboard.url && !dashboard.url.includes('VOTRE_URL')) {
                title.textContent = dashboard.title;
                icon.className = 'fas ' + dashboard.icon;
                iframe.src = dashboard.url;
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        // Fermer le plein écran
        function closeFullscreen() {
            const overlay = document.getElementById('fullscreenOverlay');
            const iframe = document.getElementById('fullscreenIframe');
            
            overlay.classList.remove('active');
            iframe.src = '';
            document.body.style.overflow = '';
        }

        // Mettre à jour l'heure
        function updateLastUpdate() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('lastUpdate').textContent = timeString;
        }

        // Raccourci clavier pour fermer le plein écran
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeFullscreen();
            }
        });

        // Mise à jour de l'heure chaque seconde
        setInterval(updateLastUpdate, 1000);
    </script>
</body>
</html>