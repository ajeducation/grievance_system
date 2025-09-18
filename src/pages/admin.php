
<?php
// Admin panel page
require_once __DIR__ . '/../templates/header.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

require_login();
if (!has_role('admin')) {
    echo '<div class="alert alert-danger">Access denied.</div>';
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

$tab = $_GET['tab'] ?? 'categories';


echo '<div class="card p-4 shadow-sm mb-4">';
echo '<h2 class="mb-3 font-weight-bold">Admin Panel</h2>';
echo '<ul class="nav nav-tabs mb-3">';
echo '<li class="nav-item"><a class="nav-link'.($tab==='categories'?' active':'').'" href="?page=admin&tab=categories">Manage Categories</a></li>';
echo '<li class="nav-item"><a class="nav-link'.($tab==='assign'?' active':'').'" href="?page=admin&tab=assign">Assign Staff</a></li>';
echo '<li class="nav-item"><a class="nav-link'.($tab==='analytics'?' active':'').'" href="?page=admin&tab=analytics">View Analytics</a></li>';
echo '</ul>';
echo '</div>';

if ($tab === 'categories') {
    // Handle add category
    if (isset($_POST['add_category']) && !empty($_POST['category_name'])) {
        $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
        $stmt->execute([$_POST['category_name']]);
        echo '<div class="alert alert-success">Category added.</div>';
    }
    // Handle delete category
    if (isset($_POST['delete_category_id'])) {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$_POST['delete_category_id']]);
        echo '<div class="alert alert-warning">Category deleted.</div>';
    }
    // List categories
    $categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <h4>Categories</h4>
    <form method="post" class="form-inline mb-3">
        <input type="text" name="category_name" class="form-control mr-2" placeholder="New category" required>
        <button type="submit" name="add_category" class="btn btn-primary">Add</button>
    </form>
    <table class="table table-bordered">
        <thead><tr><th>Name</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="delete_category_id" value="<?php echo $cat['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this category?');">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

if ($tab === 'assign') {
if ($tab === 'analytics') {
    // Category-wise completed grievances
    $cat_stats = $pdo->query("SELECT c.name, COUNT(g.id) AS total FROM categories c LEFT JOIN grievances g ON g.category_id = c.id AND g.status = 'completed' GROUP BY c.id")->fetchAll(PDO::FETCH_ASSOC);
    $cat_total = array_sum(array_column($cat_stats, 'total')) ?: 1;
    // Person-wise completed grievances
    $person_stats = $pdo->query("SELECT u.name, COUNT(g.id) AS total FROM users u LEFT JOIN grievances g ON g.assigned_to = u.id AND g.status = 'completed' WHERE u.role = 'staff' GROUP BY u.id")->fetchAll(PDO::FETCH_ASSOC);
    $person_total = array_sum(array_column($person_stats, 'total')) ?: 1;
    ?>
    <h4>Category-wise Completed Grievances</h4>
    <table class="table table-bordered">
        <thead><tr><th>Category</th><th>Completed</th><th>Percentage</th></tr></thead>
        <tbody>
        <?php foreach ($cat_stats as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo $row['total']; ?></td>
                <td><?php echo round($row['total']*100/$cat_total, 1); ?>%</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <canvas id="catChart" height="100"></canvas>
    <hr>
    <h4>Person-wise Completed Grievances</h4>
    <table class="table table-bordered">
        <thead><tr><th>Staff</th><th>Completed</th><th>Percentage</th></tr></thead>
        <tbody>
        <?php foreach ($person_stats as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo $row['total']; ?></td>
                <td><?php echo round($row['total']*100/$person_total, 1); ?>%</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <canvas id="personChart" height="100"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
    <script>
    var ctx1 = document.getElementById('catChart').getContext('2d');
    var catChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($cat_stats, 'name')); ?>,
            datasets: [{
                label: 'Completed',
                data: <?php echo json_encode(array_column($cat_stats, 'total')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)'
            }]
        },
        options: {responsive: true, legend: {display: false}}
    });
    var ctx2 = document.getElementById('personChart').getContext('2d');
    var personChart = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($person_stats, 'name')); ?>,
            datasets: [{
                label: 'Completed',
                data: <?php echo json_encode(array_column($person_stats, 'total')); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.6)'
            }]
        },
        options: {responsive: true, legend: {display: false}}
    });
    </script>
    <?php
}
    // Assign staff to categories
    // Fetch staff and categories
    $staff = $pdo->query("SELECT * FROM users WHERE role = 'staff' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    // Handle assignment
    if (isset($_POST['assign_staff_id'], $_POST['assign_category_id'])) {
        $cat_id = (int)$_POST['assign_category_id'];
        $staff_id = (int)$_POST['assign_staff_id'];
        // Assign all grievances in this category that are unassigned
        $pdo->prepare('UPDATE grievances SET assigned_to = ? WHERE category_id = ? AND (assigned_to IS NULL OR assigned_to = 0)')->execute([$staff_id, $cat_id]);
        echo '<div class="alert alert-success">Staff assigned to grievances in category.</div>';
    }
    ?>
    <h4>Assign Staff to Category</h4>
    <form method="post" class="form-inline mb-3">
        <select name="assign_staff_id" class="form-control mr-2" required>
            <option value="">Select Staff</option>
            <?php foreach ($staff as $s): ?>
                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="assign_category_id" class="form-control mr-2" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Assign</button>
    </form>
    <?php
    // Show current assignments (grievances assigned to staff by category)
    $assignments = $pdo->query('SELECT g.id, g.title, c.name AS category, u.name AS staff, g.status FROM grievances g JOIN categories c ON g.category_id = c.id LEFT JOIN users u ON g.assigned_to = u.id ORDER BY c.name, u.name')->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <table class="table table-bordered">
        <thead><tr><th>Grievance</th><th>Category</th><th>Assigned Staff</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($assignments as $a): ?>
            <tr>
                <td><?php echo htmlspecialchars($a['title']); ?></td>
                <td><?php echo htmlspecialchars($a['category']); ?></td>
                <td><?php echo htmlspecialchars($a['staff'] ?? 'Unassigned'); ?></td>
                <td><?php echo htmlspecialchars($a['status']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

require_once __DIR__ . '/../templates/footer.php';
