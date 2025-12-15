<?php
/**
 * Configuration de l'API ThingsBoard
 */

// Charger les variables d'environnement
require_once __DIR__ . '/env_loader.php';

class ThingsBoardConfig {
    // URL de l'instance ThingsBoard
    private $base_url;

    // Identifiants d'authentification
    private $username;
    private $password;

    // Token d'authentification (sera généré dynamiquement)
    private $token = null;

    // Durée de validité du token en secondes
    private $token_expiry = 3600;

    public function __construct() {
        // Charger la configuration depuis les variables d'environnement
        $this->base_url = EnvLoader::get('TB_BASE_URL', 'https://demo.thingsboard.io');
        $this->username = EnvLoader::get('TB_USERNAME', 'tenant@thingsboard.org');
        $this->password = EnvLoader::get('TB_PASSWORD', 'tenant');
    }
    
    /**
     * Obtenir l'URL de base de ThingsBoard
     */
    public function getBaseUrl() {
        return $this->base_url;
    }
    
    /**
     * S'authentifier et obtenir le token JWT
     */
    public function authenticate() {
        $url = $this->base_url . '/api/auth/login';
        
        $data = json_encode([
            'username' => $this->username,
            'password' => $this->password
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            $this->token = $result['token'] ?? null;
            
            if ($this->token) {
                // Sauvegarder le token avec timestamp
                $_SESSION['tb_token'] = $this->token;
                $_SESSION['tb_token_time'] = time();
                return true;
            }
        }
        
        error_log("Échec d'authentification ThingsBoard: HTTP $http_code");
        return false;
    }
    
    /**
     * Obtenir le token d'authentification valide
     */
    public function getToken() {
        // Vérifier si le token existe en session et est encore valide
        if (isset($_SESSION['tb_token']) && isset($_SESSION['tb_token_time'])) {
            $token_age = time() - $_SESSION['tb_token_time'];
            
            if ($token_age < $this->token_expiry) {
                return $_SESSION['tb_token'];
            }
        }
        
        // Token expiré ou inexistant, réauthentification
        if ($this->authenticate()) {
            return $this->token;
        }
        
        return null;
    }
    
    /**
     * Effectuer une requête GET vers l'API ThingsBoard
     */
    public function get($endpoint) {
        $token = $this->getToken();
        if (!$token) {
            throw new Exception("Impossible d'obtenir le token d'authentification");
        }
        
        $url = $this->base_url . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        }
        
        throw new Exception("Erreur API ThingsBoard: HTTP $http_code");
    }
    
    /**
     * Récupérer les télémétries d'un device
     */
    public function getDeviceTelemetry($deviceId, $keys, $startTs = null, $endTs = null) {
        $endpoint = "/api/plugins/telemetry/DEVICE/{$deviceId}/values/timeseries";
        
        $params = ['keys' => implode(',', $keys)];
        
        if ($startTs) {
            $params['startTs'] = $startTs;
        }
        if ($endTs) {
            $params['endTs'] = $endTs;
        }
        
        $endpoint .= '?' . http_build_query($params);
        
        return $this->get($endpoint);
    }
}
?>