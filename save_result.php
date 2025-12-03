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
    
    // On vérifie que les champs principaux sont là
    if (isset($input['username']) && isset($input['email']) && isset($input['archetype'])) {
        
        // Préparation des données supplémentaires
        // On transforme les tableaux (arrays) en texte JSON pour la base de données
        // Si les données ne sont pas fournies, on met un tableau vide par défaut
        $traitsJson = isset($input['traits']) ? json_encode($input['traits'], JSON_UNESCAPED_UNICODE) : '[]';
        $axesJson = isset($input['work_axes']) ? json_encode($input['work_axes'], JSON_UNESCAPED_UNICODE) : '[]';

        $stmt = $pdo->prepare("INSERT INTO quiz_results (username, email, archetype, traits, work_axes) VALUES (:username, :email, :archetype, :traits, :work_axes)");
        
        $stmt->execute([
            'username' => htmlspecialchars($input['username']),
            'email' => htmlspecialchars($input['email']),
            'archetype' => $input['archetype'],
            'traits' => $traitsJson,
            'work_axes' => $axesJson
        ]);

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Données manquantes']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur BDD: ' . $e->getMessage()]);
}
?>