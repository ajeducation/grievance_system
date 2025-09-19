<?php
// Analytics AI suggestion endpoint (manager/admin)
require_once __DIR__ . '/../src/db.php';
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','manager','superadmin'])) {
    http_response_code(403);
    echo json_encode(['error'=>'Access denied']);
    exit;
}

// Get OpenAI API key from config
global $pdo;
$stmt = $pdo->prepare('SELECT config_value FROM config WHERE config_key = ?');
$stmt->execute(['openai_api_key']);
$row = $stmt->fetch();
if (!$row || !$row['config_value']) {
    echo json_encode(['error'=>'OpenAI API key not set']);
    exit;
}
$api_key = $row['config_value'];

// Get prompt from POST
 $data = json_decode(file_get_contents('php://input'), true);
$prompt = trim($data['prompt'] ?? '');
if (!$prompt) {
    echo json_encode(['error'=>'No prompt provided']);
    exit;
}

// Optionally, add some analytics data to the prompt
$grieves = $pdo->query('SELECT status, category_id FROM grievances')->fetchAll(PDO::FETCH_ASSOC);
$cat_stats = $pdo->query('SELECT c.name, COUNT(g.id) AS total FROM categories c LEFT JOIN grievances g ON g.category_id = c.id GROUP BY c.id')->fetchAll(PDO::FETCH_ASSOC);

$analytics = "Category stats: ";
foreach ($cat_stats as $row) {
    $analytics .= $row['name'] . ': ' . $row['total'] . '; ';
}

$full_prompt = $prompt . "\n" . $analytics;

// Call OpenAI API (GPT-3.5 Turbo)
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $api_key,
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'gpt-3.5-turbo',
    'messages' => [
        ['role'=>'system','content'=>'You are an analytics assistant for a grievance management system.'],
        ['role'=>'user','content'=>$full_prompt],
    ],
    'max_tokens' => 300,
]));
$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['error'=>'OpenAI API error: '.curl_error($ch)]);
    exit;
}
curl_close($ch);
$res = json_decode($response, true);
if (isset($res['choices'][0]['message']['content'])) {
    echo json_encode(['result'=>$res['choices'][0]['message']['content']]);
} else {
    echo json_encode(['error'=>'No response from OpenAI']);
}
