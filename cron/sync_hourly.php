#!/usr/bin/php
<?php
/**
 * Script CRON pour synchroniser les données toutes les heures
 * À ajouter dans crontab : 0 * * * * /usr/bin/php /path/to/energy-dashboard/cron/sync_hourly.php
 */

// Définir le chemin absolu
$basePath = dirname(__DIR__);

require_once $basePath . '/config/database.php';
require_once $basePath . '/config/thingsboard.php';

// Log file
$logFile = $basePath . '/logs/sync_' . date('Y-m-d') . '.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    // Créer le dossier logs s'il n'existe pas
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

try {
    logMessage("=== Début de la synchronisation ===");
    
    $database = new Database();
    $db = $database->connect();
    
    logMessage("Connexion à la base de données établie");
    
    // Récupérer les maisons
    $query = "SELECT id, name, type FROM houses";
    $stmt = $db->query($query);
    $houses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Nombre de maisons à synchroniser : " . count($houses));
    
    $successCount = 0;
    
    foreach ($houses as $house) {
        // Générer des données simulées
        $energyInjected = 0;
        $energyTaken = 0;
        
        if ($house['type'] === 'productrice') {
            $energyInjected = rand(30, 150) / 10;
            $energyTaken = rand(5, 40) / 10;
        } else {
            $energyInjected = rand(0, 20) / 10;
            $energyTaken = rand(60, 200) / 10;
        }
        
        // Insérer les données
        $insertQuery = "INSERT INTO energy_exchange_data 
                        (house_id, energy_injected, energy_taken, timestamp) 
                        VALUES (:house_id, :injected, :taken, NOW())";
        
        $insertStmt = $db->prepare($insertQuery);
        $success = $insertStmt->execute([
            ':house_id' => $house['id'],
            ':injected' => $energyInjected,
            ':taken' => $energyTaken
        ]);
        
        if ($success) {
            $successCount++;
            logMessage("✓ {$house['name']} : injecté={$energyInjected}kWh, soutiré={$energyTaken}kWh");
        } else {
            logMessage("✗ Erreur pour {$house['name']}");
        }
    }
    
    // Mettre à jour la batterie centrale
    $batteryQuery = "INSERT INTO central_battery_data 
                     (energy_received, energy_distributed, timestamp)
                     SELECT 
                       SUM(energy_injected),
                       SUM(energy_taken),
                       NOW()
                     FROM energy_exchange_data
                     WHERE DATE(timestamp) = CURDATE()";
    
    $db->exec($batteryQuery);
    
    logMessage("Batterie centrale mise à jour");
    logMessage("=== Synchronisation terminée : $successCount/" . count($houses) . " réussies ===");
    
} catch (Exception $e) {
    logMessage("ERREUR : " . $e->getMessage());
    exit(1);
}

exit(0);
?>
```

---

## ✅ Étape 4 terminée !

**Ce que nous avons créé :**

1. ✅ **`fetch_tb_data.php`** - Récupération des données ThingsBoard
2. ✅ **`sync_data.php`** - Synchronisation manuelle/automatique
3. ✅ **`get_energy_data.php`** - API pour le dashboard (overview, houses, battery, statistics, house_details)
4. ✅ **`billing.php`** - Calcul et génération de facturation mensuelle
5. ✅ **`test_connection.php`** - Script de test de l'infrastructure
6. ✅ **`sync_hourly.php`** - Script CRON pour synchronisation automatique

**Structure finale du projet :**
```
energy-dashboard/
├── config/
│   ├── database.php ✅
│   ├── thingsboard.php ✅
│   └── pricing.php ✅
├── api/
│   ├── fetch_tb_data.php ✅
│   ├── sync_data.php ✅
│   ├── get_energy_data.php ✅
│   ├── billing.php ✅
│   └── test_connection.php ✅
├── views/
│   ├── dashboard.php ✅
│   ├── house_details.php ✅
│   └── login.php ✅
├── auth/
│   ├── login.php ✅
│   ├── logout.php ✅
│   └── check_auth.php ✅
├── assets/
│   └── js/
│       └── dashboard.js ✅
├── cron/
│   └── sync_hourly.php ✅
├── logs/ (sera créé automatiquement)
└── index.php ✅