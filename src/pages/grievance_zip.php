<?php
// Download all attachments for selected grievances as ZIP
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';
session_start();
require_login();
$user = $_SESSION['user'];
if (!($user['role'] === 'admin' || $user['role'] === 'manager')) {
    http_response_code(403);
    exit('Forbidden');
}
if (!isset($_POST['selected_grievances']) || !is_array($_POST['selected_grievances'])) {
    exit('No grievances selected.');
}
$ids = array_map('intval', $_POST['selected_grievances']);
$in = str_repeat('?,', count($ids)-1) . '?';
$stmt = $pdo->prepare('SELECT file_name, file_path FROM grievance_attachments WHERE grievance_id IN (' . $in . ')');
$stmt->execute($ids);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$files) exit('No attachments found.');
$zip = new ZipArchive();
$tmp = tempnam(sys_get_temp_dir(), 'zip');
$zip->open($tmp, ZipArchive::OVERWRITE);
foreach ($files as $f) {
    $full = __DIR__ . '/../../public/uploads/' . $f['file_path'];
    if (file_exists($full)) {
        $zip->addFile($full, $f['file_name']);
    }
}
$zip->close();
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="grievance_attachments_'.date('Ymd_His').'.zip"');
readfile($tmp);
unlink($tmp);
exit;
