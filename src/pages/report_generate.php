<?php
// Generate user report from template, replace placeholders, export as HTML, Word, or PDF
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../vendor/autoload.php'; // For PhpWord and Dompdf

session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','manager','superadmin'])) {
    echo 'Access denied.';
    exit;
}

// Get template from config
$stmt = $pdo->prepare('SELECT config_value FROM config WHERE config_key = ?');
$stmt->execute(['report_template']);
$row = $stmt->fetch();
$template = $row ? $row['config_value'] : '<h2>Grievance Report</h2>';

// Compute parameters (example)
$total_grievances = $pdo->query('SELECT COUNT(*) FROM grievances')->fetchColumn();
$category_name = 'All Categories';
$date_range = date('Y-m-01') . ' to ' . date('Y-m-d');

// Replace placeholders
$html = str_replace([
    '{{total_grievances}}',
    '{{category_name}}',
    '{{date_range}}'
], [
    $total_grievances,
    $category_name,
    $date_range
], $template);

$type = $_GET['type'] ?? 'html';
if ($type === 'word') {
    // Export as Word using PhpWord
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $section = $phpWord->addSection();
    \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html);
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="grievance_report_' . date('Ymd_His') . '.docx"');
    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
    exit;
} elseif ($type === 'pdf') {
    // Export as PDF using Dompdf
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="grievance_report_' . date('Ymd_His') . '.pdf"');
    echo $dompdf->output();
    exit;
} else {
    // HTML preview
    echo $html;
}
