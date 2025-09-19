<?php
// User management page for super admin and admin
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';
session_start();
require_login();

$user = $_SESSION['user'];
if ($user['role'] !== 'admin' && $user['role'] !== 'superadmin') {
    echo '<div class="alert alert-danger">Access denied.</div>';
    exit;
}

// Handle role or display label change
if (isset($_POST['user_id']) && (($user['role'] === 'superadmin') || (isset($_POST['new_role']) && $_POST['new_role'] !== 'superadmin'))) {
    $uid = (int)$_POST['user_id'];
    $new_role = isset($_POST['new_role']) ? $_POST['new_role'] : null;
    $new_label = isset($_POST['display_label']) ? trim($_POST['display_label']) : null;
    $set = [];
    $params = [];
    $action = [];
    if ($new_role !== null) {
        if ($user['role'] !== 'superadmin' && $new_role === 'superadmin') {
            echo '<div class="alert alert-danger">Only superadmin can assign superadmin role.</div>';
        } else {
            $set[] = 'role = ?';
            $params[] = $new_role;
            $action[] = "Changed user $uid role to $new_role";
        }
    }
    if ($new_label !== null) {
        $set[] = 'display_label = ?';
        $params[] = $new_label;
        $action[] = "Changed user $uid display label to $new_label";
    }
    if ($set) {
        $params[] = $uid;
        $pdo->prepare('UPDATE users SET '.implode(', ', $set).' WHERE id = ?')->execute($params);
        // Log action
        foreach ($action as $act) {
            $pdo->prepare('INSERT INTO grievance_actions (grievance_id, action_taken, marked_by) VALUES (?, ?, ?)')->execute([0, $act, $user['id']]);
        }
        echo '<div class="alert alert-success">User updated.</div>';
    }
}

// Handle admin removal (superadmin only)
if ($user['role'] === 'superadmin' && isset($_POST['remove_admin_id'])) {
    $uid = (int)$_POST['remove_admin_id'];
    $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute(['staff', $uid]);
    $pdo->prepare('INSERT INTO grievance_actions (grievance_id, action_taken, marked_by) VALUES (?, ?, ?)')->execute([0, "Removed admin rights from user $uid", $user['id']]);
    echo '<div class="alert alert-warning">Admin rights removed.</div>';
}

// Fetch all users
$users = $pdo->query('SELECT * FROM users ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$roles = ['superadmin', 'admin', 'staff', 'student', 'other'];
?>
<h2>User Management</h2>
<table class="table table-bordered">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Display Label</th><th>Change Role/Label</th><th>Remove Admin</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><?php echo htmlspecialchars($u['role']); ?></td>
            <td><?php echo htmlspecialchars($u['display_label']); ?></td>
            <td>
                <form method="post" class="form-inline">
                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                    <select name="new_role" class="form-control mr-2">
                        <?php foreach ($roles as $role):
                            if ($role === 'superadmin' && $user['role'] !== 'superadmin') continue;
                        ?>
                        <option value="<?php echo $role; ?>" <?php if ($u['role'] === $role) echo 'selected'; ?>><?php echo ucfirst($role); ?></option>
                        <?php
                        require_once __DIR__ . '/../includes/layout.php';
                        require_once __DIR__ . '/../src/db.php';
                        require_once __DIR__ . '/../src/functions.php';
                        session_start();
                        require_login();
                        $user = $_SESSION['user'];
                        if ($user['role'] !== 'admin' && $user['role'] !== 'superadmin') {
                            echo '<div class="alert alert-danger">Access denied.</div>';
                            exit;
                        }
                        $page_title = 'User Management';
                        ob_start();
                        // ...existing code...
                        ?>
                        <table class="table table-bordered">
                            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Display Label</th><th>Change Role/Label</th><th>Remove Admin</th></tr></thead>
                            <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['role']); ?></td>
                                    <td><?php echo htmlspecialchars($u['display_label']); ?></td>
                                    <td>
                                        <form method="post" class="form-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <select name="new_role" class="form-control mr-2">
                                                <?php foreach ($roles as $role):
                                                    if ($role === 'superadmin' && $user['role'] !== 'superadmin') continue;
                                                ?>
                                                <option value="<?php echo $role; ?>" <?php if ($u['role'] === $role) echo 'selected'; ?>><?php echo ucfirst($role); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="display_label" class="form-control mr-2" placeholder="Display Label" value="<?php echo htmlspecialchars($u['display_label']); ?>">
                                            <button type="submit" class="btn btn-info btn-sm">Update</button>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($user['role'] === 'superadmin' && $u['role'] === 'admin'): ?>
                                        <form method="post">
                                            <input type="hidden" name="remove_admin_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Remove Admin</button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                        <?php $content = ob_get_clean(); include __DIR__ . '/../includes/layout.php'; ?>
