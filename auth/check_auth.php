<?php
/**
 * Middleware pour vérifier l'authentification
 * À inclure au début de chaque page protégée
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion
    header('Location: ../views/login.php');
    exit;
}

// Vérifier si la session n'a pas expiré (timeout de 2 heures)
$session_timeout = 7200; // 2 heures en secondes

if (isset($_SESSION['login_time'])) {
    $elapsed_time = time() - $_SESSION['login_time'];
    
    if ($elapsed_time > $session_timeout) {
        // Session expirée
        session_destroy();
        header('Location: ../views/login.php?timeout=1');
        exit;
    }
}

// Mettre à jour le dernier temps d'activité
$_SESSION['last_activity'] = time();

/**
 * Fonction pour vérifier le rôle admin
 */
function requireAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die('Accès refusé : privilèges administrateur requis');
    }
}

/**
 * Fonction pour obtenir les informations de l'utilisateur connecté
 */
function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}
?>