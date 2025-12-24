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
       /* ============================================
   DATA_ENTRY.PHP - CSS AMÉLIORÉ
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
    --cyan: #00d9ff;
    
    /* Couleurs de fond */
    --bg-dark: #0a0a14;
    --bg-primary: #12121e;
    --bg-secondary: #1a1a2e;
    --bg-card: #16213e;
    --bg-card-hover: #1e2a4a;
    --bg-input: rgba(255, 255, 255, 0.05);
    
    /* Couleurs de texte */
    --text-primary: #ffffff;
    --text-secondary: rgba(255, 255, 255, 0.7);
    --text-muted: rgba(255, 255, 255, 0.5);
    --text-disabled: rgba(255, 255, 255, 0.3);
    
    /* Bordures */
    --border-color: rgba(255, 255, 255, 0.08);
    --border-color-hover: rgba(255, 255, 255, 0.15);
    --border-color-focus: rgba(52, 152, 219, 0.5);
    
    /* Ombres */
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
    --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.3);
    --shadow-lg: 0 16px 48px rgba(0, 0, 0, 0.4);
    --shadow-glow-blue: 0 0 30px rgba(52, 152, 219, 0.3);
    --shadow-glow-green: 0 0 30px rgba(46, 204, 113, 0.3);
    --shadow-glow-red: 0 0 30px rgba(231, 76, 60, 0.3);
    --shadow-glow-orange: 0 0 30px rgba(243, 156, 18, 0.3);
    --shadow-glow-purple: 0 0 30px rgba(155, 89, 182, 0.3);
    
    /* Transitions */
    --transition-fast: 0.15s ease;
    --transition-normal: 0.3s ease;
    --transition-slow: 0.5s ease;
    --transition-bounce: 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    
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

/* Fond animé avec particules subtiles */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(ellipse at 15% 15%, rgba(52, 152, 219, 0.1) 0%, transparent 45%),
        radial-gradient(ellipse at 85% 85%, rgba(46, 204, 113, 0.08) 0%, transparent 45%),
        radial-gradient(ellipse at 50% 50%, rgba(155, 89, 182, 0.05) 0%, transparent 60%);
    pointer-events: none;
    z-index: -1;
    animation: backgroundPulse 10s ease-in-out infinite alternate;
}

@keyframes backgroundPulse {
    0% { opacity: 0.8; }
    100% { opacity: 1; }
}

/* ========== SCROLLBAR ========== */
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
}

.nav-link {
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

.nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left var(--transition-slow);
}

.nav-link:hover::before {
    left: 100%;
}

.nav-link:hover {
    background: rgba(52, 152, 219, 0.15);
    color: var(--text-primary);
    border-color: rgba(52, 152, 219, 0.3);
    transform: translateY(-2px);
}

.nav-link.active {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
    color: var(--text-primary);
    box-shadow: var(--shadow-glow-blue);
}

.header-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

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
    color: var(--warning-orange);
}

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
}

.logout-btn:hover {
    background: var(--danger-red);
    color: var(--text-primary);
    border-color: var(--danger-red);
    transform: translateY(-2px);
    box-shadow: var(--shadow-glow-red);
}

/* ========== MAIN CONTENT ========== */
.main-content {
    padding: 32px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Page Title */
.page-title {
    margin-bottom: 32px;
}

.page-title h2 {
    font-size: 1.8rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 14px;
    font-weight: 700;
}

.page-title h2 i {
    color: var(--primary-blue);
    filter: drop-shadow(0 0 8px rgba(52, 152, 219, 0.5));
    animation: iconFloat 3s ease-in-out infinite;
}

@keyframes iconFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

.page-title p {
    color: var(--text-muted);
    font-size: 1rem;
}

/* ========== MESSAGES ========== */
.message {
    padding: 16px 20px;
    border-radius: var(--radius-md);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 14px;
    animation: slideIn var(--transition-normal);
    position: relative;
    overflow: hidden;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
}

.message.success {
    background: rgba(46, 204, 113, 0.1);
    border: 1px solid rgba(46, 204, 113, 0.3);
    color: var(--success-green);
}

.message.success::before {
    background: var(--success-green);
    box-shadow: 0 0 10px var(--success-green);
}

.message.success i {
    animation: successPop 0.5s ease;
}

@keyframes successPop {
    0% { transform: scale(0); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

.message.error {
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid rgba(231, 76, 60, 0.3);
    color: var(--danger-red);
}

.message.error::before {
    background: var(--danger-red);
    box-shadow: 0 0 10px var(--danger-red);
}

.message i {
    font-size: 1.2rem;
}

/* ========== MONTH SELECTOR ========== */
.month-selector {
    background: var(--bg-card);
    padding: 24px;
    border-radius: var(--radius-lg);
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    border: 1px solid var(--border-color);
    position: relative;
    overflow: hidden;
    transition: all var(--transition-normal);
}

.month-selector::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--purple), var(--primary-blue), var(--success-green));
}

.month-selector:hover {
    border-color: var(--border-color-hover);
}

.month-selector label {
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-primary);
}

.month-selector label i {
    color: var(--warning-orange);
    font-size: 1.1rem;
}

.month-input {
    padding: 12px 20px;
    background: var(--bg-input);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    font-size: 1rem;
    transition: all var(--transition-normal);
    cursor: pointer;
}

.month-input:hover {
    border-color: var(--border-color-hover);
    background: rgba(255, 255, 255, 0.08);
}

.month-input:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.15);
    background: rgba(255, 255, 255, 0.1);
}

.load-btn {
    padding: 12px 28px;
    background: linear-gradient(135deg, var(--purple) 0%, var(--purple-dark) 100%);
    color: var(--text-primary);
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all var(--transition-normal);
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
    overflow: hidden;
}

.load-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left var(--transition-slow);
}

.load-btn:hover::before {
    left: 100%;
}

.load-btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-glow-purple);
}

.load-btn:active {
    transform: translateY(-1px);
}

/* ========== HOUSES GRID ========== */
.houses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

/* ========== HOUSE CARD ========== */
.house-card {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    overflow: hidden;
    border: 1px solid var(--border-color);
    transition: all var(--transition-normal);
    position: relative;
}

.house-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    transition: height var(--transition-normal);
}

.house-card:hover {
    transform: translateY(-8px);
    border-color: var(--border-color-hover);
}

.house-card.producer::before {
    background: linear-gradient(90deg, var(--success-green), var(--success-green-dark));
}

.house-card.producer:hover {
    box-shadow: 0 20px 50px rgba(46, 204, 113, 0.2);
}

.house-card.consumer::before {
    background: linear-gradient(90deg, var(--danger-red), var(--danger-red-dark));
}

.house-card.consumer:hover {
    box-shadow: 0 20px 50px rgba(231, 76, 60, 0.2);
}

/* Card Header */
.card-header {
    padding: 20px;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    gap: 16px;
    border-bottom: 1px solid var(--border-color);
}

.house-icon {
    width: 56px;
    height: 56px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--text-primary);
    position: relative;
    overflow: hidden;
    transition: transform var(--transition-normal);
}

.house-card:hover .house-icon {
    transform: scale(1.1) rotate(5deg);
}

.house-icon::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.25) 0%, transparent 50%);
}

.house-card.producer .house-icon {
    background: linear-gradient(135deg, var(--success-green) 0%, var(--success-green-dark) 100%);
    box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
}

.house-card.consumer .house-icon {
    background: linear-gradient(135deg, var(--danger-red) 0%, var(--danger-red-dark) 100%);
    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
}

.house-info h3 {
    font-size: 1.15rem;
    margin-bottom: 6px;
    font-weight: 600;
}

.house-type {
    font-size: 0.8rem;
    padding: 4px 14px;
    border-radius: var(--radius-full);
    display: inline-block;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.house-card.producer .house-type {
    background: rgba(46, 204, 113, 0.15);
    color: var(--success-green);
    border: 1px solid rgba(46, 204, 113, 0.3);
}

.house-card.consumer .house-type {
    background: rgba(231, 76, 60, 0.15);
    color: var(--danger-red);
    border: 1px solid rgba(231, 76, 60, 0.3);
}

/* Card Body */
.card-body {
    padding: 24px;
}

/* Input Groups */
.input-group {
    margin-bottom: 20px;
    position: relative;
}

.input-group:last-child {
    margin-bottom: 0;
}

.input-group label {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 0.9rem;
    color: var(--text-secondary);
    font-weight: 500;
    transition: color var(--transition-normal);
}

.input-group:focus-within label {
    color: var(--text-primary);
}

.input-group label i {
    font-size: 0.95rem;
    transition: transform var(--transition-normal);
}

.input-group:focus-within label i {
    transform: scale(1.2);
}

.input-wrapper {
    position: relative;
}

.input-wrapper input {
    width: 100%;
    padding: 14px 18px;
    padding-right: 60px;
    background: var(--bg-input);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    font-size: 1rem;
    font-weight: 500;
    transition: all var(--transition-normal);
    font-variant-numeric: tabular-nums;
}

.input-wrapper input::placeholder {
    color: var(--text-disabled);
}

.input-wrapper input:hover {
    border-color: var(--border-color-hover);
    background: rgba(255, 255, 255, 0.08);
}

.input-wrapper input:focus {
    outline: none;
    border-color: var(--primary-blue);
    background: rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.15);
}

/* Colorier la bordure selon le type d'input */
.input-group:nth-child(1) .input-wrapper input:focus {
    border-color: var(--warning-orange);
    box-shadow: 0 0 0 4px rgba(243, 156, 18, 0.15);
}

.input-group:nth-child(2) .input-wrapper input:focus {
    border-color: var(--danger-red);
    box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.15);
}

.input-group:nth-child(3) .input-wrapper input:focus {
    border-color: var(--success-green);
    box-shadow: 0 0 0 4px rgba(46, 204, 113, 0.15);
}

.input-group:nth-child(4) .input-wrapper input:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.15);
}

.input-unit {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 0.9rem;
    font-weight: 600;
    padding: 4px 10px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: var(--radius-sm);
    pointer-events: none;
    transition: all var(--transition-normal);
}

.input-wrapper input:focus + .input-unit {
    background: rgba(52, 152, 219, 0.2);
    color: var(--primary-blue);
}

/* Validation visuelle */
.input-wrapper input:valid:not(:placeholder-shown) {
    border-color: rgba(46, 204, 113, 0.5);
}

/* ========== SUBMIT SECTION ========== */
.submit-section {
    background: var(--bg-card);
    padding: 28px;
    border-radius: var(--radius-xl);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 24px;
    border: 1px solid var(--border-color);
    position: relative;
    overflow: hidden;
}

.submit-section::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--success-green), var(--primary-blue), var(--purple));
}

.submit-info {
    display: flex;
    align-items: center;
    gap: 14px;
    color: var(--text-secondary);
}

.submit-info i {
    color: var(--warning-orange);
    font-size: 1.3rem;
    animation: infoGlow 2s ease-in-out infinite;
}

@keyframes infoGlow {
    0%, 100% { filter: drop-shadow(0 0 5px rgba(243, 156, 18, 0.3)); }
    50% { filter: drop-shadow(0 0 15px rgba(243, 156, 18, 0.6)); }
}

.submit-info strong {
    color: var(--text-primary);
    background: linear-gradient(90deg, var(--primary-blue), var(--purple));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.submit-btn {
    padding: 16px 44px;
    background: linear-gradient(135deg, var(--success-green) 0%, var(--success-green-dark) 100%);
    color: var(--text-primary);
    border: none;
    border-radius: var(--radius-lg);
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all var(--transition-normal);
    display: flex;
    align-items: center;
    gap: 12px;
    position: relative;
    overflow: hidden;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.submit-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
    transition: left 0.6s ease;
}

.submit-btn:hover::before {
    left: 100%;
}

.submit-btn:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 15px 40px rgba(46, 204, 113, 0.4);
}

.submit-btn:active {
    transform: translateY(-2px) scale(1);
}

.submit-btn i {
    font-size: 1.2rem;
    transition: transform var(--transition-normal);
}

.submit-btn:hover i {
    transform: scale(1.2);
}

/* Animation de sauvegarde réussie */
@keyframes saveSuccess {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* ========== RESPONSIVE ========== */
@media (max-width: 1200px) {
    .houses-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 900px) {
    .houses-grid {
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
        width: 100%;
    }

    .nav-links {
        flex-wrap: wrap;
        justify-content: center;
    }

    .main-content {
        padding: 20px 16px;
    }

    .month-selector {
        flex-direction: column;
        align-items: stretch;
        gap: 16px;
    }

    .month-selector label {
        justify-content: center;
    }

    .houses-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .submit-section {
        flex-direction: column;
        text-align: center;
        padding: 24px 20px;
    }

    .submit-info {
        flex-direction: column;
        gap: 10px;
    }

    .submit-btn {
        width: 100%;
        justify-content: center;
        padding: 16px 30px;
    }
}

@media (max-width: 480px) {
    .card-header {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }

    .house-info {
        text-align: center;
    }

    .input-group label {
        font-size: 0.85rem;
    }

    .input-wrapper input {
        padding: 12px 14px;
        padding-right: 55px;
        font-size: 0.95rem;
    }
}

/* ========== FOCUS VISIBLE ========== */
*:focus-visible {
    outline: 2px solid var(--primary-blue);
    outline-offset: 2px;
}

button:focus-visible,
input:focus-visible {
    outline: none;
}

/* ========== SELECTION ========== */
::selection {
    background: var(--primary-blue);
    color: var(--text-primary);
}

/* ========== PRINT STYLES ========== */
@media print {
    body {
        background: white;
        color: black;
    }

    .header,
    .logout-btn,
    .submit-section {
        display: none;
    }

    .house-card {
        break-inside: avoid;
        border: 1px solid #ccc;
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