<?php

// Désactiver l'affichage des erreurs PHP dans le navigateur (pour ne pas casser le JSON)
error_reporting(0);
ini_set('display_errors', 0);

// Headers pour le navigateur (CORS et JSON)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Fonction pour arrêter le script et renvoyer la réponse
function json_exit($status, $message, $data = []) {
    echo json_encode(array_merge([
        "status" => $status,
        "message" => $message
    ], $data));
    exit();
}

// Vérifier que c'est bien une requête POST venant du site
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_exit("error", "Méthode non autorisée");
}

$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);

if (!$input) {
    json_exit("error", "JSON invalide ou vide");
}

// Validation basique
if (empty($input["email"]) || empty($input["username"]) || empty($input["archetype"])) {
    json_exit("error", "Champs manquants");
}

$email = trim($input["email"]);
$firstName = trim($input["username"]);
// Forcer les valeurs en string (texte) pour éviter les erreurs de type
$archetype = strval($input["archetype"]);

$traits = isset($input["traits"]) && is_array($input["traits"]) 
    ? implode(", ", $input["traits"]) 
    : "";

$axes = isset($input["work_axes"]) && is_array($input["work_axes"]) 
    ? implode(", ", $input["work_axes"]) 
    : "";

// ⚠️ METTEZ VOTRE NOUVELLE CLÉ API CI-DESSOUS ⚠️
$apiKey = "pmkavvcyicbdpzfof429m2wsdzsh0nnbdbvbz1jdg8kq817srnp1gr2r88efi8xv"; 

function callSystemeIO($url, $method, $data, $key) {
    $ch = curl_init($url);
    
    // Sinon (POST/GET), c'est "application/json"
    $contentType = ($method === 'PATCH') ? "application/merge-patch+json" : "application/json";

    $headers = [
        "Accept: application/json",
        "Content-Type: $contentType",
        "X-API-Key: $key"
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix pour InfinityFree
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return ["code" => $httpCode, "response" => $response, "error" => $error];
}

// A. Rechercher le contact par email
$searchUrl = "https://api.systeme.io/api/contacts?email=" . urlencode($email);
$searchResult = callSystemeIO($searchUrl, "GET", null, $apiKey);

if ($searchResult['code'] >= 400) {
    json_exit("error", "Erreur recherche contact", ["debug" => $searchResult]);
}

$searchData = json_decode($searchResult['response'], true);
$contactId = null;

// Si items > 0, le contact existe
if (!empty($searchData['items'])) {
    $contactId = $searchData['items'][0]['id'];
}

// B. Préparer les données
// D'après votre CURL, first_name est standard, les autres sont custom
$payload = [
    "email" => $email,
    "first_name" => $firstName, // Champ standard à la racine
    "fields" => [ // Champs personnalisés dans un tableau d'objets
        [
            "slug" => "archetype", 
            "value" => $archetype
        ],
        [
            "slug" => "traits",    
            "value" => $traits
        ],
        [
            "slug" => "axes",      
            "value" => $axes
        ]
    ]
];

// C. Exécution
if ($contactId) {
    // === MISE À JOUR (PATCH) ===
    // Note: callSystemeIO va mettre automatiquement le header "application/merge-patch+json"
    $updateUrl = "https://api.systeme.io/api/contacts/" . $contactId;
    $result = callSystemeIO($updateUrl, "PATCH", $payload, $apiKey);
    
    if ($result['code'] >= 200 && $result['code'] < 300) {
        json_exit("success", "Profil mis à jour", ["id" => $contactId]);
    } else {
        json_exit("error", "Erreur mise à jour S.IO", ["debug" => $result]);
    }

} else {
    // === CRÉATION (POST) ===
    $createUrl = "https://api.systeme.io/api/contacts";
    $result = callSystemeIO($createUrl, "POST", $payload, $apiKey);
    
    if ($result['code'] >= 200 && $result['code'] < 300) {
        json_exit("success", "Nouveau profil créé", ["data" => json_decode($result['response'])]);
    } else {
        json_exit("error", "Erreur création S.IO", ["debug" => $result]);
    }
}
?>