<?php
// Login page (Microsoft SSO placeholder)
require_once __DIR__ . '/../templates/header.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['local_email'], $_POST['local_pass'])) {
		require_once __DIR__ . '/../db.php';
		$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ?');
		$stmt->execute([$_POST['local_email'], 'admin']);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($user && isset($user['password']) && password_verify($_POST['local_pass'], $user['password'])) {
				$_SESSION['user'] = $user;
				header('Location: /?page=dashboard');
				exit;
		} else {
				echo '<div class="alert alert-danger">Invalid credentials.</div>';
		}
}
?>
<div class="card p-5 shadow-sm mx-auto" style="max-width: 400px;">
	<h2 class="mb-3 text-center font-weight-bold">Login</h2>
	<p class="text-center mb-4"><a href="/sso/microsoft.php" class="btn btn-primary btn-lg">Login with Microsoft Account</a></p>
	<hr>
	<h4 class="mb-3 text-center">Super Admin Login</h4>
	<form method="post">
		<div class="form-group">
			<label>Email</label>
			<input type="email" name="local_email" class="form-control" required>
		</div>
		<div class="form-group">
			<label>Password</label>
			<input type="password" name="local_pass" class="form-control" required>
		</div>
		<button type="submit" class="btn btn-info btn-block">Login as Super Admin</button>
	</form>
</div>
<?php
require_once __DIR__ . '/../templates/footer.php';
