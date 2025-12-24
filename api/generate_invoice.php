<?php
//api/generate_invoice.php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

$current_user = getCurrentUser();

// Vérifier si l'utilisateur est admin
if ($current_user['role'] !== 'admin') {
    http_response_code(403);
    die('Accès refusé');
}

$db = getDBConnection();

$format = $_GET['format'] ?? 'pdf';
$month = $_GET['month'] ?? date('Y-m');
$house_id = isset($_GET['house_id']) ? intval($_GET['house_id']) : null;
$exportAll = isset($_GET['all']) && $_GET['all'] == '1';

// Fonction pour obtenir les données d'une facture
function getInvoiceData($db, $house_id, $month) {
    $stmt = $db->prepare("
        SELECT h.id as house_id, h.name, h.type, 
               er.energy_produced, er.energy_consumed, er.energy_injected, er.energy_taken,
               i.id as invoice_id, i.total_injected, i.total_taken,
               i.price_per_kwh_injected, i.price_per_kwh_taken,
               i.credit_amount, i.debit_amount, i.net_amount, i.status, i.generated_at
        FROM houses h
        LEFT JOIN energy_readings er ON h.id = er.house_id AND er.month = ?
        LEFT JOIN invoices i ON h.id = i.house_id AND i.month = ?
        WHERE h.id = ?
    ");
    $stmt->execute([$month, $month, $house_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fonction pour obtenir toutes les factures d'un mois
function getAllInvoicesData($db, $month) {
    $stmt = $db->prepare("
        SELECT h.id as house_id, h.name, h.type, 
               er.energy_produced, er.energy_consumed, er.energy_injected, er.energy_taken,
               i.id as invoice_id, i.total_injected, i.total_taken, 
               i.price_per_kwh_injected, i.price_per_kwh_taken,
               i.credit_amount, i.debit_amount, i.net_amount, i.status
        FROM houses h
        LEFT JOIN energy_readings er ON h.id = er.house_id AND er.month = ?
        LEFT JOIN invoices i ON h.id = i.house_id AND i.month = ?
        ORDER BY h.name
    ");
    $stmt->execute([$month, $month]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Export Excel
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    
    if ($exportAll) {
        // Export de toutes les factures
        $invoices = getAllInvoicesData($db, $month);
        $filename = "factures_communaute_" . $month . ".xls";
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo "\xEF\xBB\xBF"; // BOM UTF-8 pour Excel
        echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel'>";
        echo "<head><meta charset='UTF-8'></head>";
        echo "<body>";
        echo "<table border='1'>";
        echo "<tr style='background-color: #3498db; color: white; font-weight: bold;'>";
        echo "<th>Maison</th>";
        echo "<th>Type</th>";
        echo "<th>Production (kWh)</th>";
        echo "<th>Consommation (kWh)</th>";
        echo "<th>Énergie Injectée (kWh)</th>";
        echo "<th>Énergie Soutirée (kWh)</th>";
        echo "<th>Crédit (€)</th>";
        echo "<th>Débit (€)</th>";
        echo "<th>Solde Net (€)</th>";
        echo "<th>Statut</th>";
        echo "</tr>";
        
        $totalCredit = 0;
        $totalDebit = 0;
        
        foreach ($invoices as $invoice) {
            $credit = floatval($invoice['credit_amount'] ?? 0);
            $debit = floatval($invoice['debit_amount'] ?? 0);
            $net = floatval($invoice['net_amount'] ?? 0);
            $totalCredit += $credit;
            $totalDebit += $debit;
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($invoice['name'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($invoice['type'] ?? '') . "</td>";
            echo "<td>" . number_format($invoice['energy_produced'] ?? 0, 2) . "</td>";
            echo "<td>" . number_format($invoice['energy_consumed'] ?? 0, 2) . "</td>";
            echo "<td>" . number_format($invoice['energy_injected'] ?? 0, 2) . "</td>";
            echo "<td>" . number_format($invoice['energy_taken'] ?? 0, 2) . "</td>";
            echo "<td>" . number_format($credit, 2) . "</td>";
            echo "<td>" . number_format($debit, 2) . "</td>";
            echo "<td style='color: " . ($net >= 0 ? 'red' : 'green') . ";'>" . number_format($net, 2) . "</td>";
            echo "<td>" . (!empty($invoice['invoice_id']) ? 'Générée' : 'En attente') . "</td>";
            echo "</tr>";
        }
        
        // Ligne de totaux
        echo "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
        echo "<td colspan='6'>TOTAUX</td>";
        echo "<td>" . number_format($totalCredit, 2) . "</td>";
        echo "<td>" . number_format($totalDebit, 2) . "</td>";
        echo "<td>" . number_format($totalDebit - $totalCredit, 2) . "</td>";
        echo "<td></td>";
        echo "</tr>";
        
        echo "</table>";
        echo "<br><p><strong>Période :</strong> " . date('F Y', strtotime($month . '-01')) . "</p>";
        echo "<p><strong>Généré le :</strong> " . date('d/m/Y H:i:s') . "</p>";
        echo "</body></html>";
        
    } else {
        // Export d'une seule facture
        if (!$house_id) {
            die('ID de maison requis');
        }
        
        $invoice = getInvoiceData($db, $house_id, $month);
        
        if (!$invoice) {
            die('Facture non trouvée');
        }
        
        $filename = "facture_" . preg_replace('/[^a-zA-Z0-9]/', '_', $invoice['name'] ?? 'unknown') . "_" . $month . ".xls";
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo "\xEF\xBB\xBF"; // BOM UTF-8 pour Excel
        echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel'>";
        echo "<head><meta charset='UTF-8'></head>";
        echo "<body>";
        
        // En-tête de la facture
        echo "<h2>FACTURE ÉNERGÉTIQUE</h2>";
        echo "<table border='0'>";
        echo "<tr><td><strong>Maison :</strong></td><td>" . htmlspecialchars($invoice['name'] ?? '') . "</td></tr>";
        echo "<tr><td><strong>Type :</strong></td><td>" . htmlspecialchars($invoice['type'] ?? '') . "</td></tr>";
        echo "<tr><td><strong>Période :</strong></td><td>" . date('F Y', strtotime($month . '-01')) . "</td></tr>";
        echo "<tr><td><strong>Date génération :</strong></td><td>" . date('d/m/Y H:i:s') . "</td></tr>";
        echo "</table>";
        
        echo "<br><br>";
        
        // Détail des consommations
        echo "<h3>Détail des Consommations</h3>";
        echo "<table border='1'>";
        echo "<tr style='background-color: #3498db; color: white;'><th>Description</th><th>Quantité</th><th>Unité</th></tr>";
        echo "<tr><td>Énergie Produite</td><td>" . number_format($invoice['energy_produced'] ?? 0, 2) . "</td><td>kWh</td></tr>";
        echo "<tr><td>Énergie Consommée</td><td>" . number_format($invoice['energy_consumed'] ?? 0, 2) . "</td><td>kWh</td></tr>";
        echo "<tr><td>Énergie Injectée (vers batterie)</td><td>" . number_format($invoice['energy_injected'] ?? 0, 2) . "</td><td>kWh</td></tr>";
        echo "<tr><td>Énergie Soutirée (depuis batterie)</td><td>" . number_format($invoice['energy_taken'] ?? 0, 2) . "</td><td>kWh</td></tr>";
        echo "</table>";
        
        echo "<br><br>";
        
        // Facturation
        echo "<h3>Facturation</h3>";
        echo "<table border='1'>";
        echo "<tr style='background-color: #2ecc71; color: white;'><th>Description</th><th>Quantité</th><th>Prix unitaire</th><th>Total</th></tr>";
        echo "<tr>";
        echo "<td>Crédit (Énergie injectée)</td>";
        echo "<td>" . number_format($invoice['total_injected'] ?? 0, 2) . " kWh</td>";
        echo "<td>" . number_format($invoice['price_per_kwh_injected'] ?? 0.10, 4) . " €/kWh</td>";
        echo "<td style='color: green;'>-" . number_format($invoice['credit_amount'] ?? 0, 2) . " €</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>Débit (Énergie soutirée)</td>";
        echo "<td>" . number_format($invoice['total_taken'] ?? 0, 2) . " kWh</td>";
        echo "<td>" . number_format($invoice['price_per_kwh_taken'] ?? 0.15, 4) . " €/kWh</td>";
        echo "<td style='color: red;'>+" . number_format($invoice['debit_amount'] ?? 0, 2) . " €</td>";
        echo "</tr>";
        echo "</table>";
        
        echo "<br>";
        
        // Solde
        $net = floatval($invoice['net_amount'] ?? 0);
        echo "<table border='1'>";
        echo "<tr style='background-color: #f0f0f0; font-weight: bold;'>";
        echo "<td>SOLDE NET À PAYER</td>";
        echo "<td style='color: " . ($net >= 0 ? 'red' : 'green') . "; font-size: 16px;'>";
        echo ($net >= 0 ? '' : '-') . number_format(abs($net), 2) . " €";
        if ($net < 0) {
            echo " (Crédit)";
        }
        echo "</td>";
        echo "</tr>";
        echo "</table>";
        
        echo "</body></html>";
    }
    exit;
}

// Export PDF (version HTML simplifiée pour impression)
if ($format === 'pdf') {
    if (!$house_id) {
        die('ID de maison requis');
    }
    
    $invoice = getInvoiceData($db, $house_id, $month);
    
    if (!$invoice) {
        die('Facture non trouvée');
    }
    
    $net = floatval($invoice['net_amount'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - <?php echo htmlspecialchars($invoice['name'] ?? 'N/A'); ?> - <?php echo $month; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .company-info h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .company-info p {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .invoice-title {
            text-align: right;
        }
        
        .invoice-title h2 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .invoice-number {
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .invoice-body {
            padding: 40px;
        }
        
        .client-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .client-info, .invoice-info {
            flex: 1;
        }
        
        .client-info h3, .invoice-info h3 {
            color: #7f8c8d;
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        
        .client-info p, .invoice-info p {
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .house-type-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .producer { background: #d5f4e6; color: #27ae60; }
        .consumer { background: #fadbd8; color: #e74c3c; }
        
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .details-table th {
            background: #3498db;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .details-table td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .details-table tr:last-child td {
            border-bottom: none;
        }
        
        .billing-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .billing-table th {
            background: #2ecc71;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .billing-table td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .credit { color: #27ae60; font-weight: 600; }
        .debit { color: #e74c3c; font-weight: 600; }
        
        .total-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .total-label {
            font-size: 1.2rem;
            color: #7f8c8d;
        }
        
        .total-amount {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .total-amount.positive { color: #e74c3c; }
        .total-amount.negative { color: #27ae60; }
        
        .invoice-footer {
            background: #f8f9fa;
            padding: 30px 40px;
            text-align: center;
            color: #7f8c8d;
            font-size: 0.85rem;
        }
        
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 15px 30px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 5px 20px rgba(52, 152, 219, 0.4);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .print-btn:hover {
            background: #2980b9;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
            }
            
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="company-info">
                <h1>⚡ Energy Community</h1>
                <p>Gestion Énergétique Communautaire</p>
            </div>
            <div class="invoice-title">
                <h2>FACTURE</h2>
                <span class="invoice-number">N° <?php echo $invoice['invoice_id'] ?? 'DRAFT'; ?>-<?php echo str_replace('-', '', $month); ?></span>
            </div>
        </div>
        
        <div class="invoice-body">
            <div class="client-section">
                <div class="client-info">
                    <h3>Client</h3>
                    <p><strong><?php echo htmlspecialchars($invoice['name'] ?? 'N/A'); ?></strong></p>
                    <span class="house-type-badge <?php echo ($invoice['type'] ?? '') === 'productrice' ? 'producer' : 'consumer'; ?>">
                        <?php echo ($invoice['type'] ?? '') === 'productrice' ? '🌞 Productrice' : '🔌 Consommatrice'; ?>
                    </span>
                </div>
                <div class="invoice-info">
                    <h3>Informations</h3>
                    <p><strong>Période :</strong> <?php echo date('F Y', strtotime($month . '-01')); ?></p>
                    <p><strong>Date d'émission :</strong> <?php echo date('d/m/Y'); ?></p>
                    <p><strong>Échéance :</strong> <?php echo date('d/m/Y', strtotime('+30 days')); ?></p>
                </div>
            </div>
            
            <h3 style="margin-bottom: 15px; color: #2c3e50;">📊 Détail des Consommations</h3>
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: right;">Quantité</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>☀️ Énergie Produite</td>
                        <td style="text-align: right;"><?php echo number_format($invoice['energy_produced'] ?? 0, 2); ?> kWh</td>
                    </tr>
                    <tr>
                        <td>⚡ Énergie Consommée</td>
                        <td style="text-align: right;"><?php echo number_format($invoice['energy_consumed'] ?? 0, 2); ?> kWh</td>
                    </tr>
                    <tr>
                        <td>⬆️ Énergie Injectée (vers batterie centrale)</td>
                        <td style="text-align: right;"><?php echo number_format($invoice['energy_injected'] ?? 0, 2); ?> kWh</td>
                    </tr>
                    <tr>
                        <td>⬇️ Énergie Soutirée (depuis batterie centrale)</td>
                        <td style="text-align: right;"><?php echo number_format($invoice['energy_taken'] ?? 0, 2); ?> kWh</td>
                    </tr>
                </tbody>
            </table>
            
            <h3 style="margin-bottom: 15px; color: #2c3e50;">💰 Facturation</h3>
            <table class="billing-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Quantité</th>
                        <th>Prix unitaire</th>
                        <th style="text-align: right;">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Crédit - Énergie injectée</td>
                        <td><?php echo number_format($invoice['total_injected'] ?? 0, 2); ?> kWh</td>
                        <td><?php echo number_format($invoice['price_per_kwh_injected'] ?? 0.10, 4); ?> €/kWh</td>
                        <td style="text-align: right;" class="credit">-<?php echo number_format($invoice['credit_amount'] ?? 0, 2); ?> €</td>
                    </tr>
                    <tr>
                        <td>Débit - Énergie soutirée</td>
                        <td><?php echo number_format($invoice['total_taken'] ?? 0, 2); ?> kWh</td>
                        <td><?php echo number_format($invoice['price_per_kwh_taken'] ?? 0.15, 4); ?> €/kWh</td>
                        <td style="text-align: right;" class="debit">+<?php echo number_format($invoice['debit_amount'] ?? 0, 2); ?> €</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="total-section">
                <span class="total-label">
                    <?php echo $net >= 0 ? 'MONTANT À PAYER' : 'CRÉDIT EN VOTRE FAVEUR'; ?>
                </span>
                <span class="total-amount <?php echo $net >= 0 ? 'positive' : 'negative'; ?>">
                    <?php echo number_format(abs($net), 2); ?> €
                </span>
            </div>
        </div>
        
        <div class="invoice-footer">
            <p>Facture générée automatiquement par Energy Community Management System</p>
            <p>Pour toute question, contactez l'administrateur de la communauté</p>
        </div>
    </div>
    
    <button class="print-btn" onclick="window.print()">
        🖨️ Imprimer / Sauvegarder PDF
    </button>
</body>
</html>
<?php
    exit;
}
?>