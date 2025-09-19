<?php
// OneDrive file upload handler (requires Microsoft Graph API)
function onedrive_upload($localPath, $destName, $cfg) {
    // You must install microsoft/microsoft-graph via Composer and set up credentials
    require_once __DIR__ . '/../../vendor/autoload.php';
    $accessToken = $cfg['onedrive_refresh_token']; // Should be a valid access token or use refresh flow
    $client = new Microsoft\Graph\Graph();
    $client->setAccessToken($accessToken);
    $folderId = $cfg['onedrive_folder_id'];
    $url = "/me/drive/items/$folderId:/{$destName}:/content";
    $content = file_get_contents($localPath);
    $response = $client->createRequest('PUT', $url)
        ->addHeaders(["Content-Type" => mime_content_type($localPath)])
        ->attachBody($content)
        ->execute();
    return $response ? true : false;
}
