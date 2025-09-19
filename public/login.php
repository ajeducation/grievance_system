<?php
// Login page with Microsoft SSO and Superadmin login, branding, and background image
session_start();
require_once __DIR__ . '/../src/db.php';

// Fetch branding config
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

if (isset($_SESSION['user'])) {
    header('Location: /public/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['superadmin_email'], $_POST['superadmin_pass'])) {
    $email = $_POST['superadmin_email'];
    $pass = $_POST['superadmin_pass'];
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ?');
    $stmt->execute([$email, 'superadmin']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user'] = $user;
        header('Location: /public/index.php');
        exit;
    } else {
        $error = 'Invalid super-admin credentials.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($config['system_title']); ?></title>
    <link rel="icon" href="<?php echo htmlspecialchars($config['favicon_path']); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body, html { height: 100%; margin: 0; }
        .login-bg {
            background: url('<?php echo htmlspecialchars($config['bg_image_path']); ?>') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .login-box {
            background: rgba(255,255,255,0.95);
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            margin: 40px auto 0 auto;
            max-width: 400px;
            padding: 2rem 2.5rem 2rem 2.5rem;
        }
        .system-title {
            font-size: <?php echo htmlspecialchars($config['title_font_size']); ?>;
            font-family: <?php echo htmlspecialchars($config['title_font_family']); ?>;
            font-weight: <?php echo htmlspecialchars($config['title_font_weight'] ?? 'bold'); ?>;
            text-align: center;
            margin-bottom: 1rem;
        }
        .logo {
            display: block;
            margin: 0 auto 1rem auto;
            max-width: 120px;
            max-height: 120px;
        }
    </style>
</head>
<body class="login-bg">
<div class="login-container">
    <div style="height: 8vh;"></div>
    <div class="login-box">
        <?php if (file_exists(__DIR__ . $config['logo_path'])): ?>
            <img src="<?php echo htmlspecialchars($config['logo_path']); ?>" alt="Logo" class="logo">
        <?php endif; ?>
        <div class="system-title"><?php echo htmlspecialchars($config['system_title']); ?></div>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <div class="mb-3">
            <a href="/public/sso/login.php" class="btn btn-outline-primary w-100 mb-2">Login with Microsoft Account</a>
        </div>
        <form method="post">
            <div class="mb-2 text-center text-muted">Or Super Admin Login</div>
            <div class="mb-2">
                <input type="email" name="superadmin_email" class="form-control" placeholder="Super Admin Email" required>
            </div>
            <div class="mb-2">
                <input type="password" name="superadmin_pass" class="form-control" placeholder="Password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login as Super Admin</button>
        </form>
    </div>
</div>
</body>
</html>
