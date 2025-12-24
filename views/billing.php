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
       /* ============================================
   BILLING.PHP - CSS AMÉLIORÉ
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

/* Fond animé avec motif */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(ellipse at 10% 10%, rgba(46, 204, 113, 0.08) 0%, transparent 40%),
        radial-gradient(ellipse at 90% 90%, rgba(231, 76, 60, 0.08) 0%, transparent 40%),
        radial-gradient(ellipse at 50% 50%, rgba(52, 152, 219, 0.05) 0%, transparent 60%);
    pointer-events: none;
    z-index: -1;
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
    background: linear-gradient(180deg, var(--success-green) 0%, var(--primary-blue) 100%);
    border-radius: var(--radius-full);
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, var(--success-green-dark) 0%, var(--primary-blue-dark) 100%);
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
    background: linear-gradient(90deg, transparent, var(--success-green), var(--primary-blue), transparent);
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
    max-width: 1600px;
    margin: 0 auto;
}

/* Page Title */
.page-title {
    margin-bottom: 32px;
    position: relative;
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
    color: var(--success-green);
    filter: drop-shadow(0 0 8px rgba(46, 204, 113, 0.5));
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
}

.message.error {
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid rgba(231, 76, 60, 0.3);
    color: var(--danger-red);
}

.message.error::before {
    background: var(--danger-red);
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
}

.month-selector::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--purple), var(--primary-blue));
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
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    font-size: 1rem;
    transition: all var(--transition-normal);
    cursor: pointer;
}

.month-input:hover {
    border-color: var(--border-color-hover);
}

.month-input:focus {
    outline: none;
    border-color: var(--purple);
    box-shadow: 0 0 0 3px rgba(155, 89, 182, 0.2);
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

/* ========== SUMMARY CARDS ========== */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.summary-card {
    background: var(--bg-card);
    padding: 24px 20px;
    border-radius: var(--radius-lg);
    text-align: center;
    border: 1px solid var(--border-color);
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
}

.summary-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    opacity: 0;
    transition: opacity var(--transition-normal);
}

.summary-card:hover {
    transform: translateY(-6px);
    border-color: var(--border-color-hover);
    box-shadow: var(--shadow-md);
}

.summary-card:hover::before {
    opacity: 1;
}

.summary-card i {
    font-size: 2.2rem;
    margin-bottom: 16px;
    display: block;
}

.summary-card.produced i { 
    color: var(--warning-orange);
    filter: drop-shadow(0 0 8px rgba(243, 156, 18, 0.4));
}
.summary-card.produced::before { background: var(--warning-orange); }
.summary-card.produced:hover { box-shadow: var(--shadow-glow-orange); }

.summary-card.consumed i { 
    color: var(--danger-red);
    filter: drop-shadow(0 0 8px rgba(231, 76, 60, 0.4));
}
.summary-card.consumed::before { background: var(--danger-red); }
.summary-card.consumed:hover { box-shadow: var(--shadow-glow-red); }

.summary-card.injected i { 
    color: var(--success-green);
    filter: drop-shadow(0 0 8px rgba(46, 204, 113, 0.4));
}
.summary-card.injected::before { background: var(--success-green); }
.summary-card.injected:hover { box-shadow: var(--shadow-glow-green); }

.summary-card.taken i { 
    color: var(--primary-blue);
    filter: drop-shadow(0 0 8px rgba(52, 152, 219, 0.4));
}
.summary-card.taken::before { background: var(--primary-blue); }
.summary-card.taken:hover { box-shadow: var(--shadow-glow-blue); }

.summary-card.credit i { 
    color: var(--success-green);
    filter: drop-shadow(0 0 8px rgba(46, 204, 113, 0.4));
}
.summary-card.credit::before { background: var(--success-green); }

.summary-card.debit i { 
    color: var(--danger-red);
    filter: drop-shadow(0 0 8px rgba(231, 76, 60, 0.4));
}
.summary-card.debit::before { background: var(--danger-red); }

.summary-value {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 6px;
    font-variant-numeric: tabular-nums;
}

.summary-label {
    color: var(--text-muted);
    font-size: 0.85rem;
    font-weight: 500;
}

/* ========== BILLING SECTION ========== */
.billing-section {
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.section-header {
    padding: 20px 24px;
    background: rgba(0, 0, 0, 0.3);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-header h3 {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.15rem;
    font-weight: 600;
}

.section-header h3 i {
    color: var(--primary-blue);
}

.export-all-btn {
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--success-green) 0%, var(--success-green-dark) 100%);
    color: var(--text-primary);
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
}

.export-all-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.export-all-btn:hover::before {
    width: 300px;
    height: 300px;
}

.export-all-btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-glow-green);
}

.export-all-btn i,
.export-all-btn span {
    position: relative;
    z-index: 1;
}

/* ========== HOUSES BILLING GRID ========== */
.houses-billing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
    gap: 24px;
    padding: 24px;
}

/* ========== BILLING CARD ========== */
.billing-card {
    background: rgba(0, 0, 0, 0.2);
    border-radius: var(--radius-lg);
    overflow: hidden;
    border: 1px solid var(--border-color);
    transition: all var(--transition-normal);
    position: relative;
}

.billing-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    width: 4px;
    transition: width var(--transition-normal);
}

.billing-card:hover {
    transform: translateY(-6px) scale(1.01);
    border-color: var(--border-color-hover);
}

.billing-card.producer::before {
    background: linear-gradient(180deg, var(--success-green) 0%, var(--success-green-dark) 100%);
}

.billing-card.producer:hover {
    box-shadow: -4px 10px 40px rgba(46, 204, 113, 0.2);
}

.billing-card.consumer::before {
    background: linear-gradient(180deg, var(--danger-red) 0%, var(--danger-red-dark) 100%);
}

.billing-card.consumer:hover {
    box-shadow: -4px 10px 40px rgba(231, 76, 60, 0.2);
}

/* Billing Card Header */
.billing-card-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid var(--border-color);
}

.house-identity {
    display: flex;
    align-items: center;
    gap: 14px;
}

.house-icon {
    width: 52px;
    height: 52px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--text-primary);
    position: relative;
    overflow: hidden;
}

.house-icon::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 50%);
}

.billing-card.producer .house-icon {
    background: linear-gradient(135deg, var(--success-green) 0%, var(--success-green-dark) 100%);
    box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);
}

.billing-card.consumer .house-icon {
    background: linear-gradient(135deg, var(--danger-red) 0%, var(--danger-red-dark) 100%);
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
}

.house-name {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 4px;
}

.house-type-badge {
    font-size: 0.75rem;
    padding: 4px 12px;
    border-radius: var(--radius-full);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.billing-card.producer .house-type-badge {
    background: rgba(46, 204, 113, 0.15);
    color: var(--success-green);
    border: 1px solid rgba(46, 204, 113, 0.3);
}

.billing-card.consumer .house-type-badge {
    background: rgba(231, 76, 60, 0.15);
    color: var(--danger-red);
    border: 1px solid rgba(231, 76, 60, 0.3);
}

/* Invoice Status */
.invoice-status {
    padding: 8px 14px;
    border-radius: var(--radius-full);
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.invoice-status.generated {
    background: rgba(46, 204, 113, 0.15);
    color: var(--success-green);
    border: 1px solid rgba(46, 204, 113, 0.3);
    animation: statusGlow 2s ease-in-out infinite;
}

@keyframes statusGlow {
    0%, 100% { box-shadow: 0 0 0 rgba(46, 204, 113, 0); }
    50% { box-shadow: 0 0 15px rgba(46, 204, 113, 0.3); }
}

.invoice-status.pending {
    background: rgba(241, 196, 15, 0.15);
    color: #f1c40f;
    border: 1px solid rgba(241, 196, 15, 0.3);
}

.invoice-status.no-data {
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-muted);
    border: 1px solid var(--border-color);
}

/* Billing Card Body */
.billing-card-body {
    padding: 20px;
}

/* Data Grid */
.data-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 18px;
}

.data-item {
    background: rgba(255, 255, 255, 0.03);
    padding: 14px 16px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
    transition: all var(--transition-normal);
}

.data-item:hover {
    background: rgba(255, 255, 255, 0.06);
    border-color: var(--border-color-hover);
}

.data-label {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.data-label i {
    font-size: 0.9rem;
}

.data-value {
    font-size: 1.1rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.data-item.credit .data-value { 
    color: var(--success-green);
}

.data-item.debit .data-value { 
    color: var(--danger-red);
}

.data-value small {
    display: block;
    font-size: 0.8rem;
    opacity: 0.8;
    margin-top: 2px;
}

/* Billing Summary */
.billing-summary {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
    padding: 16px 20px;
    border-radius: var(--radius-md);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    border: 1px solid var(--border-color);
}

.net-label {
    color: var(--text-secondary);
    font-weight: 500;
}

.net-amount {
    font-size: 1.5rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.net-amount.positive { 
    color: var(--danger-red);
    text-shadow: 0 0 20px rgba(231, 76, 60, 0.5);
}

.net-amount.negative { 
    color: var(--success-green);
    text-shadow: 0 0 20px rgba(46, 204, 113, 0.5);
}

/* Billing Actions */
.billing-actions {
    display: flex;
    gap: 12px;
}

.action-btn {
    flex: 1;
    padding: 14px 18px;
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all var(--transition-normal);
    text-decoration: none;
    font-size: 0.9rem;
    position: relative;
    overflow: hidden;
}

.action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left var(--transition-slow);
}

.action-btn:hover::before {
    left: 100%;
}

.generate-btn {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
    color: var(--text-primary);
}

.generate-btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-glow-blue);
}

.pdf-btn {
    background: linear-gradient(135deg, var(--danger-red) 0%, var(--danger-red-dark) 100%);
    color: var(--text-primary);
}

.pdf-btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-glow-red);
}

.excel-btn {
    background: linear-gradient(135deg, var(--success-green) 0%, var(--success-green-dark) 100%);
    color: var(--text-primary);
}

.excel-btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-glow-green);
}

.action-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

/* No Data Message */
.no-data-message {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-muted);
}

.no-data-message i {
    font-size: 3rem;
    margin-bottom: 16px;
    display: block;
    color: var(--text-disabled);
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

.no-data-message p {
    margin-bottom: 12px;
}

.no-data-message a {
    color: var(--primary-blue);
    text-decoration: none;
    font-weight: 600;
    transition: all var(--transition-normal);
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.no-data-message a:hover {
    color: var(--cyan);
    text-shadow: 0 0 10px rgba(0, 217, 255, 0.5);
}

/* ========== RESPONSIVE ========== */
@media (max-width: 1400px) {
    .summary-cards {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .houses-billing-grid {
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
    }

    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .summary-card {
        padding: 18px 14px;
    }

    .summary-value {
        font-size: 1.3rem;
    }

    .houses-billing-grid {
        grid-template-columns: 1fr;
        padding: 16px;
    }

    .billing-actions {
        flex-direction: column;
    }

    .section-header {
        flex-direction: column;
        gap: 14px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .data-grid {
        grid-template-columns: 1fr;
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