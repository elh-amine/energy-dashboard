<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Fallback pour les formulaires classiques
    $input = $_POST;
}

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

// Validation des champs
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Nom d\'utilisateur et mot de passe requis'
    ]);
    exit;
}

try {
    // Connexion à la base de données
    $database = new Database();
    $db = $database->connect();
    
    // Rechercher l'utilisateur
    $query = "SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier si l'utilisateur existe et si le mot de passe est correct
    if ($user && password_verify($password, $user['password'])) {
        // Régénérer l'ID de session pour sécurité
        session_regenerate_id(true);
        
        // Stocker les informations de l'utilisateur en session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Réponse de succès
        echo json_encode([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'username' => $user['username'],
                'role' => $user['role']
            ],
            'redirect' => '../views/dashboard.php'
        ]);
        
    } else {
        // Authentification échouée
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Nom d\'utilisateur ou mot de passe incorrect'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur : ' . $e->getMessage()
    ]);
}
?>