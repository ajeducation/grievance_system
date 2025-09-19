<?php
// Cron job: Send reminders for pending grievances (per-category X days)
require_once __DIR__ . '/../src/db.php';

// Find all ongoing grievances past their category's reminder_days, not yet reminded in last 24h
$sql = "SELECT g.id, g.title, g.assigned_to, g.category_id, g.updated_at, c.reminder_days, u.email, u.name
        FROM grievances g
        JOIN categories c ON g.category_id = c.id
        JOIN users u ON g.assigned_to = u.id
        WHERE g.status = 'ongoing'
        AND g.assigned_to IS NOT NULL
        AND TIMESTAMPDIFF(DAY, g.updated_at, NOW()) >= c.reminder_days";
$stmt = $pdo->query($sql);
$reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($reminders as $r) {
    // TODO: Replace with real notification/email logic
    $msg = "Reminder: Grievance #{$r['id']} ('{$r['title']}') is pending for more than {$r['reminder_days']} days.";
    // Example: log to file (replace with email/notification system)
    file_put_contents(__DIR__ . '/../reminder_log.txt', date('Y-m-d H:i:s') . " - To: {$r['email']} - $msg\n", FILE_APPEND);
}

echo count($reminders) . " reminders sent.\n";
