<?php
// NAAC-compliant grievance report export (CSV)
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="naac_grievance_report_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');
// NAAC recommended columns (customize as needed)
fputcsv($out, [
    'Grievance ID', 'Student Name', 'Student Email', 'Category', 'Title', 'Description', 'Status', 'Assigned Staff', 'Created At', 'Completed At', 'Appeal Status', 'Appeal Comment', 'Appeal Submitted At', 'Final Resolution'
]);

$sql = 'SELECT g.id, u.name AS student_name, u.email AS student_email, c.name AS category, g.title, g.description, g.status, s.name AS staff_name, g.created_at, g.updated_at,
        ga.status AS appeal_status, ga.comment AS appeal_comment, ga.created_at AS appeal_submitted, ga2.status AS final_resolution
        FROM grievances g
        JOIN users u ON g.user_id = u.id
        JOIN categories c ON g.category_id = c.id
        LEFT JOIN users s ON g.assigned_to = s.id
        LEFT JOIN grievance_appeals ga ON ga.grievance_id = g.id
        LEFT JOIN grievance_appeals ga2 ON ga2.grievance_id = g.id AND ga2.status IN ("accepted","rejected")
        ORDER BY g.id DESC';
$stmt = $pdo->query($sql);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        $row['id'],
        $row['student_name'],
        $row['student_email'],
        $row['category'],
        $row['title'],
        $row['description'],
        $row['status'],
        $row['staff_name'],
        $row['created_at'],
        $row['updated_at'],
        $row['appeal_status'],
        $row['appeal_comment'],
        $row['appeal_submitted'],
        $row['final_resolution']
    ]);
}
fclose($out);
exit;
