<?php
// Dashboard page (role-based)
require_once __DIR__ . '/../templates/header.php';
if (!isset($_SESSION['user'])) {
        header('Location: /?page=login');
        exit;
}
$user = $_SESSION['user'];
?>
<div class="card p-5 shadow-sm mx-auto" style="max-width: 600px;">
    <h2 class="mb-3 font-weight-bold">Dashboard</h2>
    <p class="lead">Welcome, <span class="font-weight-bold"><?php echo htmlspecialchars($user['name']); ?></span> (<?php echo htmlspecialchars($user['role']); ?>)</p>
    <ul class="list-group list-group-flush mb-3">
        <li class="list-group-item"><a href="?page=grievance" class="btn btn-outline-primary btn-block">Submit/View Grievances</a></li>
        <?php if ($user['role'] === 'admin'): ?>
        <li class="list-group-item"><a href="?page=admin" class="btn btn-outline-info btn-block">Admin Panel</a></li>
        <?php endif; ?>
        <li class="list-group-item"><a href="?page=logout" class="btn btn-outline-secondary btn-block">Logout</a></li>
    </ul>
</div>
<?php
require_once __DIR__ . '/../templates/footer.php';
