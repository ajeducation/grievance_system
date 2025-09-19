<?php
// Cron job: Auto-escalate overdue grievances (per-category Y days)
require_once __DIR__ . '/../src/db.php';

// Find all ongoing grievances past their category's escalation_days
$sql = "SELECT g.id, g.title, g.assigned_to, g.category_id, g.updated_at, c.escalation_days, u.email, u.name, c.name AS category_name
        FROM grievances g
        JOIN categories c ON g.category_id = c.id
        JOIN users u ON g.assigned_to = u.id
        WHERE g.status = 'ongoing'
        AND g.assigned_to IS NOT NULL
        AND TIMESTAMPDIFF(DAY, g.updated_at, NOW()) >= c.escalation_days";
$stmt = $pdo->query($sql);
$overdues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Find all admins/managers
$admins = $pdo->query("SELECT email, name FROM users WHERE role IN ('admin','manager')")->fetchAll(PDO::FETCH_ASSOC);

foreach ($overdues as $r) {
    $msg = "Escalation: Grievance #{$r['id']} ('{$r['title']}') in category '{$r['category_name']}' is overdue (no update for {$r['escalation_days']}+ days).";
    // Example: log to file (replace with real escalation/notification system)
    foreach ($admins as $admin) {
        file_put_contents(__DIR__ . '/../escalation_log.txt', date('Y-m-d H:i:s') . " - To: {$admin['email']} - $msg\n", FILE_APPEND);
    }
    // Optionally, mark as escalated in DB or add a grievance_action
    $pdo->prepare('INSERT INTO grievance_actions (grievance_id, action_taken, marked_by) VALUES (?, ?, ?)')->execute([$r['id'], 'Auto-escalated (overdue)', 0]);
}

echo count($overdues) . " grievances escalated.\n";
