<?php
//config/database.php
/**
 * Configuration de la connexion MySQL
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'energy_dashboard';
    private $username = 'root';  // Modifier selon votre configuration
    private $password = '';      // Modifier selon votre configuration
    private $charset = 'utf8mb4';
    private $conn = null;

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
function getDBConnection() {
    $database = new Database();
    return $database->connect();
}
?>