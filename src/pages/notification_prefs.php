<?php
// User notification preferences page
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';
session_start();
require_login();
$user = $_SESSION['user'];

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Disable/enable for category
    if (isset($_POST['category_id'])) {
        $cat_id = (int)$_POST['category_id'];
        $disable = isset($_POST['disable_cat']) ? 1 : 0;
        $pdo->prepare('INSERT INTO user_notification_prefs (user_id, category_id, disabled) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE disabled = VALUES(disabled)')
            ->execute([$user['id'], $cat_id, $disable]);
    }
    // Disable/enable for grievance
    if (isset($_POST['grievance_id'])) {
        $gid = (int)$_POST['grievance_id'];
        $disable = isset($_POST['disable_griev']) ? 1 : 0;
        $pdo->prepare('INSERT INTO user_notification_prefs (user_id, grievance_id, disabled) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE disabled = VALUES(disabled)')
            ->execute([$user['id'], $gid, $disable]);
    }
    // Disable/enable for all assigned categories
    if (isset($_POST['disable_all_cats'])) {
        $disable = $_POST['disable_all_cats'] ? 1 : 0;
        $assigned = $pdo->prepare('SELECT DISTINCT category_id FROM grievances WHERE assigned_to = ?');
        $assigned->execute([$user['id']]);
        foreach ($assigned->fetchAll(PDO::FETCH_COLUMN) as $cat_id) {
            $pdo->prepare('INSERT INTO user_notification_prefs (user_id, category_id, disabled) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE disabled = 1')
                ->execute([$user['id'], $cat_id]);
        }
    }
}

// Fetch categories and grievances for UI
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$my_grievances = $pdo->prepare('SELECT id, title FROM grievances WHERE assigned_to = ?');
$my_grievances->execute([$user['id']]);
$my_grievances = $my_grievances->fetchAll(PDO::FETCH_ASSOC);
$prefs = $pdo->prepare('SELECT * FROM user_notification_prefs WHERE user_id = ?');
$prefs->execute([$user['id']]);
$prefs = $prefs->fetchAll(PDO::FETCH_ASSOC);
$cat_prefs = [];
$griev_prefs = [];
foreach ($prefs as $p) {
    if ($p['category_id']) $cat_prefs[$p['category_id']] = $p['disabled'];
    if ($p['grievance_id']) $griev_prefs[$p['grievance_id']] = $p['disabled'];
}
$page_title = 'Notification Preferences';
include __DIR__ . '/../includes/layout.php';
?>
<div class="card p-4 mb-4">
    <h3>Notification Preferences</h3>
    <form method="post" class="mb-3">
        <label><b>Disable notifications for all assigned categories</b></label>
        <input type="hidden" name="disable_all_cats" value="1">
        <button type="submit" class="btn btn-warning btn-sm ms-2">Disable All</button>
    </form>
    <h5>By Category</h5>
    <form method="post">
        <table class="table table-bordered">
            <thead><tr><th>Category</th><th>Disable Notifications</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?php echo htmlspecialchars($cat['name']); ?></td>
                    <td><input type="checkbox" name="disable_cat" value="1" <?php if (!empty($cat_prefs[$cat['id']])) echo 'checked'; ?> onchange="this.form.category_id.value='<?php echo $cat['id']; ?>'; this.form.submit();"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <input type="hidden" name="category_id" value="">
    </form>
    <h5>By Grievance</h5>
    <form method="post">
        <table class="table table-bordered">
            <thead><tr><th>Grievance</th><th>Disable Notifications</th></tr></thead>
            <tbody>
            <?php foreach ($my_grievances as $g): ?>
                <tr>
                    <td><?php echo htmlspecialchars($g['title']); ?></td>
                    <td><input type="checkbox" name="disable_griev" value="1" <?php if (!empty($griev_prefs[$g['id']])) echo 'checked'; ?> onchange="this.form.grievance_id.value='<?php echo $g['id']; ?>'; this.form.submit();"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <input type="hidden" name="grievance_id" value="">
    </form>
</div>
