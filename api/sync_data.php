<?php
/**
 * Script de synchronisation des données
 * Peut être appelé manuellement ou via un cron job
 */

require_once '../config/database.php';
require_once '../config/thingsboard.php';

header('Content-Type: application/json');
session_start();

// Vérifier si c'est un appel API ou un cron job
$isCronJob = php_sapi_name() === 'cli';

if (!$isCronJob) {
    // Si c'est un appel web, vérifier l'authentification
    require_once '../auth/check_auth.php';
    
    // Vérifier que c'est un admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Accès refusé : privilèges administrateur requis'
        ]);
        exit;
    }
}

try {
    $database = new Database();
    $db = $database->connect();
    $tb = new ThingsBoardConfig();
    
    // Récupérer toutes les maisons
    $query = "SELECT id, name, type, thingsboard_device_id FROM houses";
    $stmt = $db->query($query);
    $houses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $syncResults = [];
    $totalSynced = 0;
    $totalErrors = 0;
    
    foreach ($houses as $house) {
        try {
            // Générer des données simulées (à remplacer par les vraies données ThingsBoard)
            $energyInjected = 0;
            $energyTaken = 0;
            
            if ($house['type'] === 'productrice') {
                $energyInjected = rand(50, 200) / 10; // 5.0 à 20.0 kWh
                $energyTaken = rand(10, 50) / 10;     // 1.0 à 5.0 kWh
            } else {
                $energyInjected = rand(0, 30) / 10;   // 0.0 à 3.0 kWh
                $energyTaken = rand(80, 250) / 10;    // 8.0 à 25.0 kWh
            }
            
            // Insérer les données
            $insertQuery = "INSERT INTO energy_exchange_data 
                            (house_id, energy_injected, energy_taken, timestamp) 
                            VALUES (:house_id, :energy_injected, :energy_taken, NOW())";
            
            $insertStmt = $db->prepare($insertQuery);
            $success = $insertStmt->execute([
                ':house_id' => $house['id'],
                ':energy_injected' => $energyInjected,
                ':energy_taken' => $energyTaken
            ]);
            
            if ($success) {
                $totalSynced++;
                $syncResults[] = [
                    'house_name' => $house['name'],
                    'status' => 'success',
                    'energy_injected' => $energyInjected,
                    'energy_taken' => $energyTaken
                ];
            }
            
        } catch (Exception $e) {
            $totalErrors++;
            $syncResults[] = [
                'house_name' => $house['name'],
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Mettre à jour la batterie centrale
    updateCentralBattery($db);
    
    $response = [
        'success' => true,
        'message' => "Synchronisation terminée : $totalSynced réussies, $totalErrors erreurs",
        'total_houses' => count($houses),
        'synced' => $totalSynced,
        'errors' => $totalErrors,
        'details' => $syncResults,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la synchronisation',
        'error' => $e->getMessage()
    ]);
}

function updateCentralBattery($db) {
    $query = "SELECT 
                SUM(energy_injected) as total_received,
                SUM(energy_taken) as total_distributed
              FROM energy_exchange_data
              WHERE DATE(timestamp) = CURDATE()";
    
    $stmt = $db->query($query);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $insertQuery = "INSERT INTO central_battery_data 
                    (energy_received, energy_distributed, timestamp) 
                    VALUES (:received, :distributed, NOW())";
    
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->execute([
        ':received' => $data['total_received'] ?? 0,
        ':distributed' => $data['total_distributed'] ?? 0
    ]);
}
?>