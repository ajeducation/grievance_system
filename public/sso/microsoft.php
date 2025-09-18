<?php
// Placeholder for Microsoft SSO (OAuth2) integration
// You will need to register your app in Microsoft Entra and fill in the client_id, client_secret, and redirect_uri

$client_id = 'YOUR_CLIENT_ID';
$redirect_uri = 'YOUR_CALLBACK_URI'; // e.g., https://yourdomain.com/sso/callback.php
$scope = 'openid profile email';
$authorize_url = "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?client_id=$client_id&response_type=code&redirect_uri=$redirect_uri&response_mode=query&scope=$scope";

header('Location: ' . $authorize_url);
exit;
