<?php
/**
 * Configuration de l'API ThingsBoard avec JWT Token Direct
 */

class ThingsBoardConfig {
    // URL de l'instance ThingsBoard
    private $base_url = 'https://demo.thingsboard.io';
    
    // JWT Token permanent 
    private $jwt_token = 'VOTRE_JWT_TOKEN_ICI';
    
    // Pour compatibilité (optionnel)
    private $token = null;
    
    /**
     * Obtenir l'URL de base de ThingsBoard
     */
    public function getBaseUrl() {
        return $this->base_url;
    }
    
    /**
     * Obtenir le token JWT (directement, sans authentification)
     */
    public function getToken() {
        // Retourner directement le JWT token configuré
        return $this->jwt_token;
    }
    
    /**
     * Effectuer une requête GET vers l'API ThingsBoard
     */
    public function get($endpoint) {
        $token = $this->getToken();
        if (!$token) {
            throw new Exception("Token JWT non configuré");
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
