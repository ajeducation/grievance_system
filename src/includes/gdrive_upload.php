<?php
// Google Drive file upload handler (requires Google API Client)
function gdrive_upload($localPath, $destName, $cfg) {
    // You must install google/apiclient via Composer and set up credentials
    require_once __DIR__ . '/../../vendor/autoload.php';
    $client = new Google_Client();
    $client->setClientId($cfg['gdrive_client_id']);
    $client->setClientSecret($cfg['gdrive_client_secret']);
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');
    $client->setScopes(['https://www.googleapis.com/auth/drive.file']);
    $client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
    $client->setAccessToken(['refresh_token' => $cfg['gdrive_refresh_token']]);
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    }
    $service = new Google_Service_Drive($client);
    $fileMetadata = [
        'name' => $destName,
        'parents' => [$cfg['gdrive_folder_id']]
    ];
    $content = file_get_contents($localPath);
    $file = new Google_Service_Drive_DriveFile($fileMetadata);
    $uploaded = $service->files->create($file, [
        'data' => $content,
        'mimeType' => mime_content_type($localPath),
        'uploadType' => 'multipart',
        'fields' => 'id,webViewLink'
    ]);
    return $uploaded->webViewLink ?? true;
}
