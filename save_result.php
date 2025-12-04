<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username'], $input['email'], $input['archetype'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Champs manquants",
        "recu" => $input
    ]);
    exit;
}

// Mapping archétypes
$archetypeMap = [
    "Guerrier"    => 1,
    "Sage"        => 2,
    "Sorcier"     => 3,
    "Explorateur" => 4,
    "Souverain"   => 5,
    "Guérisseur"  => 6,
    "Rebelle"     => 7,
    "Courtisan"   => 8,
    "Prêtre"      => 9,
    "Artisan"     => 10,
    "Ermite"      => 11,
    "Barde"       => 12
];

$archetypeName = $input['archetype'];
if (!isset($archetypeMap[$archetypeName])) {
    echo json_encode([
        "status" => "error",
        "message" => "Archetype inconnu côté Systeme.io",
        "recu" => $archetypeName
    ]);
    exit;
}

// Préparer les champs personnalisés
$fields = [];

// Archetype converti en string
$fields[] = [
    "slug"  => "archetype",
    "value" => strval($archetypeName)
];

// Traits et axes (convertis en string)
if (!empty($input['traits'])) {
    $fields[] = [
        "slug"  => "traits",
        "value" => is_array($input['traits']) ? implode(", ", $input['traits']) : strval($input['traits'])
    ];
}
if (!empty($input['work_axes'])) {
    $fields[] = [
        "slug"  => "axes",
        "value" => is_array($input['work_axes']) ? implode(", ", $input['work_axes']) : strval($input['work_axes'])
    ];
}

// Ajouter automatiquement les autres champs
foreach ($input as $key => $value) {
    if (in_array($key, ['username', 'email', 'archetype', 'traits', 'work_axes'])) continue;

    $fields[] = [
        "slug"  => $key,
        "value" => is_array($value) ? implode(", ", $value) : strval($value)
    ];
}

$apiKey = "pmkavvcyicbdpzfof429m2wsdzsh0nnbdbvbz1jdg8kq817srnp1gr2r88efi8xv";
$url = "https://api.systeme.io/api/contacts";

$postData = [
    "email"      => $input['email'],
    "first_name" => $input['username'],
    "fields"     => $fields
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "X-API-Key: $apiKey"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode([
        "status" => "success",
        "message" => "Enregistré avec succès",
        "recu" => $input
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Erreur lors de l'envoi à Systeme.io",
        "recu" => $input,
        "api_response" => $response
    ]);
}
