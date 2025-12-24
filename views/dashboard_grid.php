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
       /* ============================================
   DASHBOARD_GRID.PHP - CSS AMÉLIORÉ
   ============================================ */

/* ========== RESET & BASE ========== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    /* Couleurs principales */
    --primary-blue: #3498db;
    --primary-blue-dark: #2980b9;
    --success-green: #2ecc71;
    --success-green-dark: #27ae60;
    --danger-red: #e74c3c;
    --danger-red-dark: #c0392b;
    --warning-orange: #f39c12;
    --warning-orange-dark: #e67e22;
    --purple: #9b59b6;
    --purple-dark: #8e44ad;
    
    /* Couleurs de fond */
    --bg-dark: #0a0a14;
    --bg-primary: #12121e;
    --bg-secondary: #1a1a2e;
    --bg-card: #16213e;
    --bg-card-hover: #1e2a4a;
    
    /* Couleurs de texte */
    --text-primary: #ffffff;
    --text-secondary: rgba(255, 255, 255, 0.7);
    --text-muted: rgba(255, 255, 255, 0.5);
    --text-disabled: rgba(255, 255, 255, 0.3);
    
    /* Bordures */
    --border-color: rgba(255, 255, 255, 0.08);
    --border-color-hover: rgba(255, 255, 255, 0.15);
    
    /* Ombres */
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
    --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.3);
    --shadow-lg: 0 16px 48px rgba(0, 0, 0, 0.4);
    --shadow-glow-blue: 0 0 30px rgba(52, 152, 219, 0.3);
    --shadow-glow-green: 0 0 30px rgba(46, 204, 113, 0.3);
    --shadow-glow-orange: 0 0 30px rgba(243, 156, 18, 0.3);
    
    /* Transitions */
    --transition-fast: 0.15s ease;
    --transition-normal: 0.3s ease;
    --transition-slow: 0.5s ease;
    
    /* Border radius */
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 20px;
    --radius-full: 50px;
}

body {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', sans-serif;
    background: var(--bg-dark);
    color: var(--text-primary);
    min-height: 100vh;
    overflow-x: hidden;
}

/* Fond animé subtil */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(ellipse at 20% 20%, rgba(52, 152, 219, 0.08) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 80%, rgba(155, 89, 182, 0.08) 0%, transparent 50%),
        radial-gradient(ellipse at 50% 50%, rgba(243, 156, 18, 0.05) 0%, transparent 70%);
    pointer-events: none;
    z-index: -1;
}

/* ========== SCROLLBAR PERSONNALISÉE ========== */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: var(--bg-dark);
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, var(--primary-blue) 0%, var(--purple) 100%);
    border-radius: var(--radius-full);
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, var(--primary-blue-dark) 0%, var(--purple-dark) 100%);
}

/* ========== HEADER ========== */
.header {
    background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-secondary) 100%);
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--primary-blue), var(--purple), transparent);
    opacity: 0.5;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 24px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
}

.logo i {
    font-size: 2rem;
    color: var(--warning-orange);
    filter: drop-shadow(0 0 10px rgba(243, 156, 18, 0.5));
    animation: energyPulse 2s ease-in-out infinite;
}

@keyframes energyPulse {
    0%, 100% { 
        transform: scale(1);
        filter: drop-shadow(0 0 10px rgba(243, 156, 18, 0.5));
    }
    50% { 
        transform: scale(1.1);
        filter: drop-shadow(0 0 20px rgba(243, 156, 18, 0.8));
    }
}

.logo h1 {
    font-size: 1.4rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--warning-orange) 0%, var(--danger-red) 50%, var(--purple) 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: gradientShift 3s ease infinite;
}

@keyframes gradientShift {
    0%, 100% { background-position: 0% center; }
    50% { background-position: 100% center; }
}

/* Navigation */
.nav-links {
    display: flex;
    gap: 8px;
    margin-left: 16px;
}

.nav-btn {
    padding: 10px 18px;
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: var(--radius-md);
    transition: all var(--transition-normal);
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    border: 1px solid transparent;
    position: relative;
    overflow: hidden;
}

.nav-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left var(--transition-slow);
}

.nav-btn:hover::before {
    left: 100%;
}

.nav-btn:hover {
    background: rgba(52, 152, 219, 0.15);
    color: var(--text-primary);
    border-color: rgba(52, 152, 219, 0.3);
    transform: translateY(-2px);
}

.nav-btn.active {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
    color: var(--text-primary);
    box-shadow: var(--shadow-glow-blue);
}

.header-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

/* Bouton Actualiser */
.refresh-btn {
    padding: 10px 20px;
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--purple) 100%);
    color: var(--text-primary);
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
}

.refresh-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, var(--purple) 0%, var(--primary-blue) 100%);
    opacity: 0;
    transition: opacity var(--transition-normal);
}

.refresh-btn:hover::before {
    opacity: 1;
}

.refresh-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(52, 152, 219, 0.4);
}

.refresh-btn:active {
    transform: translateY(-1px);
}

.refresh-btn i,
.refresh-btn span {
    position: relative;
    z-index: 1;
}

.refresh-btn:hover i {
    animation: spinOnce 0.6s ease;
}

@keyframes spinOnce {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* User Info */
.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: var(--radius-full);
    border: 1px solid var(--border-color);
    transition: all var(--transition-normal);
}

.user-info:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: var(--border-color-hover);
}

.user-info i {
    color: var(--primary-blue);
    font-size: 1.1rem;
}

.user-info span {
    font-size: 0.9rem;
    font-weight: 500;
}

/* Logout Button */
.logout-btn {
    padding: 10px 14px;
    background: rgba(231, 76, 60, 0.1);
    color: var(--danger-red);
    border: 1px solid rgba(231, 76, 60, 0.2);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all var(--transition-normal);
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.logout-btn:hover {
    background: var(--danger-red);
    color: var(--text-primary);
    border-color: var(--danger-red);
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(231, 76, 60, 0.4);
}

/* ========== CATEGORY TABS ========== */
.category-tabs {
    display: flex;
    gap: 10px;
    padding: 16px 24px;
    background: rgba(255, 255, 255, 0.02);
    border-bottom: 1px solid var(--border-color);
    overflow-x: auto;
    scrollbar-width: none;
}

.category-tabs::-webkit-scrollbar {
    display: none;
}

.category-tab {
    padding: 12px 24px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all var(--transition-normal);
    display: flex;
    align-items: center;
    gap: 10px;
    white-space: nowrap;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.category-tab::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-blue), var(--purple));
    border-radius: var(--radius-full);
    transition: width var(--transition-normal);
}

.category-tab:hover {
    background: rgba(255, 255, 255, 0.06);
    color: var(--text-primary);
    border-color: var(--border-color-hover);
    transform: translateY(-2px);
}

.category-tab:hover::before {
    width: 50%;
}

.category-tab.active {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
    border-color: transparent;
    color: var(--text-primary);
    box-shadow: var(--shadow-glow-blue);
}

.category-tab.active::before {
    display: none;
}

.category-tab i {
    font-size: 1rem;
    transition: transform var(--transition-normal);
}

.category-tab:hover i {
    transform: scale(1.2);
}

/* View Toggle */
.view-toggle {
    display: flex;
    background: rgba(255, 255, 255, 0.05);
    border-radius: var(--radius-md);
    overflow: hidden;
    border: 1px solid var(--border-color);
    margin-left: auto;
}

.view-btn {
    padding: 10px 18px;
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    transition: all var(--transition-normal);
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 500;
}

.view-btn:hover {
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-primary);
}

.view-btn.active {
    background: var(--primary-blue);
    color: var(--text-primary);
}

/* ========== DASHBOARD GRID ========== */
.dashboard-container {
    padding: 24px;
    padding-bottom: 100px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
    max-width: 1800px;
    margin: 0 auto;
}

.dashboard-grid.single-column {
    grid-template-columns: 1fr;
    max-width: 1200px;
}

/* ========== DASHBOARD CARD ========== */
.dashboard-card {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-normal);
    border: 1px solid var(--border-color);
    position: relative;
}

.dashboard-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-blue), var(--purple), var(--warning-orange));
    opacity: 0;
    transition: opacity var(--transition-normal);
}

.dashboard-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
    border-color: var(--border-color-hover);
}

.dashboard-card:hover::before {
    opacity: 1;
}

.dashboard-card.focused {
    grid-column: 1 / -1;
}

/* Card Header */
.card-header {
    padding: 18px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
    background: rgba(0, 0, 0, 0.2);
}

.card-title {
    display: flex;
    align-items: center;
    gap: 14px;
}

.card-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: var(--text-primary);
    position: relative;
    overflow: hidden;
}

.card-icon::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: inherit;
    filter: blur(10px);
    opacity: 0.5;
    z-index: -1;
}

.card-title-text h3 {
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.card-title-text p {
    font-size: 0.8rem;
    color: var(--text-muted);
}

/* Card Actions */
.card-actions {
    display: flex;
    gap: 8px;
}

.card-action-btn {
    width: 38px;
    height: 38px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border-color);
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all var(--transition-normal);
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-action-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
    border-color: var(--border-color-hover);
    transform: scale(1.1);
}

.card-action-btn.expand-btn:hover {
    background: var(--primary-blue);
    border-color: var(--primary-blue);
    box-shadow: var(--shadow-glow-blue);
}

.card-action-btn.refresh-card-btn:hover {
    background: var(--success-green);
    border-color: var(--success-green);
    box-shadow: var(--shadow-glow-green);
}

.card-action-btn.refresh-card-btn:hover i {
    animation: spinOnce 0.6s ease;
}

/* ========== IFRAME CONTAINER ========== */
.iframe-wrapper {
    position: relative;
    height: 420px;
    background: linear-gradient(135deg, var(--bg-dark) 0%, var(--bg-primary) 100%);
}

.dashboard-card.focused .iframe-wrapper {
    height: 700px;
}

.iframe-wrapper iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* Loading Overlay */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, var(--bg-dark) 0%, var(--bg-primary) 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
    transition: opacity var(--transition-normal);
}

.loading-overlay.hidden {
    opacity: 0;
    pointer-events: none;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 3px solid var(--border-color);
    border-top-color: var(--primary-blue);
    border-right-color: var(--purple);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    color: var(--text-muted);
    font-size: 0.9rem;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; }
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
    color: var(--text-disabled);
    margin-bottom: 20px;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.empty-state h4 {
    color: var(--text-muted);
    margin-bottom: 10px;
    font-size: 1.1rem;
}

.empty-state p {
    color: var(--text-disabled);
    font-size: 0.9rem;
    line-height: 1.6;
}

.empty-state code {
    background: rgba(255, 255, 255, 0.1);
    padding: 4px 10px;
    border-radius: var(--radius-sm);
    color: var(--danger-red);
    font-size: 0.8rem;
}

/* ========== STATUS BAR ========== */
.status-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, var(--bg-card) 0%, var(--bg-secondary) 100%);
    padding: 14px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid var(--border-color);
    z-index: 100;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.status-bar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--success-green), var(--primary-blue), transparent);
    opacity: 0.5;
}

.status-left {
    display: flex;
    align-items: center;
    gap: 24px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-muted);
    font-size: 0.85rem;
}

.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--success-green);
    box-shadow: 0 0 10px var(--success-green);
    animation: statusPulse 2s ease-in-out infinite;
}

@keyframes statusPulse {
    0%, 100% { 
        opacity: 1;
        box-shadow: 0 0 10px var(--success-green);
    }
    50% { 
        opacity: 0.6;
        box-shadow: 0 0 20px var(--success-green);
    }
}

.status-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.last-update {
    color: var(--text-muted);
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.last-update i {
    color: var(--primary-blue);
}

/* ========== FULLSCREEN MODE ========== */
.fullscreen-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--bg-dark);
    z-index: 1000;
    display: none;
    flex-direction: column;
    animation: fadeIn var(--transition-normal);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.fullscreen-overlay.active {
    display: flex;
}

.fullscreen-header {
    padding: 16px 24px;
    background: var(--bg-card);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
}

.fullscreen-title {
    display: flex;
    align-items: center;
    gap: 14px;
    color: var(--text-primary);
    font-size: 1.2rem;
    font-weight: 600;
}

.fullscreen-title i {
    font-size: 1.4rem;
    color: var(--primary-blue);
}

.fullscreen-close {
    padding: 10px 20px;
    background: rgba(231, 76, 60, 0.1);
    color: var(--danger-red);
    border: 1px solid rgba(231, 76, 60, 0.2);
    border-radius: var(--radius-md);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    transition: all var(--transition-normal);
}

.fullscreen-close:hover {
    background: var(--danger-red);
    color: var(--text-primary);
    border-color: var(--danger-red);
}

.fullscreen-content {
    flex: 1;
    padding: 20px;
    background: var(--bg-primary);
}

.fullscreen-content iframe {
    width: 100%;
    height: 100%;
    border: none;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
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
        gap: 16px;
        padding: 16px;
    }

    .header-left {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
        width: 100%;
    }

    .nav-links {
        margin-left: 0;
        justify-content: center;
        flex-wrap: wrap;
    }

    .header-right {
        flex-wrap: wrap;
        justify-content: center;
        width: 100%;
    }

    .category-tabs {
        padding: 12px 16px;
    }

    .dashboard-container {
        padding: 16px;
        padding-bottom: 120px;
    }

    .status-bar {
        flex-direction: column;
        gap: 12px;
        padding: 12px 16px;
    }

    .iframe-wrapper {
        height: 350px;
    }
}

/* ========== SELECTION ========== */
::selection {
    background: var(--primary-blue);
    color: var(--text-primary);
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