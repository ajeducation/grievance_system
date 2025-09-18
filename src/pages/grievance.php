
<?php
// Grievance submission and tracking page
require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

require_login();
$user = $_SESSION['user'];

// Handle grievance submission
if ($user['role'] === 'student' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['description'], $_POST['category_id'])) {
	$stmt = $pdo->prepare('INSERT INTO grievances (user_id, category_id, title, description) VALUES (?, ?, ?, ?)');
	$stmt->execute([$user['id'], $_POST['category_id'], $_POST['title'], $_POST['description']]);
	echo '<div class="alert alert-success">Grievance submitted.</div>';
}

// Fetch categories for dropdown
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

// Filtering
$filter_category = $_GET['filter_category'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$filter_sql = '';
$filter_params = [];
if ($user['role'] === 'student') {
	$filter_sql = 'g.user_id = ?';
	$filter_params[] = $user['id'];
} elseif ($user['role'] === 'staff') {
	$filter_sql = 'g.assigned_to = ?';
	$filter_params[] = $user['id'];
} else {
	$filter_sql = '1=1';
}
if ($filter_category) {
	$filter_sql .= ' AND g.category_id = ?';
	$filter_params[] = $filter_category;
}
if ($filter_status) {
	$filter_sql .= ' AND g.status = ?';
	$filter_params[] = $filter_status;
}
$sql = 'SELECT g.*, c.name AS category_name, u.name AS assigned_name FROM grievances g JOIN categories c ON g.category_id = c.id LEFT JOIN users u ON g.assigned_to = u.id WHERE ' . $filter_sql . ' ORDER BY g.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($filter_params);
$grievances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status/action update (staff only)
if ($user['role'] === 'staff' && isset($_POST['mark_grievance_id'], $_POST['status'], $_POST['action_taken'])) {
	$gid = (int)$_POST['mark_grievance_id'];
	$status = $_POST['status'] === 'completed' ? 'completed' : 'ongoing';
	$action = $_POST['action_taken'];
	$pdo->prepare('UPDATE grievances SET status = ?, updated_at = NOW() WHERE id = ? AND assigned_to = ?')->execute([$status, $gid, $user['id']]);
	$pdo->prepare('INSERT INTO grievance_actions (grievance_id, action_taken, marked_by) VALUES (?, ?, ?)')->execute([$gid, $action, $user['id']]);
	echo '<div class="alert alert-info">Grievance updated.</div>';
}

?>
<h2>Grievances</h2>

<?php if ($user['role'] === 'student'): ?>
<div class="card p-4 shadow-sm mb-4" style="max-width: 600px; margin:auto;">
<form method="post">
	<div class="form-group">
		<label for="title">Title</label>
		<input type="text" name="title" id="title" class="form-control" required>
	</div>
	<div class="form-group">
		<label for="description">Description</label>
		<textarea name="description" id="description" class="form-control" required></textarea>
	</div>
	<div class="form-group">
		<label for="category_id">Category</label>
		<select name="category_id" id="category_id" class="form-control" required>
			<option value="">Select Category</option>
			<?php foreach ($categories as $cat): ?>
				<option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<button type="submit" class="btn btn-primary btn-block">Submit Grievance</button>
</form>
</div>
<?php endif; ?>

<form method="get" class="form-inline mb-2">
	<input type="hidden" name="page" value="grievance">
	<select name="filter_category" class="form-control mr-2">
		<option value="">All Categories</option>
		<?php foreach ($categories as $cat): ?>
			<option value="<?php echo $cat['id']; ?>" <?php if ($filter_category == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
		<?php endforeach; ?>
	</select>
	<select name="filter_status" class="form-control mr-2">
		<option value="">All Statuses</option>
		<option value="ongoing" <?php if ($filter_status === 'ongoing') echo 'selected'; ?>>Ongoing</option>
		<option value="completed" <?php if ($filter_status === 'completed') echo 'selected'; ?>>Completed</option>
	</select>
	<button type="submit" class="btn btn-secondary">Filter</button>
</form>
<table class="table table-bordered table-striped">
	<thead>
		<tr>
			<th>Title</th>
			<th>Category</th>
			<th>Status</th>
			<th>Assigned To</th>
			<th>Created</th>
			<th>Updated</th>
			<th>Actions</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($grievances as $g): ?>
		<tr>
			<td><?php echo htmlspecialchars($g['title']); ?></td>
			<td><?php echo htmlspecialchars($g['category_name']); ?></td>
			<td><?php echo htmlspecialchars($g['status']); ?></td>
			<td><?php echo htmlspecialchars($g['assigned_name'] ?? 'Unassigned'); ?></td>
			<td><?php echo htmlspecialchars($g['created_at']); ?></td>
			<td><?php echo htmlspecialchars($g['updated_at']); ?></td>
			<td>
				<?php if ($user['role'] === 'staff' && $g['assigned_to'] == $user['id']): ?>
				<form method="post" class="form-inline">
					<input type="hidden" name="mark_grievance_id" value="<?php echo $g['id']; ?>">
					<select name="status" class="form-control mr-2">
						<option value="ongoing" <?php if ($g['status'] === 'ongoing') echo 'selected'; ?>>Ongoing</option>
						<option value="completed" <?php if ($g['status'] === 'completed') echo 'selected'; ?>>Completed</option>
					</select>
					<input type="text" name="action_taken" class="form-control mr-2" placeholder="Action taken" required>
					<button type="submit" class="btn btn-sm btn-info">Update</button>
				</form>
				<?php else: ?>
				-
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<p><a href="?page=dashboard">Back to Dashboard</a></p>
<?php
require_once __DIR__ . '/../templates/footer.php';
