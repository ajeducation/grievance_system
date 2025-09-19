<?php
// Export selected grievances to CSV
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
$sql = 'SELECT g.*, c.name AS category_name, u.name AS assigned_name FROM grievances g JOIN categories c ON g.category_id = c.id LEFT JOIN users u ON g.assigned_to = u.id WHERE g.id IN (' . $in . ')';
$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="grievances_export_'.date('Ymd_His').'.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['ID','Title','Description','Category','Status','Assigned To','Created','Updated']);
foreach ($rows as $r) {
    fputcsv($out, [$r['id'],$r['title'],$r['description'],$r['category_name'],$r['status'],$r['assigned_name'],$r['created_at'],$r['updated_at']]);
}
fclose($out);
exit;
