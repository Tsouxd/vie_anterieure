<?php
// save_result.php
header('Content-Type: application/json');

// --- CONFIGURATION ---
$host = '127.0.0.1';
$dbname = 'vie_anterieure_bdd'; 
$user = 'root';                
$pass = '';                    
// ---------------------

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $input = json_decode(file_get_contents('php://input'), true);
    
    // Vérification que tout est présent
    if (isset($input['username']) && isset($input['email']) && isset($input['archetype'])) {
        
        $stmt = $pdo->prepare("INSERT INTO quiz_results (username, email, archetype) VALUES (:username, :email, :archetype)");
        
        $stmt->execute([
            'username' => htmlspecialchars($input['username']), // Sécurité XSS basique
            'email' => htmlspecialchars($input['email']),
            'archetype' => $input['archetype']
        ]);

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Données manquantes']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur BDD: ' . $e->getMessage()]);
}
?>