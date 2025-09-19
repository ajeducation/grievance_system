<?php
// Admin file storage configuration page
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';
session_start();
require_login();
if (!has_role('admin')) {
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit;
}
$storage_file = __DIR__ . '/../../config/storage.php';
$storage = file_exists($storage_file) ? include $storage_file : [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $storage['type'] = $_POST['type'] ?? 'local';
    // Google Drive
    $storage['gdrive_client_id'] = $_POST['gdrive_client_id'] ?? '';
    $storage['gdrive_client_secret'] = $_POST['gdrive_client_secret'] ?? '';
    $storage['gdrive_refresh_token'] = $_POST['gdrive_refresh_token'] ?? '';
    $storage['gdrive_folder_id'] = $_POST['gdrive_folder_id'] ?? '';
    // OneDrive
    $storage['onedrive_client_id'] = $_POST['onedrive_client_id'] ?? '';
    $storage['onedrive_client_secret'] = $_POST['onedrive_client_secret'] ?? '';
    $storage['onedrive_refresh_token'] = $_POST['onedrive_refresh_token'] ?? '';
    $storage['onedrive_folder_id'] = $_POST['onedrive_folder_id'] ?? '';
    $config = "<?php\nreturn " . var_export($storage, true) . ";\n";
    file_put_contents($storage_file, $config);
    echo '<div class="alert alert-success">Storage settings updated.</div>';
}
$page_title = 'File Storage Settings';
include __DIR__ . '/../includes/layout.php';
?>
<div class="card p-4 mb-4" style="max-width:700px;margin:auto;">
    <h3>File Storage Settings</h3>
    <form method="post">
        <div class="mb-3">
            <label><b>Storage Type</b></label>
            <select name="type" class="form-control" onchange="this.form.submit()">
                <option value="local" <?php if (($storage['type'] ?? 'local') === 'local') echo 'selected'; ?>>Local Server</option>
                <option value="gdrive" <?php if (($storage['type'] ?? '') === 'gdrive') echo 'selected'; ?>>Google Drive</option>
                <option value="onedrive" <?php if (($storage['type'] ?? '') === 'onedrive') echo 'selected'; ?>>OneDrive</option>
            </select>
        </div>
        <div id="gdrive_fields" style="display:<?php echo ($storage['type'] ?? '') === 'gdrive' ? 'block' : 'none'; ?>;">
            <h5>Google Drive Settings</h5>
            <div class="mb-2"><label>Client ID</label><input type="text" name="gdrive_client_id" class="form-control" value="<?php echo htmlspecialchars($storage['gdrive_client_id'] ?? ''); ?>"></div>
            <div class="mb-2"><label>Client Secret</label><input type="text" name="gdrive_client_secret" class="form-control" value="<?php echo htmlspecialchars($storage['gdrive_client_secret'] ?? ''); ?>"></div>
            <div class="mb-2"><label>Refresh Token</label><input type="text" name="gdrive_refresh_token" class="form-control" value="<?php echo htmlspecialchars($storage['gdrive_refresh_token'] ?? ''); ?>"></div>
            <div class="mb-2"><label>Folder ID</label><input type="text" name="gdrive_folder_id" class="form-control" value="<?php echo htmlspecialchars($storage['gdrive_folder_id'] ?? ''); ?>"></div>
        </div>
        <div id="onedrive_fields" style="display:<?php echo ($storage['type'] ?? '') === 'onedrive' ? 'block' : 'none'; ?>;">
            <h5>OneDrive Settings</h5>
            <div class="mb-2"><label>Client ID</label><input type="text" name="onedrive_client_id" class="form-control" value="<?php echo htmlspecialchars($storage['onedrive_client_id'] ?? ''); ?>"></div>
            <div class="mb-2"><label>Client Secret</label><input type="text" name="onedrive_client_secret" class="form-control" value="<?php echo htmlspecialchars($storage['onedrive_client_secret'] ?? ''); ?>"></div>
            <div class="mb-2"><label>Refresh Token</label><input type="text" name="onedrive_refresh_token" class="form-control" value="<?php echo htmlspecialchars($storage['onedrive_refresh_token'] ?? ''); ?>"></div>
            <div class="mb-2"><label>Folder ID</label><input type="text" name="onedrive_folder_id" class="form-control" value="<?php echo htmlspecialchars($storage['onedrive_folder_id'] ?? ''); ?>"></div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
    </form>
</div>
<script>
// Show/hide fields based on storage type
const typeSelect = document.querySelector('select[name="type"]');
typeSelect.addEventListener('change', function() {
    document.getElementById('gdrive_fields').style.display = this.value === 'gdrive' ? 'block' : 'none';
    document.getElementById('onedrive_fields').style.display = this.value === 'onedrive' ? 'block' : 'none';
});
</script>
