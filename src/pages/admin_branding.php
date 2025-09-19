<?php
// Superadmin branding config: title, fonts, logo, favicon, background image
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../src/db.php';
require_login();
if ($_SESSION['user']['role'] !== 'superadmin') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit;
}

// Handle uploads and config
$fields = [
    'system_title', 'title_font_size', 'title_font_family', 'title_font_weight'
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $stmt = $pdo->prepare('REPLACE INTO config (config_key, config_value) VALUES (?, ?)');
            $stmt->execute(["branding_$f", $_POST[$f]]);
        }
    }
    // Handle logo
    if (!empty($_FILES['logo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $dest = '/public/logo.' . $ext;
        move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/..' . $dest);
        $stmt = $pdo->prepare('REPLACE INTO config (config_key, config_value) VALUES (?, ?)');
        $stmt->execute(['branding_logo_path', $dest]);
    }
    // Handle favicon
    if (!empty($_FILES['favicon']['name'])) {
        $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
        $dest = '/public/favicon.' . $ext;
        move_uploaded_file($_FILES['favicon']['tmp_name'], __DIR__ . '/..' . $dest);
        $stmt = $pdo->prepare('REPLACE INTO config (config_key, config_value) VALUES (?, ?)');
        $stmt->execute(['branding_favicon_path', $dest]);
    }
    // Handle background image
    if (!empty($_FILES['bg_image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['bg_image']['name'], PATHINFO_EXTENSION));
        $dest = '/public/login_bg.' . $ext;
        move_uploaded_file($_FILES['bg_image']['tmp_name'], __DIR__ . '/..' . $dest);
        $stmt = $pdo->prepare('REPLACE INTO config (config_key, config_value) VALUES (?, ?)');
        $stmt->execute(['branding_bg_image_path', $dest]);
    }
    echo '<div class="alert alert-success">Branding updated.</div>';
}
// Load current config
$defaults = [
    'system_title' => 'Grievance System',
    'title_font_size' => '2.5rem',
    'title_font_family' => 'Arial, sans-serif',
    'title_font_weight' => 'bold',
    'logo_path' => '/public/logo.png',
    'favicon_path' => '/public/favicon.ico',
    'bg_image_path' => '/public/login_bg.jpg',
];
$config = $defaults;
$stmt = $pdo->query("SELECT config_key, config_value FROM config WHERE config_key LIKE 'branding_%' OR config_key LIKE 'system_title%' ");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $key = str_replace('branding_', '', $row['config_key']);
    $config[$key] = $row['config_value'];
}
$page_title = 'Branding & Login Page Settings';
ob_start();
?>
<h2>Branding & Login Page Settings</h2>
<form method="post" enctype="multipart/form-data" class="mb-4">
    <div class="mb-3">
        <label>System Title</label>
        <input type="text" name="system_title" class="form-control" value="<?php echo htmlspecialchars($config['system_title']); ?>" required>
    </div>
    <div class="mb-3">
        <label>Title Font Size</label>
        <input type="text" name="title_font_size" class="form-control" value="<?php echo htmlspecialchars($config['title_font_size']); ?>">
    </div>
    <div class="mb-3">
        <label>Title Font Family</label>
        <input type="text" name="title_font_family" class="form-control" value="<?php echo htmlspecialchars($config['title_font_family']); ?>">
    </div>
    <div class="mb-3">
        <label>Title Font Weight</label>
        <input type="text" name="title_font_weight" class="form-control" value="<?php echo htmlspecialchars($config['title_font_weight']); ?>">
    </div>
    <div class="mb-3">
        <label>Logo Image (max 200x200px, PNG/JPG)</label><br>
        <?php if (file_exists(__DIR__ . '/..' . $config['logo_path'])): ?>
            <img src="<?php echo $config['logo_path']; ?>" alt="Logo" style="max-width:100px;max-height:100px;">
        <?php endif; ?>
        <input type="file" name="logo" accept="image/png,image/jpeg" class="form-control">
    </div>
    <div class="mb-3">
        <label>Favicon (ICO/PNG, 32x32px)</label><br>
        <?php if (file_exists(__DIR__ . '/..' . $config['favicon_path'])): ?>
            <img src="<?php echo $config['favicon_path']; ?>" alt="Favicon" style="max-width:32px;max-height:32px;">
        <?php endif; ?>
        <input type="file" name="favicon" accept="image/x-icon,image/png" class="form-control">
    </div>
    <div class="mb-3">
        <label>Login Page Background Image (JPG/PNG, large)</label><br>
        <?php if (file_exists(__DIR__ . '/..' . $config['bg_image_path'])): ?>
            <img src="<?php echo $config['bg_image_path']; ?>" alt="Background" style="max-width:200px;max-height:100px;">
        <?php endif; ?>
        <input type="file" name="bg_image" accept="image/png,image/jpeg" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">Save Branding</button>
</form>
<?php $content = ob_get_clean(); include __DIR__ . '/../includes/layout.php'; ?>
