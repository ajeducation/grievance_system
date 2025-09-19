<?php
// Admin panel page

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';

require_login();
if (!has_role('admin')) {
    echo '<div class="alert alert-danger">Access denied.</div>';
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

$tab = $_GET['tab'] ?? 'categories';



$page_title = 'Admin Panel';
ob_start();
?>
<div class="card p-4 shadow-sm mb-4">
    <h2 class="mb-3 font-weight-bold">Admin Panel</h2>
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link<?php echo ($tab==='categories'?' active':''); ?>" href="?page=admin&tab=categories">Manage Categories</a></li>
        <li class="nav-item"><a class="nav-link<?php echo ($tab==='assign'?' active':''); ?>" href="?page=admin&tab=assign">Assign Staff</a></li>
        <li class="nav-item"><a class="nav-link<?php echo ($tab==='analytics'?' active':''); ?>" href="?page=admin&tab=analytics">View Analytics</a></li>
        <li class="nav-item"><a class="nav-link<?php echo ($tab==='appeals'?' active':''); ?>" href="?page=admin&tab=appeals">Appeals Report</a></li>
        <?php if (isset($_SESSION["user"]) && $_SESSION["user"]["role"] === "superadmin"): ?>
        <li class="nav-item"><a class="nav-link" href="/src/pages/admin_openai.php">OpenAI API Key</a></li>
    <li class="nav-item"><a class="nav-link" href="/src/pages/admin_report_template.php">Report Template</a></li>
    <li class="nav-item"><a class="nav-link" href="/src/pages/admin_branding.php">Branding Settings</a></li>
        <?php endif; ?>
        <?php if (isset($_SESSION["user"]) && (($_SESSION["user"]["role"] ?? null) === "admin" || ($_SESSION["user"]["role"] ?? null) === "superadmin")): ?>
        <li class="nav-item"><a class="nav-link<?php echo ($tab==='users'?' active':''); ?>" href="?page=admin&tab=users">User Management</a></li>
        <?php endif; ?>
    </ul>
if ($tab === 'appeals') {
    // Appeals report and analytics
    $appeal_stats = $pdo->query("SELECT status, COUNT(*) AS total FROM grievance_appeals GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
    $total_appeals = array_sum(array_column($appeal_stats, 'total'));
    $appeal_statuses = ['pending','under review','accepted','rejected'];
    $appeal_counts = array_fill_keys($appeal_statuses, 0);
    foreach ($appeal_stats as $row) {
        $appeal_counts[$row['status']] = $row['total'];
    }
    // Average resolution time (accepted/rejected only)
    $avg_time = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) AS avg_hours FROM grievance_appeals WHERE status IN ('accepted','rejected') AND updated_at IS NOT NULL")->fetch(PDO::FETCH_ASSOC);
    ?>
    <h4>Appeals Report & Analytics</h4>
    <div class="row mb-3">
        <div class="col-md-3"><b>Total Appeals:</b> <?php echo $total_appeals ?: 0; ?></div>
        <div class="col-md-3"><b>Pending:</b> <?php echo $appeal_counts['pending']; ?></div>
        <div class="col-md-3"><b>Under Review:</b> <?php echo $appeal_counts['under review']; ?></div>
        <div class="col-md-3"><b>Accepted:</b> <?php echo $appeal_counts['accepted']; ?> | <b>Rejected:</b> <?php echo $appeal_counts['rejected']; ?></div>
    </div>
    <div class="mb-3"><b>Average Resolution Time:</b> <?php echo $avg_time && $avg_time['avg_hours'] ? round($avg_time['avg_hours'],1) . ' hours' : 'N/A'; ?></div>
    <table class="table table-bordered table-striped">
        <thead><tr><th>Grievance ID</th><th>Student</th><th>Status</th><th>Submitted</th><th>Updated</th><th>Comment</th></tr></thead>
        <tbody>
        <?php
        $appeals = $pdo->query("SELECT ga.*, u.name AS student_name FROM grievance_appeals ga JOIN users u ON ga.user_id = u.id ORDER BY ga.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($appeals as $a): ?>
            <tr>
                <td><?php echo $a['grievance_id']; ?></td>
                <td><?php echo htmlspecialchars($a['student_name']); ?></td>
                <td><?php echo htmlspecialchars($a['status']); ?></td>
                <td><?php echo htmlspecialchars($a['created_at']); ?></td>
                <td><?php echo htmlspecialchars($a['updated_at'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($a['comment'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    // End appeals tab block
    // No unmatched closing brace here
    ?>
    <?php if ($tab === 'users' && (isset($_SESSION["user"]) && ($_SESSION["user"]["role"] === "admin" || $_SESSION["user"]["role"] === "superadmin"))) {
        require_once __DIR__ . '/user_management.php';
    } ?>
</div>
<?php

if ($tab === 'categories') {
    // Handle add category
    if (isset($_POST['add_category']) && !empty($_POST['category_name'])) {
        $stmt = $pdo->prepare('INSERT INTO categories (name, reminder_days, escalation_days, allow_appeal, appeal_window_days) VALUES (?, ?, ?, ?, ?)');
        $reminder = isset($_POST['reminder_days']) ? (int)$_POST['reminder_days'] : 3;
        $escalation = isset($_POST['escalation_days']) ? (int)$_POST['escalation_days'] : 7;
        $allow_appeal = isset($_POST['allow_appeal']) ? 1 : 0;
        $appeal_window_days = isset($_POST['appeal_window_days']) ? (int)$_POST['appeal_window_days'] : 7;
        $stmt->execute([$_POST['category_name'], $reminder, $escalation, $allow_appeal, $appeal_window_days]);
        echo '<div class="alert alert-success">Category added.</div>';
    }
    // Handle update reminder/escalation days, allow_appeal, and appeal_window_days
    if (isset($_POST['update_category_id'])) {
        $cat_id = (int)$_POST['update_category_id'];
        $reminder = isset($_POST['reminder_days']) ? (int)$_POST['reminder_days'] : 3;
        $escalation = isset($_POST['escalation_days']) ? (int)$_POST['escalation_days'] : 7;
        $allow_appeal = isset($_POST['allow_appeal']) ? 1 : 0;
        $appeal_window_days = isset($_POST['appeal_window_days']) ? (int)$_POST['appeal_window_days'] : 7;
        $pdo->prepare('UPDATE categories SET reminder_days = ?, escalation_days = ?, allow_appeal = ?, appeal_window_days = ? WHERE id = ?')->execute([$reminder, $escalation, $allow_appeal, $appeal_window_days, $cat_id]);
        echo '<div class="alert alert-info">Category workflow settings updated.</div>';
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
    <form method="post" class="mb-3 row g-2 align-items-end">
        <div class="col-md-3">
            <label>Category Name</label>
            <input type="text" name="category_name" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label>Reminder Days (X)</label>
            <input type="number" name="reminder_days" class="form-control" min="1" value="3">
        </div>
        <div class="col-md-2">
            <label>Escalation Days (Y)</label>
            <input type="number" name="escalation_days" class="form-control" min="1" value="7">
        </div>
        <div class="col-md-2">
            <label>Appeal Window (days)</label>
            <input type="number" name="appeal_window_days" class="form-control" min="1" value="7" title="Number of days after completion within which appeals can be filed for this category.">
        </div>
        <div class="col-md-1">
            <label>Allow Appeal</label><br>
            <input type="checkbox" name="allow_appeal" value="1" checked>
        </div>
        <div class="col-md-2">
            <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
        </div>
    </form>
    <table class="table table-bordered">
        <thead><tr><th>Name</th><th>Reminder Days (X)</th><th>Escalation Days (Y)</th><th>Appeal Window (days)</th><th>Allow Appeal</th><th>Update</th><th>Delete</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                <form method="post" class="row g-2 align-items-center">
                    <td><input type="number" name="reminder_days" class="form-control" min="1" value="<?php echo (int)$cat['reminder_days']; ?>"></td>
                    <td><input type="number" name="escalation_days" class="form-control" min="1" value="<?php echo (int)$cat['escalation_days']; ?>"></td>
                    <td><input type="number" name="appeal_window_days" class="form-control" min="1" value="<?php echo (int)($cat['appeal_window_days'] ?? 7); ?>" title="Number of days after completion within which appeals can be filed for this category."></td>
                    <td><input type="checkbox" name="allow_appeal" value="1" <?php if ($cat['allow_appeal']) echo 'checked'; ?>></td>
                    <td>
                        <input type="hidden" name="update_category_id" value="<?php echo $cat['id']; ?>">
                        <button type="submit" class="btn btn-info btn-sm">Update</button>
                    </td>
                </form>
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
            // AI Analytics Suggestion Window
            if (in_array($user['role'], ['admin','manager','superadmin'])): ?>
            <div class="mb-3">
                <a href="/src/pages/report_generate.php" target="_blank" class="btn btn-outline-secondary me-2">Preview Report</a>
                <a href="/src/pages/report_generate.php?type=word" class="btn btn-outline-primary me-2">Download Word</a>
                <a href="/src/pages/report_generate.php?type=pdf" class="btn btn-outline-danger">Download PDF</a>
            </div>
        <div class="card mb-4">
            <div class="card-header bg-info text-white"><i class="bi bi-stars"></i> AI Analytics Suggestions</div>
            <div class="card-body">
                <form id="aiAnalyticsForm" class="mb-2">
                    <label for="aiPrompt">Ask a question or request insights:</label>
                    <div class="input-group">
                        <input type="text" id="aiPrompt" class="form-control" placeholder="e.g. What are the main grievance trends?" required>
                        <button type="submit" class="btn btn-primary">Get AI Suggestion</button>
                    </div>
                </form>
                <div id="aiAnalyticsResult" class="border rounded p-2 bg-light" style="min-height:40px;"></div>
            </div>
        </div>
        <script>
        document.getElementById('aiAnalyticsForm').onsubmit = async function(e) {
            e.preventDefault();
            var prompt = document.getElementById('aiPrompt').value;
            var resultDiv = document.getElementById('aiAnalyticsResult');
            resultDiv.innerHTML = '<span class="text-muted">Loading...</span>';
            const resp = await fetch('/src/pages/analytics_ai.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt })
            });
            const data = await resp.json();
            if (data.result) resultDiv.innerHTML = '<b>AI Suggestion:</b><br>' + data.result.replace(/\n/g,'<br>');
            else resultDiv.innerHTML = '<span class="text-danger">' + (data.error || 'Error') + '</span>';
        };
        </script>
        <?php endif; ?>
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
        <thead><tr><th>Grievance Title</th><th>Category</th><th>Assigned Staff</th><th>Status</th></tr></thead>
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


$content = ob_get_clean();
include __DIR__ . '/../includes/layout.php';
