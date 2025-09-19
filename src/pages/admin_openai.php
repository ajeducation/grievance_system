<?php
// Superadmin OpenAI API key management
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../src/db.php';
require_login();
if ($_SESSION['user']['role'] !== 'superadmin') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit;
}

$key = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['openai_api_key'])) {
    $key = trim($_POST['openai_api_key']);
    $stmt = $pdo->prepare('REPLACE INTO config (config_key, config_value) VALUES (?, ?)');
    $stmt->execute(['openai_api_key', $key]);
    echo '<div class="alert alert-success">API key updated.</div>';
}
$stmt = $pdo->prepare('SELECT config_value FROM config WHERE config_key = ?');
$stmt->execute(['openai_api_key']);
$row = $stmt->fetch();
if ($row) $key = $row['config_value'];

$page_title = 'OpenAI Integration';
ob_start();
?>
<h2>OpenAI API Key Management</h2>
<form method="post" class="mb-4" autocomplete="off">
    <div class="form-group mb-2">
        <label for="openai_api_key">OpenAI API Key</label>
        <input type="password" name="openai_api_key" id="openai_api_key" class="form-control" value="<?php echo htmlspecialchars($key); ?>" autocomplete="new-password" required>
        <small class="form-text text-muted">Superadmin only. Stored securely in the config table.</small>
    </div>
    <button type="submit" class="btn btn-primary">Save API Key</button>
</form>
<?php $content = ob_get_clean(); include __DIR__ . '/../includes/layout.php'; ?>
