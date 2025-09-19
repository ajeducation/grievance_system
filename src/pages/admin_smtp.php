<?php
// Admin SMTP configuration page
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';
session_start();
require_login();
if (!has_role('admin')) {
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit;
}
$smtp_file = __DIR__ . '/../../config/smtp.php';
$smtp = file_exists($smtp_file) ? include $smtp_file : [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtp['host'] = $_POST['host'] ?? '';
    $smtp['port'] = (int)($_POST['port'] ?? 587);
    $smtp['username'] = $_POST['username'] ?? '';
    $smtp['password'] = $_POST['password'] ?? '';
    $smtp['from_email'] = $_POST['from_email'] ?? '';
    $smtp['from_name'] = $_POST['from_name'] ?? '';
    $smtp['encryption'] = $_POST['encryption'] ?? 'tls';
    $config = "<?php\nreturn " . var_export($smtp, true) . ";\n";
    file_put_contents($smtp_file, $config);
    echo '<div class="alert alert-success">SMTP settings updated.</div>';
}
$page_title = 'SMTP Settings';
include __DIR__ . '/../includes/layout.php';
?>
<div class="card p-4 mb-4" style="max-width:600px;margin:auto;">
    <h3>SMTP Email Settings</h3>
    <form method="post">
        <div class="mb-2">
            <label>SMTP Host</label>
            <input type="text" name="host" class="form-control" value="<?php echo htmlspecialchars($smtp['host'] ?? ''); ?>" required>
        </div>
        <div class="mb-2">
            <label>SMTP Port</label>
            <input type="number" name="port" class="form-control" value="<?php echo htmlspecialchars($smtp['port'] ?? 587); ?>" required>
        </div>
        <div class="mb-2">
            <label>SMTP Username</label>
            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($smtp['username'] ?? ''); ?>">
        </div>
        <div class="mb-2">
            <label>SMTP Password</label>
            <input type="password" name="password" class="form-control" value="<?php echo htmlspecialchars($smtp['password'] ?? ''); ?>">
        </div>
        <div class="mb-2">
            <label>From Email</label>
            <input type="email" name="from_email" class="form-control" value="<?php echo htmlspecialchars($smtp['from_email'] ?? ''); ?>" required>
        </div>
        <div class="mb-2">
            <label>From Name</label>
            <input type="text" name="from_name" class="form-control" value="<?php echo htmlspecialchars($smtp['from_name'] ?? ''); ?>">
        </div>
        <div class="mb-2">
            <label>Encryption</label>
            <select name="encryption" class="form-control">
                <option value="tls" <?php if (($smtp['encryption'] ?? '') === 'tls') echo 'selected'; ?>>TLS</option>
                <option value="ssl" <?php if (($smtp['encryption'] ?? '') === 'ssl') echo 'selected'; ?>>SSL</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>
