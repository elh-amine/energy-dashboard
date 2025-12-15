<?php
/**
 * API de facturation mensuelle
 * Calcule et génère les factures pour un mois donné
 */

require_once '../auth/check_auth.php';
require_once '../config/database.php';
require_once '../config/pricing.php';

header('Content-Type: application/json');

$month = $_GET['month'] ?? date('Y-m');

try {
    $database = new Database();
    $db = $database->connect();
    $pricing = new PricingConfig();
    
    // Vérifier si la facturation existe déjà pour ce mois
    $checkQuery = "SELECT COUNT(*) as count FROM monthly_billing WHERE month = :month";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([':month' => $month]);
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    
    if (!$exists) {
        // Générer la facturation pour ce mois
        generateMonthlyBilling($db, $pricing, $month);
    }
    
    // Récupérer les données de facturation
    $query = "SELECT 
                b.id,
                b.house_id,
                h.name as house_name,
                h.type,
                b.month,
                b.total_energy_injected,
                b.total_energy_taken,
                b.amount_to_pay,
                b.generated_at
              FROM monthly_billing b
              JOIN houses h ON b.house_id = h.id
              WHERE b.month = :month
              ORDER BY h.name";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':month' => $month]);
    $billingData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer les totaux
    $totalInjected = 0;
    $totalTaken = 0;
    $totalAmount = 0;
    
    foreach ($billingData as $row) {
        $totalInjected += floatval($row['total_energy_injected']);
        $totalTaken += floatval($row['total_energy_taken']);
        $totalAmount += floatval($row['amount_to_pay']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $billingData,
        'summary' => [
            'month' => $month,
            'total_injected' => round($totalInjected, 2),
            'total_taken' => round($totalTaken, 2),
            'total_amount' => round($totalAmount, 2),
            'house_count' => count($billingData)
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la génération de la facturation',
        'error' => $e->getMessage()
    ]);
}

/**
 * Générer la facturation mensuelle
 */
function generateMonthlyBilling($db, $pricing, $month) {
    // Récupérer toutes les maisons
    $housesQuery = "SELECT id FROM houses";
    $housesStmt = $db->query($housesQuery);
    $houses = $housesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($houses as $house) {
        $houseId = $house['id'];
        
        // Calculer le total d'énergie pour ce mois
        $energyQuery = "SELECT 
                          SUM(energy_injected) as total_injected,
                          SUM(energy_taken) as total_taken
                        FROM energy_exchange_data
                        WHERE house_id = :house_id
                          AND DATE_FORMAT(timestamp, '%Y-%m') = :month";
        
        $energyStmt = $db->prepare($energyQuery);
        $energyStmt->execute([
            ':house_id' => $houseId,
            ':month' => $month
        ]);
        
        $energyData = $energyStmt->fetch(PDO::FETCH_ASSOC);
        
        $totalInjected = floatval($energyData['total_injected'] ?? 0);
        $totalTaken = floatval($energyData['total_taken'] ?? 0);
        
        // Calculer le montant à payer
        $amountToPay = $pricing->calculateNetAmount($totalInjected, $totalTaken);
        
        // Insérer dans la table de facturation
        $insertQuery = "INSERT INTO monthly_billing 
                        (house_id, month, total_energy_injected, total_energy_taken, amount_to_pay, generated_at)
                        VALUES (:house_id, :month, :injected, :taken, :amount, NOW())
                        ON DUPLICATE KEY UPDATE
                        total_energy_injected = :injected,
                        total_energy_taken = :taken,
                        amount_to_pay = :amount,
                        generated_at = NOW()";
        
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([
            ':house_id' => $houseId,
            ':month' => $month,
            ':injected' => $totalInjected,
            ':taken' => $totalTaken,
            ':amount' => $amountToPay
        ]);
    }
    
    return true;
}
?>