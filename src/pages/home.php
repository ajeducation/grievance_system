<?php
// Home page
?>
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';
session_start();
require_login();
$user = $_SESSION['user'];
$page_title = 'Home';
ob_start();
?>
<div class="card shadow-sm animate__animated animate__fadeInUp">
	<div class="card-body">
		<h2 class="card-title">Welcome to the Grievance System</h2>
		<p class="card-text">Use the left panel to navigate through the system features.</p>
	</div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../includes/layout.php'; ?>
<?php
