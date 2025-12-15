<?php
/**
 * Script pour récupérer les données depuis ThingsBoard
 * À exécuter périodiquement (via cron job ou manuellement)
 */

require_once '../config/database.php';
require_once '../config/thingsboard.php';

header('Content-Type: application/json');

// Démarrer la session pour le token ThingsBoard
session_start();

try {
    // Connexion à la base de données
    $database = new Database();
    $db = $database->connect();
    
    // Initialiser ThingsBoard
    $tb = new ThingsBoardConfig();
    
    // Récupérer toutes les maisons
    $query = "SELECT id, name, type, thingsboard_device_id FROM houses";
    $stmt = $db->query($query);
    $houses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [];
    $errors = [];
    
    foreach ($houses as $house) {
        try {
            // Définir les clés de télémétrie à récupérer
            $keys = ['energy_injected', 'energy_taken'];
            
            // Récupérer les données des dernières 24 heures
            $endTs = time() * 1000; // Timestamp actuel en millisecondes
            $startTs = $endTs - (24 * 60 * 60 * 1000); // 24 heures avant
            
            // Récupérer les données de ThingsBoard
            $telemetryData = $tb->getDeviceTelemetry(
                $house['thingsboard_device_id'],
                $keys,
                $startTs,
                $endTs
            );
            
            // Traiter les données reçues
            if ($telemetryData) {
                $energyInjected = 0;
                $energyTaken = 0;
                
                // Extraire les valeurs les plus récentes
                if (isset($telemetryData['energy_injected']) && !empty($telemetryData['energy_injected'])) {
                    $latestInjected = $telemetryData['energy_injected'][0];
                    $energyInjected = floatval($latestInjected['value']);
                }
                
                if (isset($telemetryData['energy_taken']) && !empty($telemetryData['energy_taken'])) {
                    $latestTaken = $telemetryData['energy_taken'][0];
                    $energyTaken = floatval($latestTaken['value']);
                }
                
                // Stocker les données dans la base de données
                $insertQuery = "INSERT INTO energy_exchange_data 
                                (house_id, energy_injected, energy_taken, timestamp) 
                                VALUES (:house_id, :energy_injected, :energy_taken, NOW())";
                
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([
                    ':house_id' => $house['id'],
                    ':energy_injected' => $energyInjected,
                    ':energy_taken' => $energyTaken
                ]);
                
                $results[] = [
                    'house_id' => $house['id'],
                    'house_name' => $house['name'],
                    'energy_injected' => $energyInjected,
                    'energy_taken' => $energyTaken,
                    'status' => 'success'
                ];
                
            } else {
                $errors[] = [
                    'house_id' => $house['id'],
                    'house_name' => $house['name'],
                    'error' => 'Aucune donnée reçue de ThingsBoard'
                ];
            }
            
        } catch (Exception $e) {
            $errors[] = [
                'house_id' => $house['id'],
                'house_name' => $house['name'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Mettre à jour les données de la batterie centrale
    updateCentralBatteryData($db);
    
    // Réponse
    echo json_encode([
        'success' => true,
        'message' => 'Données récupérées avec succès',
        'total_houses' => count($houses),
        'successful_updates' => count($results),
        'failed_updates' => count($errors),
        'results' => $results,
        'errors' => $errors,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération des données',
        'error' => $e->getMessage()
    ]);
}

/**
 * Mettre à jour les données de la batterie centrale
 */
function updateCentralBatteryData($db) {
    try {
        // Calculer le total d'énergie reçue et distribuée aujourd'hui
        $query = "SELECT 
                    SUM(energy_injected) as total_received,
                    SUM(energy_taken) as total_distributed
                  FROM energy_exchange_data
                  WHERE DATE(timestamp) = CURDATE()";
        
        $stmt = $db->query($query);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $totalReceived = floatval($data['total_received'] ?? 0);
        $totalDistributed = floatval($data['total_distributed'] ?? 0);
        
        // Insérer dans la table de la batterie centrale
        $insertQuery = "INSERT INTO central_battery_data 
                        (energy_received, energy_distributed, timestamp) 
                        VALUES (:energy_received, :energy_distributed, NOW())";
        
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([
            ':energy_received' => $totalReceived,
            ':energy_distributed' => $totalDistributed
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Erreur mise à jour batterie centrale: " . $e->getMessage());
        return false;
    }
}
?>