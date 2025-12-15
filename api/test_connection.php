<?php
/**
 * Script de test pour vérifier que tout fonctionne
 */

header('Content-Type: application/json');

$tests = [];

// Test 1: Connexion à la base de données
try {
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->connect();
    $tests['database'] = [
        'status' => 'success',
        'message' => 'Connexion à la base de données réussie'
    ];
} catch (Exception $e) {
    $tests['database'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Test 2: Vérification des tables
try {
    $tables = ['users', 'houses', 'energy_exchange_data', 'central_battery_data', 'monthly_billing'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->query($query);
        if ($stmt->rowCount() > 0) {
            $existingTables[] = $table;
        }
    }
    
    $tests['tables'] = [
        'status' => count($existingTables) === count($tables) ? 'success' : 'warning',
        'message' => count($existingTables) . ' / ' . count($tables) . ' tables trouvées',
        'existing_tables' => $existingTables
    ];
} catch (Exception $e) {
    $tests['tables'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Test 3: Comptage des maisons
try {
    $query = "SELECT COUNT(*) as count FROM houses";
    $stmt = $db->query($query);
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $tests['houses'] = [
        'status' => $count > 0 ? 'success' : 'warning',
        'message' => "$count maison(s) dans la base de données"
    ];
} catch (Exception $e) {
    $tests['houses'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Test 4: Configuration ThingsBoard
try {
    require_once '../config/thingsboard.php';
    session_start();
    $tb = new ThingsBoardConfig();
    
    $tests['thingsboard'] = [
        'status' => 'info',
        'message' => 'Configuration ThingsBoard chargée',
        'base_url' => $tb->getBaseUrl()
    ];
} catch (Exception $e) {
    $tests['thingsboard'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Test 5: Configuration de tarification
try {
    require_once '../config/pricing.php';
    $pricing = new PricingConfig();
    
    $tests['pricing'] = [
        'status' => 'success',
        'message' => 'Configuration de tarification chargée',
        'price_injected' => $pricing->getPriceInjected() . ' €/kWh',
        'price_taken' => $pricing->getPriceTaken() . ' €/kWh'
    ];
} catch (Exception $e) {
    $tests['pricing'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Résultat global
$allSuccess = true;
foreach ($tests as $test) {
    if ($test['status'] === 'error') {
        $allSuccess = false;
        break;
    }
}

echo json_encode([
    'overall_status' => $allSuccess ? 'success' : 'error',
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => $tests
], JSON_PRETTY_PRINT);
?>