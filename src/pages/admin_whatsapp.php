<?php
// Admin WhatsApp API configuration page
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';
session_start();
require_login();
if (!has_role('admin')) {
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit;
}
$wa_file = __DIR__ . '/../../config/whatsapp.php';
$wa = file_exists($wa_file) ? include $wa_file : [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wa['enabled'] = isset($_POST['enabled']) ? true : false;
    $wa['api_url'] = $_POST['api_url'] ?? '';
    $wa['api_key'] = $_POST['api_key'] ?? '';
    $wa['sender_id'] = $_POST['sender_id'] ?? '';
    $config = "<?php\nreturn " . var_export($wa, true) . ";\n";
    file_put_contents($wa_file, $config);
    echo '<div class="alert alert-success">WhatsApp API settings updated.</div>';
}
$page_title = 'WhatsApp API Settings';
include __DIR__ . '/../includes/layout.php';
?>
<div class="card p-4 mb-4" style="max-width:600px;margin:auto;">
    <h3>WhatsApp API Settings</h3>
    <form method="post">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="enabled" id="wa_enabled" value="1" <?php if (!empty($wa['enabled'])) echo 'checked'; ?>>
            <label class="form-check-label" for="wa_enabled">Enable WhatsApp Notifications</label>
        </div>
        <div class="mb-2">
            <label>API URL</label>
            <input type="text" name="api_url" class="form-control" value="<?php echo htmlspecialchars($wa['api_url'] ?? ''); ?>">
        </div>
        <div class="mb-2">
            <label>API Key/Token</label>
            <input type="text" name="api_key" class="form-control" value="<?php echo htmlspecialchars($wa['api_key'] ?? ''); ?>">
        </div>
        <div class="mb-2">
            <label>Sender ID/Number</label>
            <input type="text" name="sender_id" class="form-control" value="<?php echo htmlspecialchars($wa['sender_id'] ?? ''); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>
