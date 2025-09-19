<?php
// Superadmin report template editor (rich text)
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../src/db.php';
require_login();
if ($_SESSION['user']['role'] !== 'superadmin') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit;
}

$template = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_template'])) {
    $template = $_POST['report_template'];
    $stmt = $pdo->prepare('REPLACE INTO config (config_key, config_value) VALUES (?, ?)');
    $stmt->execute(['report_template', $template]);
    echo '<div class="alert alert-success">Template updated.</div>';
}
$stmt = $pdo->prepare('SELECT config_value FROM config WHERE config_key = ?');
$stmt->execute(['report_template']);
$row = $stmt->fetch();
if ($row) $template = $row['config_value'];

$page_title = 'Report Template Editor';
ob_start();
?>
<h2>Report Template Editor</h2>
<form method="post" class="mb-4">
    <textarea id="report_template" name="report_template" rows="12" class="form-control"><?php echo htmlspecialchars($template); ?></textarea>
    <small class="form-text text-muted">Use placeholders like <code>{{total_grievances}}</code>, <code>{{category_name}}</code>, <code>{{date_range}}</code>. You can insert images and rich formatting.</small>
    <button type="submit" class="btn btn-primary mt-2">Save Template</button>
</form>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/skins/ui/oxide/skin.min.css">
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js"></script>
<script>
tinymce.init({
  selector: '#report_template',
  plugins: 'image link lists table code',
  toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright | bullist numlist | image link table | code',
  height: 400,
  menubar: false
});
</script>
<?php $content = ob_get_clean(); include __DIR__ . '/../includes/layout.php'; ?>
