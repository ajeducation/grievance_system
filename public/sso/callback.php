<?php
// Microsoft SSO OAuth2 callback handler
// This file should be set as the redirect_uri in Entra registration

require_once __DIR__ . '/../../src/db.php';
session_start();

$client_id = 'YOUR_CLIENT_ID';
$client_secret = 'YOUR_CLIENT_SECRET';
$redirect_uri = 'YOUR_CALLBACK_URI';

default_timezone_set('UTC');

if (!isset($_GET['code'])) {
    die('No code provided.');
}

$code = $_GET['code'];

// Exchange code for access token
$token_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
$data = [
    'client_id' => $client_id,
    'scope' => 'openid profile email',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
    'client_secret' => $client_secret,
];

$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
    ],
];
$context  = stream_context_create($options);
$result = file_get_contents($token_url, false, $context);
if ($result === FALSE) {
    die('Error fetching token');
}
$token = json_decode($result, true);
if (!isset($token['access_token'])) {
    die('No access token received');
}

// Fetch user info
$user_url = 'https://graph.microsoft.com/oidc/userinfo';
$opts = [
    'http' => [
        'header' => "Authorization: Bearer {$token['access_token']}\r\n"
    ]
];
$ctx = stream_context_create($opts);
$userinfo = file_get_contents($user_url, false, $ctx);
if ($userinfo === FALSE) {
    die('Error fetching user info');
}
$userinfo = json_decode($userinfo, true);

// Extract user info
$email = $userinfo['email'] ?? $userinfo['preferred_username'] ?? null;
$name = $userinfo['name'] ?? '';
if (!$email) {
    die('No email found in user info');
}

// Check if user exists, else create
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    // Default role: student
    $stmt = $pdo->prepare('INSERT INTO users (name, email, role) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, 'student']);
    $user_id = $pdo->lastInsertId();
    $user = [
        'id' => $user_id,
        'name' => $name,
        'email' => $email,
        'role' => 'student',
    ];
}

// Set session
$_SESSION['user'] = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
];

header('Location: /?page=dashboard');
exit;
