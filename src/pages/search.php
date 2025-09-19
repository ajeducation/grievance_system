<?php
// Global search page for grievances, users, categories, and grievance ID
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';
require_login();
$user = $_SESSION['user'];
$q = trim($_GET['q'] ?? '');
$results = [];
if ($q !== '') {
    // Search by grievance ID (exact match if numeric)
    if (ctype_digit($q)) {
        $stmt = $pdo->prepare('SELECT g.*, c.name AS category_name, u.name AS assigned_name FROM grievances g JOIN categories c ON g.category_id = c.id LEFT JOIN users u ON g.assigned_to = u.id WHERE g.id = ?');
        $stmt->execute([$q]);
        $results['grievances'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Search grievances by title/description
        $stmt = $pdo->prepare('SELECT g.*, c.name AS category_name, u.name AS assigned_name FROM grievances g JOIN categories c ON g.category_id = c.id LEFT JOIN users u ON g.assigned_to = u.id WHERE g.title LIKE ? OR g.description LIKE ?');
        $stmt->execute(["%$q%", "%$q%"]);
        $results['grievances'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Search users by name/email
    $stmt = $pdo->prepare('SELECT * FROM users WHERE name LIKE ? OR email LIKE ?');
    $stmt->execute(["%$q%", "%$q%"]);
    $results['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Search categories by name
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE name LIKE ?');
    $stmt->execute(["%$q%"]);
    $results['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$page_title = 'Search Results';
ob_start();
?>
<h2>Search Results for "<?php echo htmlspecialchars($q); ?>"</h2>
<?php if ($q === ''): ?>
    <div class="alert alert-info">Enter a search term above.</div>
<?php else: ?>
    <?php if (!empty($results['grievances'])): ?>
        <h4>Grievances</h4>
        <table class="table table-bordered table-sm">
            <thead><tr><th>ID</th><th>Title</th><th>Category</th><th>Status</th><th>Assigned To</th><th>Created</th></tr></thead>
            <tbody>
            <?php foreach ($results['grievances'] as $g): ?>
                <tr>
                    <td><span class="badge bg-info text-dark"><?php echo $g['id']; ?></span></td>
                    <td><?php echo htmlspecialchars($g['title']); ?></td>
                    <td><?php echo htmlspecialchars($g['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($g['status']); ?></td>
                    <td><?php echo htmlspecialchars($g['assigned_name'] ?? 'Unassigned'); ?></td>
                    <td><?php echo htmlspecialchars($g['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php if (!empty($results['users'])): ?>
        <h4>Users</h4>
        <table class="table table-bordered table-sm">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead>
            <tbody>
            <?php foreach ($results['users'] as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars($u['role']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php if (!empty($results['categories'])): ?>
        <h4>Categories</h4>
        <ul>
            <?php foreach ($results['categories'] as $c): ?>
                <li><?php echo htmlspecialchars($c['name']); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if (empty($results['grievances']) && empty($results['users']) && empty($results['categories'])): ?>
        <div class="alert alert-warning">No results found.</div>
    <?php endif; ?>
<?php endif; ?>
<?php $content = ob_get_clean(); include __DIR__ . '/../includes/layout.php'; ?>
