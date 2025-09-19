<?php
// Installer script for Student Grievance System

session_start();
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';

// Dependency check (Step 0)
function check_dependencies() {
    $errors = [];
    if (version_compare(PHP_VERSION, '7.4', '<')) $errors[] = 'PHP 7.4 or higher required.';
    foreach ([
        'pdo_mysql' => 'PDO MySQL',
        'curl' => 'cURL',
        'mbstring' => 'mbstring',
        'gd' => 'GD',
        'fileinfo' => 'fileinfo',
        'zip' => 'zip',
        'xml' => 'xml',
    ] as $ext => $desc) {
        if (!extension_loaded($ext)) $errors[] = "$desc PHP extension missing.";
    }
    // Composer dependencies
    $vendor = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($vendor)) $errors[] = 'Composer dependencies not installed. Run <code>composer install</code>.';
    else {
        if (!class_exists('PhpOffice\\PhpWord\\PhpWord')) $errors[] = 'PhpWord not installed. Run <code>composer require phpoffice/phpword</code>.';
        if (!class_exists('Dompdf\\Dompdf')) $errors[] = 'Dompdf not installed. Run <code>composer require dompdf/dompdf</code>.';
    }
    return $errors;
}

if ($step === 0) {
    $dep_errors = check_dependencies();
}

function write_config($dbhost, $dbname, $dbuser, $dbpass) {
    $config = "<?php\nreturn [\n    'host' => '$dbhost',\n    'dbname' => '$dbname',\n    'username' => '$dbuser',\n    'password' => '$dbpass',\n    'charset' => 'utf8mb4',\n];\n";
    file_put_contents(__DIR__ . '/../config/database.php', $config);
}

function create_tables($pdo) {
    $sql = file_get_contents(__DIR__ . '/../install/schema.sql');
    $pdo->exec($sql);
}

if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['dbhost'] = $_POST['dbhost'];
    $_SESSION['dbname'] = $_POST['dbname'];
    $_SESSION['dbuser'] = $_POST['dbuser'];
    $_SESSION['dbpass'] = $_POST['dbpass'];
    header('Location: ?step=2');
    exit;
}

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbhost = $_SESSION['dbhost'];
    $dbname = $_SESSION['dbname'];
    $dbuser = $_SESSION['dbuser'];
    $dbpass = $_SESSION['dbpass'];
    try {
        $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        create_tables($pdo);
        write_config($dbhost, $dbname, $dbuser, $dbpass);
        $_SESSION['install_ok'] = true;
        header('Location: ?step=3');
        exit;
    } catch (PDOException $e) {
        $error = 'Database connection failed: ' . $e->getMessage();
    }
}

if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['admin_name'];
    $email = $_POST['admin_email'];
    $pass1 = $_POST['admin_pass'];
    $pass2 = $_POST['admin_pass2'];
    if ($pass1 !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $pass = password_hash($pass1, PASSWORD_DEFAULT);
        $pdo = new PDO(
            "mysql:host={$_SESSION['dbhost']};dbname={$_SESSION['dbname']};charset=utf8mb4",
            $_SESSION['dbuser'],
            $_SESSION['dbpass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)")->execute([$name, $email, $pass, 'admin']);
        $_SESSION['install_done'] = true;
        header('Location: ?step=4');
        exit;
    }
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Install Student Grievance System</title>
    <link rel="stylesheet" href="/public/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5" style="max-width:600px;">
    <div class="card p-4 shadow-sm">
        <h2 class="mb-4">Install Student Grievance System</h2>
        <?php if ($step === 0): ?>
            <h5>Dependency Check</h5>
            <?php $dep_errors = check_dependencies(); ?>
            <?php if ($dep_errors): ?>
                <div class="alert alert-danger">
                    <b>Missing dependencies:</b><br>
                    <ul>
                        <?php foreach ($dep_errors as $err): ?><li><?php echo $err; ?></li><?php endforeach; ?>
                    </ul>
                    <hr>
                    <b>Suggestions:</b><br>
                    <ul>
                        <li>Install PHP extensions using <code>sudo apt install php-xml php-mbstring php-gd php-curl php-zip php-fileinfo</code></li>
                        <li>Install Composer dependencies using <code>composer install</code></li>
                        <li>Install missing packages as suggested above</li>
                    </ul>
                </div>
                <a href="?step=0" class="btn btn-secondary">Re-check</a>
            <?php else: ?>
                <div class="alert alert-success">All dependencies are met.</div>
                <a href="?step=1" class="btn btn-primary">Start Installation</a>
            <?php endif; ?>
        <?php elseif ($step === 1): ?>
            <form method="post">
                <div class="form-group">
                    <label>Database Host</label>
                    <input type="text" name="dbhost" class="form-control" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>Database Name</label>
                    <input type="text" name="dbname" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Database User</label>
                    <input type="text" name="dbuser" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Database Password</label>
                    <input type="password" name="dbpass" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Next</button>
            </form>
        <?php elseif ($step === 2): ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <form method="post">
                <p>Click Next to create tables and write config.</p>
                <button type="submit" class="btn btn-primary btn-block">Next</button>
            </form>
        <?php elseif ($step === 3): ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label>Super Admin Name</label>
                    <input type="text" name="admin_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Super Admin Email</label>
                    <input type="email" name="admin_email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="admin_pass" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Repeat Password</label>
                    <input type="password" name="admin_pass2" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Finish Installation</button>
            </form>
        <?php elseif ($step === 4): ?>
            <div class="alert alert-success">
                Installation complete! <a href="/public/index.php">Go to Application</a>
                <hr>
                <strong>Important:</strong> For security, please delete <code>public/install.php</code> and the <code>install/</code> directory:<br>
                <code>rm public/install.php</code><br>
                <code>rm -rf install</code>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
