<?php
// File storage abstraction for local, Google Drive, OneDrive
function save_uploaded_file($file, $destName) {
    $cfg = require __DIR__ . '/../../config/storage.php';
    if (($cfg['type'] ?? 'local') === 'gdrive') {
        // Google Drive upload
        require_once __DIR__ . '/gdrive_upload.php';
        return gdrive_upload($file['tmp_name'], $destName, $cfg);
    } elseif (($cfg['type'] ?? 'local') === 'onedrive') {
        require_once __DIR__ . '/onedrive_upload.php';
        return onedrive_upload($file['tmp_name'], $destName, $cfg);
    } else {
        // Local storage
        $dest = __DIR__ . '/../../public/uploads/' . $destName;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return '/uploads/' . $destName;
        }
        return false;
    }
}
