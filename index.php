<?php
//index.php
session_start();

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Rediriger vers le dashboard
    header('Location: views/dashboard_grid.php');
    exit;
} else {
    // Rediriger vers la page de connexion
    header('Location: views/login.php');
    exit;
}
?>