<?php
// Grievance submission and tracking page

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../includes/email.php';
require_once __DIR__ . '/../includes/storage.php';

require_login();
$user = $_SESSION['user'];

// Handle grievance submission
if ($user['role'] === 'student' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['description'], $_POST['category_id'])) {
    $pdo->beginTransaction();
    try {
        // Find least-loaded staff for the selected category
        $cat_id = (int)$_POST['category_id'];
        $staff_stmt = $pdo->prepare('SELECT u.id, COUNT(g.id) AS open_count FROM users u LEFT JOIN grievances g ON u.id = g.assigned_to AND g.status = "ongoing" WHERE u.role = "staff" AND EXISTS (SELECT 1 FROM categories c WHERE c.id = ? ) GROUP BY u.id ORDER BY open_count ASC LIMIT 1');
        $staff_stmt->execute([$cat_id]);
        $staff = $staff_stmt->fetch(PDO::FETCH_ASSOC);
        $assigned_to = $staff ? $staff['id'] : null;
        $insert_sql = 'INSERT INTO grievances (user_id, category_id, title, description' . ($assigned_to ? ', assigned_to' : '') . ') VALUES (?, ?, ?, ?' . ($assigned_to ? ', ?' : '') . ')';
        $params = [$user['id'], $cat_id, $_POST['title'], $_POST['description']];
        if ($assigned_to) $params[] = $assigned_to;
        $stmt = $pdo->prepare($insert_sql);
        $stmt->execute($params);
        $grievance_id = $pdo->lastInsertId();
        // Handle file upload
        if (!empty($_FILES['attachment']['name'])) {
            $file = $_FILES['attachment'];
            $allowed = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($file['error'] === 0 && in_array($ext, $allowed) && $file['size'] <= 5*1024*1024) {
                $safeName = uniqid('att_').'.'.$ext;
                $fileUrl = save_uploaded_file($file, $safeName);
                if ($fileUrl) {
                    $stmt = $pdo->prepare('INSERT INTO grievance_attachments (grievance_id, file_name, file_path, uploaded_by) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$grievance_id, $file['name'], $safeName, $user['id']]);
                }
            }
        }
        $pdo->commit();
        echo '<div class="alert alert-success">Grievance submitted.' . ($assigned_to ? ' Assigned to staff.' : ' No staff available for auto-assignment.') . '</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        echo '<div class="alert alert-danger">Error submitting grievance.</div>';
    }
}

// Fetch categories for dropdown
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

// Filtering
$filter_id = $_GET['filter_id'] ?? '';
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
if ($filter_id) {
    $filter_sql .= ' AND g.id = ?';
    $filter_params[] = $filter_id;
}
if ($filter_category) {
    $filter_sql .= ' AND g.category_id = ?';
    $filter_params[] = $filter_category;
}
if ($filter_status) {
    $filter_sql .= ' AND g.status = ?';
    $filter_params[] = $filter_status;
}

$sql = 'SELECT g.*, c.name AS category_name, c.allow_appeal, u.name AS assigned_name FROM grievances g JOIN categories c ON g.category_id = c.id LEFT JOIN users u ON g.assigned_to = u.id WHERE ' . $filter_sql . ' ORDER BY g.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($filter_params);
$grievances = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Appeal logic: fetch appeal status for grievances
$appeals = [];
if ($grievance_ids) {
    $in = str_repeat('?,', count($grievance_ids)-1) . '?';
    $stmt = $pdo->prepare('SELECT grievance_id, status FROM grievance_appeals WHERE grievance_id IN (' . $in . ')');
    $stmt->execute($grievance_ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $appeals[$row['grievance_id']] = $row['status'];
    }
}

// Handle appeal submission (student)
if ($user['role'] === 'student' && isset($_POST['appeal_grievance_id'])) {
    $gid = (int)$_POST['appeal_grievance_id'];
    $appeal_comment = trim($_POST['appeal_comment'] ?? '');
    // Check if already appealed
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM grievance_appeals WHERE grievance_id = ? AND user_id = ?');
    $stmt->execute([$gid, $user['id']]);
    if ($stmt->fetchColumn()) {
        echo '<div class="alert alert-warning">Appeal already submitted for this grievance.</div>';
        exit;
    }
    // Fetch grievance info for window enforcement
    $stmt = $pdo->prepare('SELECT g.*, c.appeal_window_days AS category_appeal_window_days FROM grievances g JOIN categories c ON g.category_id = c.id WHERE g.id = ?');
    $stmt->execute([$gid]);
    $gr = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$gr) {
        echo '<div class="alert alert-danger">Invalid grievance.</div>';
        exit;
    }
    // Enforce allow_appeal
    $allow_appeal = $gr['allow_appeal'] !== null ? $gr['allow_appeal'] : $gr['category_allow_appeal'];
    if (!$allow_appeal) {
        echo '<div class="alert alert-danger">Appeal is not allowed for this grievance.</div>';
        exit;
    }
    // Enforce appeal window
    $appeal_window_days = $gr['appeal_window_days'] ?? $gr['category_appeal_window_days'] ?? 7;
    $completed_at = $gr['updated_at'] ?? $gr['created_at'];
    $now = new DateTime();
    $completed = new DateTime($completed_at);
    $interval = $completed->diff($now);
    $days_since_completed = (int)$interval->format('%a');
    if ($gr['status'] !== 'completed' || $days_since_completed > $appeal_window_days) {
        echo '<div class="alert alert-danger">Appeal window has expired. Appeals must be filed within '.(int)$appeal_window_days.' days of completion.</div>';
        exit;
    }
    // Validate comment length
    if (strlen($appeal_comment) > 1000) {
        echo '<div class="alert alert-danger">Appeal comment too long (max 1000 characters).</div>';
        exit;
    }
    // Validate file if present
    $file_ok = true;
    $file_error = '';
    if (!empty($_FILES['appeal_attachment']['name'])) {
        $file = $_FILES['appeal_attachment'];
        $allowed = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file['error'] !== 0) {
            $file_ok = false;
            $file_error = 'File upload error.';
        } elseif (!in_array($ext, $allowed)) {
            $file_ok = false;
            $file_error = 'Invalid file type.';
        } elseif ($file['size'] > 5*1024*1024) {
            $file_ok = false;
            $file_error = 'File too large (max 5MB).';
        }
    }
    if (!$file_ok) {
        echo '<div class="alert alert-danger">Appeal not submitted: ' . htmlspecialchars($file_error) . '</div>';
        exit;
    }
    // Insert appeal
    $stmt = $pdo->prepare('INSERT INTO grievance_appeals (grievance_id, user_id, status, created_at, comment) VALUES (?, ?, ?, NOW(), ?)');
    $stmt->execute([$gid, $user['id'], 'pending', $appeal_comment]);
    // Handle file upload for appeal
    if (!empty($_FILES['appeal_attachment']['name'])) {
        $file = $_FILES['appeal_attachment'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safeName = uniqid('appeal_').'.'.$ext;
        $dest = __DIR__ . '/../../public/uploads/appeals/' . $safeName;
        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0777, true);
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $appeal_id = $gid;
            $stmt = $pdo->prepare('INSERT INTO grievance_appeal_attachments (appeal_id, file_name, file_path, uploaded_by) VALUES (?, ?, ?, ?)');
            $stmt->execute([$appeal_id, $file['name'], $safeName, $user['id']]);
        }
    }
    // Audit log
    $stmt = $pdo->prepare('INSERT INTO grievance_appeal_audit (appeal_id, action, performed_by, details) VALUES (?, ?, ?, ?)');
    $stmt->execute([$gid, 'submit', $user['id'], $appeal_comment]);
    echo '<div class="alert alert-success">Appeal submitted for review.</div>';
}

// Handle manager/admin enabling/disabling appeal for a grievance
if (($user['role'] === 'manager' || $user['role'] === 'admin') && isset($_POST['toggle_appeal_grievance_id'])) {
    $gid = (int)$_POST['toggle_appeal_grievance_id'];
    $enable = isset($_POST['enable_appeal']) ? 1 : 0;
    $window = isset($_POST['appeal_window_days']) ? (int)$_POST['appeal_window_days'] : null;
    $stmt = $pdo->prepare('UPDATE grievances SET allow_appeal = ?, appeal_window_days = ? WHERE id = ?');
    $stmt->execute([$enable, $window, $gid]);
    echo '<div class="alert alert-info">Appeal option '.($enable?'enabled':'disabled').' for grievance. Appeal window set to '.($window ? $window.' days.' : 'default.').'</div>';
}

// Handle manager/admin updating appeal status (accept/reject/under review)
if (($user['role'] === 'manager' || $user['role'] === 'admin') && isset($_POST['update_appeal_status_id'], $_POST['appeal_status'])) {
    $appeal_id = (int)$_POST['update_appeal_status_id'];
    $new_status = $_POST['appeal_status'];
    if (in_array($new_status, ['pending','under review','accepted','rejected'])) {
        $stmt = $pdo->prepare('UPDATE grievance_appeals SET status = ?, updated_at = NOW() WHERE grievance_id = ?');
        $stmt->execute([$new_status, $appeal_id]);
        // Audit log
        $stmt = $pdo->prepare('INSERT INTO grievance_appeal_audit (appeal_id, action, performed_by, details) VALUES (?, ?, ?, ?)');
        $stmt->execute([$appeal_id, 'status_update', $user['id'], $new_status]);
        // Fetch student info for notification
        $stmt = $pdo->prepare('SELECT ga.*, u.email, u.phone, g.title FROM grievance_appeals ga JOIN grievances g ON ga.grievance_id = g.id JOIN users u ON ga.user_id = u.id WHERE ga.grievance_id = ?');
        $stmt->execute([$appeal_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $msg = "Your appeal for Grievance #{$appeal_id} ('{$row['title']}') status changed to '{$new_status}'.";
            // Send email
            send_email($row['email'], 'Appeal Status Update', $msg);
            // WhatsApp integration (if enabled)
            $wa_cfg_file = __DIR__ . '/../../config/whatsapp.php';
            if (file_exists($wa_cfg_file)) {
                $wa_cfg = include $wa_cfg_file;
                if (!empty($wa_cfg['enabled']) && !empty($row['phone'])) {
                    // Example: call WhatsApp API (pseudo-code)
                    // send_whatsapp($row['phone'], $msg, $wa_cfg);
                    file_put_contents(__DIR__ . '/../../whatsapp_log.txt', date('Y-m-d H:i:s') . " - To: {$row['phone']} - $msg\n", FILE_APPEND);
                }
            }
        }
        echo '<div class="alert alert-info">Appeal status updated and student notified.</div>';
    }
}

// Fetch attachments for all grievances
$grievance_ids = array_column($grievances, 'id');
$attachments = [];
if ($grievance_ids) {
    $in = str_repeat('?,', count($grievance_ids)-1) . '?';
    $stmt = $pdo->prepare('SELECT * FROM grievance_attachments WHERE grievance_id IN (' . $in . ')');
    $stmt->execute($grievance_ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $att) {
        $attachments[$att['grievance_id']][] = $att;
    }
}

// Handle status/action update (staff only)
if ($user['role'] === 'staff' && isset($_POST['mark_grievance_id'], $_POST['status'], $_POST['action_taken'])) {
    $gid = (int)$_POST['mark_grievance_id'];
    $status = $_POST['status'] === 'completed' ? 'completed' : 'ongoing';
    $action = $_POST['action_taken'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE grievances SET status = ?, updated_at = NOW() WHERE id = ? AND assigned_to = ?')->execute([$status, $gid, $user['id']]);
        $stmt = $pdo->prepare('INSERT INTO grievance_actions (grievance_id, action_taken, marked_by) VALUES (?, ?, ?)');
        $stmt->execute([$gid, $action, $user['id']]);
        $action_id = $pdo->lastInsertId();
        // Handle file upload for comment
        if (!empty($_FILES['action_attachment']['name'])) {
            $file = $_FILES['action_attachment'];
            $allowed = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($file['error'] === 0 && in_array($ext, $allowed) && $file['size'] <= 5*1024*1024) {
                $safeName = uniqid('att_').'.'.$ext;
                $fileUrl = save_uploaded_file($file, $safeName);
                if ($fileUrl) {
                    $stmt = $pdo->prepare('INSERT INTO grievance_attachments (grievance_id, comment_id, file_name, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$gid, $action_id, $file['name'], $safeName, $user['id']]);
                }
            }
        }
        // Notification logic (email, WhatsApp, respect user prefs)
        $ginfo = $pdo->prepare('SELECT g.*, c.name AS category_name, u.email AS user_email, u.name AS user_name, u.phone FROM grievances g JOIN categories c ON g.category_id = c.id JOIN users u ON g.user_id = u.id WHERE g.id = ?');
        $ginfo->execute([$gid]);
        $g = $ginfo->fetch(PDO::FETCH_ASSOC);
        if ($g) {
            // Check notification prefs for grievance, category, or all assigned
            $pref = $pdo->prepare('SELECT disabled FROM user_notification_prefs WHERE user_id = ? AND (grievance_id = ? OR category_id = ? OR (category_id IS NULL AND grievance_id IS NULL)) ORDER BY grievance_id DESC, category_id DESC LIMIT 1');
            $pref->execute([$g['user_id'], $gid, $g['category_id']]);
            $row = $pref->fetch(PDO::FETCH_ASSOC);
            if (!$row || !$row['disabled']) {
                $msg = "Notification: Grievance #{$g['id']} ('{$g['title']}') status changed to '{$status}'.";
                // Send email
                send_email($g['user_email'], 'Grievance Update', $msg);
                // WhatsApp integration (if enabled)
                $wa_cfg_file = __DIR__ . '/../../config/whatsapp.php';
                if (file_exists($wa_cfg_file)) {
                    $wa_cfg = include $wa_cfg_file;
                    if (!empty($wa_cfg['enabled']) && !empty($g['phone'])) {
                        // Example: call WhatsApp API (pseudo-code)
                        // send_whatsapp($g['phone'], $msg, $wa_cfg);
                        file_put_contents(__DIR__ . '/../../whatsapp_log.txt', date('Y-m-d H:i:s') . " - To: {$g['phone']} - $msg\n", FILE_APPEND);
                    }
                }
            }
        }
        $pdo->commit();
        echo '<div class="alert alert-info">Grievance updated.</div>';
    } catch (Exception $e) {
        $pdo->rollBack();
        echo '<div class="alert alert-danger">Error updating grievance.</div>';
    }
}

// Handle add comment
if (isset($_POST['add_comment'], $_POST['comment_content'], $_GET['grievance_id'])) {
    $grievance_id = (int)$_GET['grievance_id'];
    $content = trim($_POST['comment_content']);
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $attachment = null;
    if (!empty($_FILES['comment_attachment']['name'])) {
        $file = $_FILES['comment_attachment'];
        $allowed = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx','txt'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file['error'] === 0 && in_array($ext, $allowed) && $file['size'] <= 5*1024*1024) {
            $safeName = uniqid('cmt_').'.'.$ext;
            $dest = __DIR__ . '/../../public/uploads/comments/' . $safeName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $attachment = $safeName;
            }
        }
    }
    $stmt = $pdo->prepare('INSERT INTO comments (grievance_id, user_id, content, attachment, parent_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$grievance_id, $user['id'], $content, $attachment, $parent_id]);
    echo '<div class="alert alert-success">Comment posted.</div>';
}
// Fetch comments for a grievance (flat, can be threaded in UI)
$comments = [];
if (isset($_GET['grievance_id'])) {
    $grievance_id = (int)$_GET['grievance_id'];
    $stmt = $pdo->prepare('SELECT c.*, u.name AS user_display FROM comments c JOIN users u ON c.user_id = u.id WHERE c.grievance_id = ? ORDER BY c.created_at ASC');
    $stmt->execute([$grievance_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch grievance actions for timeline
$actions = [];
if (isset($_GET['grievance_id'])) {
    $grievance_id = (int)$_GET['grievance_id'];
    $stmt = $pdo->prepare('SELECT a.*, u.name AS user_name FROM grievance_actions a JOIN users u ON a.marked_by = u.id WHERE a.grievance_id = ? ORDER BY a.action_date ASC');
    $stmt->execute([$grievance_id]);
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper: build threaded comment tree
function buildCommentTree($comments) {
    $tree = [];
    $refs = [];
    foreach ($comments as &$c) {
        $c['children'] = [];
        $refs[$c['id']] = &$c;
    }
    foreach ($comments as &$c) {
        if ($c['parent_id']) {
            $refs[$c['parent_id']]['children'][] = &$c;
        } else {
            $tree[] = &$c;
        }
    }
    return $tree;
}
$commentTree = buildCommentTree($comments);

// Handle bulk actions (admin/manager)
if (($user['role'] === 'admin' || $user['role'] === 'manager') && isset($_POST['bulk_action'], $_POST['selected_grievances'])) {
    $ids = array_map('intval', $_POST['selected_grievances']);
    $in = str_repeat('?,', count($ids)-1) . '?';
    if ($_POST['bulk_action'] === 'assign' && !empty($_POST['assign_to'])) {
        $staff_id = (int)$_POST['assign_to'];
        $pdo->prepare("UPDATE grievances SET assigned_to = ? WHERE id IN ($in)")->execute(array_merge([$staff_id], $ids));
        echo '<div class="alert alert-success">Selected grievances assigned.</div>';
    } elseif ($_POST['bulk_action'] === 'complete') {
        $pdo->prepare("UPDATE grievances SET status = 'completed' WHERE id IN ($in)")->execute($ids);
        echo '<div class="alert alert-success">Selected grievances marked as completed.</div>';
    } elseif ($_POST['bulk_action'] === 'delete') {
        $pdo->prepare("DELETE FROM grievances WHERE id IN ($in)")->execute($ids);
        echo '<div class="alert alert-warning">Selected grievances deleted.</div>';
    } elseif ($_POST['bulk_action'] === 'category' && !empty($_POST['new_category'])) {
        $cat_id = (int)$_POST['new_category'];
        $pdo->prepare("UPDATE grievances SET category_id = ? WHERE id IN ($in)")->execute(array_merge([$cat_id], $ids));
        echo '<div class="alert alert-success">Selected grievances moved to new category.</div>';
    } elseif ($_POST['bulk_action'] === 'comment' && !empty($_POST['bulk_comment'])) {
        $comment = trim($_POST['bulk_comment']);
        foreach ($ids as $gid) {
            $stmt = $pdo->prepare('INSERT INTO comments (grievance_id, user_id, content) VALUES (?, ?, ?)');
            $stmt->execute([$gid, $user['id'], $comment]);
        }
        echo '<div class="alert alert-success">Comment added to selected grievances.</div>';
    }
}

// Fetch staff and categories for assignment/category dropdowns
$all_staff = [];
$all_categories = [];
if ($user['role'] === 'admin' || $user['role'] === 'manager') {
    $all_staff = $pdo->query("SELECT id, name FROM users WHERE role = 'staff' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $all_categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
}

?>
$page_title = 'Grievances';
ob_start();
?>
<h2 class="mb-4">Grievances</h2>
<?php if ($user['role'] === 'student'): ?>
<div class="card p-4 shadow-sm mb-4" style="max-width: 600px; margin:auto;">
<form method="post" enctype="multipart/form-data">
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
    <div class="form-group mb-2">
        <label for="attachment">Attachment (optional, max 5MB, jpg/png/pdf/doc/xls)</label>
        <input type="file" name="attachment" id="attachment" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary btn-block">Submit Grievance</button>
</form>
</div>
<?php endif; ?>

<form method="get" class="form-inline mb-2">
	<input type="hidden" name="page" value="grievance">
	<input type="text" name="filter_id" class="form-control mr-2" placeholder="Grievance ID" value="<?php echo htmlspecialchars($filter_id); ?>" style="max-width:120px;">
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
<form id="bulkActionForm" method="post" class="mb-2">
<?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?>
    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
        <select name="bulk_action" class="form-select form-select-sm w-auto" required>
            <option value="">Bulk Action</option>
            <option value="assign">Assign to Staff</option>
            <option value="complete">Mark as Completed</option>
            <option value="delete">Delete</option>
            <option value="category">Change Category</option>
            <option value="comment">Add Comment/Note</option>
        </select>
        <select name="assign_to" class="form-select form-select-sm w-auto" style="display:none;">
            <option value="">Select Staff</option>
            <?php foreach ($all_staff as $s): ?>
                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="new_category" class="form-select form-select-sm w-auto" style="display:none;">
            <option value="">Select Category</option>
            <?php foreach ($all_categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="bulk_comment" class="form-control form-control-sm w-auto" placeholder="Comment/Note" style="display:none;max-width:200px;">
        <button type="submit" class="btn btn-sm btn-primary">Apply</button>
        <button type="button" class="btn btn-sm btn-outline-success ms-2" id="exportCsvBtn"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</button>
        <button type="button" class="btn btn-sm btn-outline-secondary ms-1" id="downloadZipBtn"><i class="bi bi-file-zip"></i> Download Attachments</button>
        <a href="/src/pages/naac_report.php" class="btn btn-sm btn-outline-warning ms-1" target="_blank" title="Download NAAC-compliant grievance report for accreditation."><i class="bi bi-file-earmark-excel"></i> NAAC Report</a>
    </div>
<?php endif; ?>
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?><th><input type="checkbox" id="selectAll"></th><?php endif; ?>
            <th>ID</th>
            <th>Title</th>
            <th>Category</th>
            <th>Status</th>
            <th>Assigned To</th>
            <th>Created</th>
            <th>Updated</th>
            <th>Attachment(s)</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($grievances as $g): ?>
        <tr>
            <?php if ($user['role'] === 'admin' || $user['role'] === 'manager'): ?><td><input type="checkbox" name="selected_grievances[]" value="<?php echo $g['id']; ?>"></td><?php endif; ?>
            <td><span class="badge bg-info text-dark"><?php echo $g['id']; ?></span></td>
            <td><?php echo htmlspecialchars($g['title']); ?></td>
            <td><?php echo htmlspecialchars($g['category_name']); ?></td>
            <td><?php echo htmlspecialchars($g['status']); ?></td>
            <td><?php echo htmlspecialchars($g['assigned_name'] ?? 'Unassigned'); ?></td>
            <td><?php echo htmlspecialchars($g['created_at']); ?></td>
            <td><?php echo htmlspecialchars($g['updated_at']); ?></td>
            <td>
                <?php if (!empty($attachments[$g['id']])): ?>
                    <?php foreach ($attachments[$g['id']] as $att): ?>
                        <a href="/uploads/<?php echo urlencode($att['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary mb-1"><i class="bi bi-paperclip"></i> <?php echo htmlspecialchars($att['file_name']); ?></a><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
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
                <?php elseif ($user['role'] === 'student'): ?>
                    <?php
                    // Show appeal status/history for this grievance
                    $appeal_info = $pdo->prepare('SELECT * FROM grievance_appeals WHERE grievance_id = ? AND user_id = ?');
                    $appeal_info->execute([$g['id'], $user['id']]);
                    $appeal = $appeal_info->fetch(PDO::FETCH_ASSOC);
                    if ($g['status'] === 'completed' && $g['allow_appeal'] && !$appeal): ?>
                        <form method="post" class="form-inline" enctype="multipart/form-data">
                            <input type="hidden" name="appeal_grievance_id" value="<?php echo $g['id']; ?>">
                            <div class="mb-1">
                                <input type="text" name="appeal_comment" class="form-control form-control-sm" placeholder="Reason for appeal (optional)" title="Explain why you are not satisfied with the resolution. This will help reviewers understand your case.">
                            </div>
                            <div class="mb-1">
                                <input type="file" name="appeal_attachment" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" title="Attach supporting documents (max 5MB, jpg/png/pdf/doc/xls)">
                            </div>
                            <button type="submit" class="btn btn-sm btn-warning" title="Submit your appeal for review. You can only appeal once per grievance.">Appeal</button>
                            <span class="ms-2 text-muted small" data-bs-toggle="tooltip" title="You can appeal if you are not satisfied with the resolution. Appeals are reviewed by a manager or admin. You may attach supporting documents.">
                                <i class="bi bi-info-circle"></i> Need help? Hover for info.
                            </span>
                        </form>
                    <?php elseif ($appeal): ?>
                        <div>
                            <span class="badge bg-secondary" title="Current status of your appeal.">Appeal: <?php echo htmlspecialchars($appeal['status']); ?></span><br>
                            <span class="text-muted small" title="Date you submitted the appeal.">Submitted: <?php echo htmlspecialchars($appeal['created_at']); ?></span><br>
                            <?php if (!empty($appeal['updated_at'])): ?>
                                <span class="text-muted small" title="Date your appeal status was last updated.">Updated: <?php echo htmlspecialchars($appeal['updated_at']); ?></span><br>
                            <?php endif; ?>
                            <?php if (!empty($appeal['comment'])): ?>
                                <span class="small" title="Your reason for appeal.">Comment: <?php echo htmlspecialchars($appeal['comment']); ?></span><br>
                            <?php endif; ?>
                            <?php
                            // Show appeal attachments if any
                            $att_stmt = $pdo->prepare('SELECT * FROM grievance_appeal_attachments WHERE appeal_id = ?');
                            $att_stmt->execute([$g['id']]);
                            $appeal_attachments = $att_stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($appeal_attachments as $att): ?>
                                <a href="/uploads/appeals/<?php echo urlencode($att['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-1" title="Download supporting document."><i class="bi bi-paperclip"></i> <?php echo htmlspecialchars($att['file_name']); ?></a><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php elseif (($user['role'] === 'manager' || $user['role'] === 'admin')): ?>
                <form method="post" class="form-inline d-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="toggle_appeal_grievance_id" value="<?php echo $g['id']; ?>">
                    <input type="checkbox" name="enable_appeal" value="1" <?php if ($g['allow_appeal']) echo 'checked'; ?>> Allow Appeal
                    <input type="number" name="appeal_window_days" class="form-control form-control-sm ms-2" min="1" style="width:110px;" value="<?php echo (int)($g['appeal_window_days'] ?? $g['category_appeal_window_days'] ?? 7); ?>" title="Custom appeal window (days) for this grievance. Leave blank for category default.">
                    <button type="submit" class="btn btn-sm btn-outline-info ms-1">Update</button>
                    <span class="small text-muted ms-2" title="If set, this overrides the category's appeal window for this grievance.">(Custom window)</span>
                </form>
                <?php else: ?>
                -
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</form>

<!-- Grievance Timeline -->
<?php if (isset($_GET['grievance_id'])): ?>
<div class="card mb-4">
  <div class="card-header"><i class="bi bi-clock-history"></i> Grievance Timeline</div>
  <div class="card-body">
    <ul class="timeline list-unstyled">
      <?php foreach ($actions as $act): ?>
        <li class="mb-4 position-relative ps-4">
          <span class="position-absolute top-0 start-0 translate-middle bg-primary rounded-circle" style="width:12px;height:12px;"></span>
          <div><span class="fw-bold"><?php echo htmlspecialchars($act['user_name']); ?></span> <span class="text-muted small ms-2"><?php echo htmlspecialchars($act['action_date']); ?></span></div>
          <div class="ps-2 text-secondary"><?php echo htmlspecialchars($act['action_taken']); ?></div>
        </li>
      <?php endforeach; ?>
      <?php if (!$actions): ?>
        <li class="text-muted">No actions yet.</li>
      <?php endif; ?>
    </ul>
  </div>
</div>
<?php endif; ?>

<!-- Comments Section -->
<?php if (isset($_GET['grievance_id'])): ?>
<div class="card mt-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-chat-dots"></i> Comments & Discussion</span>
  </div>
  <div class="card-body">
    <?php
    function renderComments($comments, $level = 0) {
        foreach ($comments as $comment): ?>
        <div class="mb-3 ms-<?php echo $level * 4; ?>">
          <div class="d-flex align-items-center mb-1">
            <span class="fw-bold"><?php echo htmlspecialchars($comment['user_display']); ?></span>
            <span class="text-muted ms-2 small"><?php echo htmlspecialchars($comment['created_at']); ?></span>
          </div>
          <div class="ps-3">
            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
            <?php if ($comment['attachment']): ?>
              <div class="mt-2">
                <a href="/uploads/comments/<?php echo urlencode($comment['attachment']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-paperclip"></i> View Attachment
                </a>
              </div>
            <?php endif; ?>
            <button class="btn btn-link btn-sm p-0 mt-1 reply-btn" data-comment-id="<?php echo $comment['id']; ?>"><i class="bi bi-reply"></i> Reply</button>
            <div class="reply-form mt-2" id="reply-form-<?php echo $comment['id']; ?>" style="display:none;">
              <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                <div class="mb-2">
                  <textarea name="comment_content" class="form-control" rows="2" placeholder="Reply..." required></textarea>
                </div>
                <div class="mb-2">
                  <input type="file" name="comment_attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt" class="form-control form-control-sm">
                </div>
                <button type="submit" name="add_comment" class="btn btn-primary btn-sm">
                  <i class="bi bi-send"></i> Post Reply
                </button>
              </form>
            </div>
          </div>
          <?php if (!empty($comment['children'])) renderComments($comment['children'], $level+1); ?>
        </div>
    <?php endforeach; }
    renderComments($commentTree);
    ?>
    <!-- Add Comment Form (top-level) -->
    <form method="post" enctype="multipart/form-data" class="mt-4">
      <div class="mb-2">
        <textarea name="comment_content" class="form-control" rows="2" placeholder="Add a comment..." required></textarea>
      </div>
      <div class="mb-2">
        <input type="file" name="comment_attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt" class="form-control form-control-sm">
      </div>
      <button type="submit" name="add_comment" class="btn btn-primary btn-sm">
        <i class="bi bi-send"></i> Post Comment
      </button>
    </form>
  </div>
</div>
<script>
  document.querySelectorAll('.reply-btn').forEach(function(btn) {
    btn.onclick = function() {
      var id = btn.getAttribute('data-comment-id');
      var form = document.getElementById('reply-form-' + id);
      if (form.style.display === 'none') form.style.display = 'block';
      else form.style.display = 'none';
    };
  });
</script>
<?php endif; ?>

<style>
.timeline li { border-left: 2px solid #dee2e6; min-height: 40px; }
.timeline li:last-child { border-left: none; }
.timeline span.bg-primary { left: -7px; top: 8px; }
</style>

<?php $content = ob_get_clean(); include __DIR__ . '/../includes/layout.php'; ?>

<script>
document.getElementById('exportCsvBtn')?.addEventListener('click', function() {
    var ids = getSelectedIds();
    if (!ids.length) return alert('Select grievances to export.');
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/src/pages/grievance_export.php';
    form.target = '_blank';
    ids.forEach(id => {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_grievances[]';
        input.value = id;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    form.remove();
});
document.getElementById('downloadZipBtn')?.addEventListener('click', function() {
    var ids = getSelectedIds();
    if (!ids.length) return alert('Select grievances to download attachments.');
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '/src/pages/grievance_zip.php';
    form.target = '_blank';
    ids.forEach(id => {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_grievances[]';
        input.value = id;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    form.remove();
});
</script>
