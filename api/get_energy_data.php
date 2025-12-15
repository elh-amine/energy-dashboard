<?php
/**
 * API pour récupérer les données énergétiques
 * Utilisé par le dashboard pour afficher les statistiques
 */

require_once '../auth/check_auth.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'overview';

try {
    $database = new Database();
    $db = $database->connect();
    
    switch ($action) {
        case 'overview':
            echo json_encode(getOverviewData($db));
            break;
            
        case 'houses':
            echo json_encode(getHousesData($db));
            break;
            
        case 'battery':
            echo json_encode(getBatteryData($db));
            break;
            
        case 'statistics':
            $period = $_GET['period'] ?? 'today';
            echo json_encode(getStatisticsData($db, $period));
            break;
            
        case 'house_details':
            $houseId = intval($_GET['house_id'] ?? 0);
            echo json_encode(getHouseDetails($db, $houseId));
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Action non reconnue'
            ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur',
        'error' => $e->getMessage()
    ]);
}

/**
 * Données pour la vue d'ensemble
 */
function getOverviewData($db) {
    // Énergie totale injectée et soutirée aujourd'hui
    $query = "SELECT 
                SUM(energy_injected) as total_injected,
                SUM(energy_taken) as total_taken
              FROM energy_exchange_data
              WHERE DATE(timestamp) = CURDATE()";
    
    $stmt = $db->query($query);
    $energyData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Nombre de maisons actives aujourd'hui
    $activeQuery = "SELECT COUNT(DISTINCT house_id) as active_houses
                    FROM energy_exchange_data
                    WHERE DATE(timestamp) = CURDATE()";
    
    $activeStmt = $db->query($activeQuery);
    $activeData = $activeStmt->fetch(PDO::FETCH_ASSOC);
    
    // Activité récente
    $activityQuery = "SELECT h.name, e.energy_injected, e.energy_taken, e.timestamp
                      FROM energy_exchange_data e
                      JOIN houses h ON e.house_id = h.id
                      ORDER BY e.timestamp DESC
                      LIMIT 10";
    
    $activityStmt = $db->query($activityQuery);
    $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les activités
    $recentActivity = [];
    foreach ($activities as $activity) {
        $message = "{$activity['name']} : ";
        if ($activity['energy_injected'] > 0) {
            $message .= "injecté {$activity['energy_injected']} kWh";
        }
        if ($activity['energy_taken'] > 0) {
            if ($activity['energy_injected'] > 0) $message .= ", ";
            $message .= "soutiré {$activity['energy_taken']} kWh";
        }
        
        $recentActivity[] = [
            'message' => $message,
            'timestamp' => $activity['timestamp']
        ];
    }
    
    return [
        'success' => true,
        'data' => [
            'total_injected' => floatval($energyData['total_injected'] ?? 0),
            'total_taken' => floatval($energyData['total_taken'] ?? 0),
            'active_houses' => intval($activeData['active_houses'] ?? 0),
            'recent_activity' => $recentActivity
        ]
    ];
}

/**
 * Données des maisons
 */
function getHousesData($db) {
    $query = "SELECT 
                h.id,
                h.name,
                h.type,
                h.thingsboard_device_id,
                COALESCE(SUM(e.energy_injected), 0) as energy_injected,
                COALESCE(SUM(e.energy_taken), 0) as energy_taken
              FROM houses h
              LEFT JOIN energy_exchange_data e ON h.id = e.house_id 
                  AND DATE(e.timestamp) = CURDATE()
              GROUP BY h.id, h.name, h.type, h.thingsboard_device_id
              ORDER BY h.name";
    
    $stmt = $db->query($query);
    $houses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir les valeurs en nombres
    foreach ($houses as &$house) {
        $house['energy_injected'] = floatval($house['energy_injected']);
        $house['energy_taken'] = floatval($house['energy_taken']);
    }
    
    return [
        'success' => true,
        'data' => $houses
    ];
}

/**
 * Données de la batterie centrale
 */
function getBatteryData($db) {
    // Données totales
    $query = "SELECT 
                SUM(energy_received) as total_received,
                SUM(energy_distributed) as total_distributed
              FROM central_battery_data";
    
    $stmt = $db->query($query);
    $batteryData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Historique des 7 derniers jours
    $historyQuery = "SELECT 
                        DATE(timestamp) as date,
                        SUM(energy_received) as received,
                        SUM(energy_distributed) as distributed
                     FROM central_battery_data
                     WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                     GROUP BY DATE(timestamp)
                     ORDER BY date";
    
    $historyStmt = $db->query($historyQuery);
    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'data' => [
            'total_received' => floatval($batteryData['total_received'] ?? 0),
            'total_distributed' => floatval($batteryData['total_distributed'] ?? 0),
            'history' => $history
        ]
    ];
}

/**
 * Statistiques selon la période
 */
function getStatisticsData($db, $period) {
    $dateCondition = match($period) {
        'today' => "DATE(timestamp) = CURDATE()",
        'week' => "timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
        'month' => "timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
        default => "DATE(timestamp) = CURDATE()"
    };
    
    // Tendance énergétique
    $trendQuery = "SELECT 
                      DATE(timestamp) as date,
                      SUM(energy_injected) as total_injected,
                      SUM(energy_taken) as total_taken
                   FROM energy_exchange_data
                   WHERE $dateCondition
                   GROUP BY DATE(timestamp)
                   ORDER BY date";
    
    $trendStmt = $db->query($trendQuery);
    $trendData = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $trendLabels = [];
    $trendValues = [];
    
    foreach ($trendData as $row) {
        $trendLabels[] = date('d/m', strtotime($row['date']));
        $trendValues[] = floatval($row['total_injected']) + floatval($row['total_taken']);
    }
    
    // Comparaison des maisons
    $comparisonQuery = "SELECT 
                          h.name,
                          SUM(e.energy_injected + e.energy_taken) as total_energy
                        FROM houses h
                        LEFT JOIN energy_exchange_data e ON h.id = e.house_id
                        WHERE $dateCondition
                        GROUP BY h.id, h.name
                        ORDER BY total_energy DESC";
    
    $comparisonStmt = $db->query($comparisonQuery);
    $comparisonData = $comparisonStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $comparisonLabels = [];
    $comparisonValues = [];
    
    foreach ($comparisonData as $row) {
        $comparisonLabels[] = $row['name'];
        $comparisonValues[] = floatval($row['total_energy']);
    }
    
    return [
        'success' => true,
        'data' => [
            'trend' => [
                'labels' => $trendLabels,
                'values' => $trendValues
            ],
            'comparison' => [
                'labels' => $comparisonLabels,
                'values' => $comparisonValues
            ]
        ]
    ];
}

/**
 * Détails d'une maison spécifique
 */
function getHouseDetails($db, $houseId) {
    if ($houseId === 0) {
        return [
            'success' => false,
            'message' => 'ID de maison invalide'
        ];
    }
    
    // Données du jour
    $todayQuery = "SELECT 
                     SUM(energy_injected) as today_injected,
                     SUM(energy_taken) as today_taken
                   FROM energy_exchange_data
                   WHERE house_id = :house_id AND DATE(timestamp) = CURDATE()";
    
    $todayStmt = $db->prepare($todayQuery);
    $todayStmt->execute([':house_id' => $houseId]);
    $todayData = $todayStmt->fetch(PDO::FETCH_ASSOC);
    
    // Historique des 7 derniers jours
    $historyQuery = "SELECT 
                       energy_injected,
                       energy_taken,
                       timestamp
                     FROM energy_exchange_data
                     WHERE house_id = :house_id
                     ORDER BY timestamp DESC
                     LIMIT 50";
    
    $historyStmt = $db->prepare($historyQuery);
    $historyStmt->execute([':house_id' => $houseId]);
    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Données pour le graphique (7 jours)
    $chartQuery = "SELECT 
                     DATE(timestamp) as date,
                     SUM(energy_injected) as injected,
                     SUM(energy_taken) as taken
                   FROM energy_exchange_data
                   WHERE house_id = :house_id 
                     AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                   GROUP BY DATE(timestamp)
                   ORDER BY date";
    
    $chartStmt = $db->prepare($chartQuery);
    $chartStmt->execute([':house_id' => $houseId]);
    $chartData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $chartLabels = [];
    $chartInjected = [];
    $chartTaken = [];
    
    foreach ($chartData as $row) {
        $chartLabels[] = date('d/m', strtotime($row['date']));
        $chartInjected[] = floatval($row['injected']);
        $chartTaken[] = floatval($row['taken']);
    }
    
    return [
        'success' => true,
        'data' => [
            'today_injected' => floatval($todayData['today_injected'] ?? 0),
            'today_taken' => floatval($todayData['today_taken'] ?? 0),
            'history' => $history,
            'chart_data' => [
                'labels' => $chartLabels,
                'injected' => $chartInjected,
                'taken' => $chartTaken
            ]
        ]
    ];
}
?>