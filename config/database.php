<?php
/**
 * Configuration de la connexion MySQL
 */

// Charger les variables d'environnement
require_once __DIR__ . '/env_loader.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    private $conn = null;

    public function __construct() {
        // Charger la configuration depuis les variables d'environnement
        $this->host = EnvLoader::get('DB_HOST', 'localhost');
        $this->db_name = EnvLoader::get('DB_NAME', 'energy_dashboard');
        $this->username = EnvLoader::get('DB_USERNAME', 'root');
        $this->password = EnvLoader::get('DB_PASSWORD', '');
        $this->charset = EnvLoader::get('DB_CHARSET', 'utf8mb4');
    }

    /**
     * Établir la connexion à la base de données
     */
    public function connect() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
                ];
                
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                
            } catch(PDOException $e) {
                error_log("Erreur de connexion à la base de données : " . $e->getMessage());
                throw new Exception("Impossible de se connecter à la base de données");
            }
        }
        
        return $this->conn;
    }

    /**
     * Fermer la connexion
     */
    public function disconnect() {
        $this->conn = null;
    }

    /**
     * Vérifier si la connexion est active
     */
    public function isConnected() {
        return $this->conn !== null;
    }
}
?>