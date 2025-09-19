<?php
// Bootstrap 5 layout with left panel navigation for all pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Grievance System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; transition: background 0.3s, color 0.3s; }
        .sidebar {
            min-height: 100vh;
            background: #212529;
            color: #fff;
            transition: width 0.3s, background 0.3s, color 0.3s;
        }
        .sidebar.collapsed { width: 60px; }
        .sidebar .nav-link { color: #adb5bd; transition: color 0.2s; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { color: #fff; background: #343a40; }
        .sidebar .sidebar-header { padding: 1.5rem 1rem; font-size: 1.25rem; font-weight: bold; }
        .sidebar .user-info { padding: 1rem; border-bottom: 1px solid #343a40; }
        .sidebar .user-info .avatar { width: 40px; height: 40px; border-radius: 50%; background: #495057; display: inline-block; margin-right: 0.5rem; }
        .sidebar .collapse-btn { position: absolute; top: 1rem; right: 1rem; color: #adb5bd; cursor: pointer; }
        .main-content { margin-left: 220px; transition: margin-left 0.3s, background 0.3s, color 0.3s; }
        .sidebar.collapsed + .main-content { margin-left: 60px; }
        .sidebar .nav-icon { font-size: 1.2rem; margin-right: 0.75rem; }
        .sidebar .nav-link span { transition: opacity 0.3s; }
        .sidebar.collapsed .nav-link span { opacity: 0; width: 0; display: inline-block; }
        .sidebar.collapsed .sidebar-header, .sidebar.collapsed .user-info span { display: none; }
        .sidebar.collapsed .user-info .avatar { margin-right: 0; }
        .sidebar .nav-link { padding: 0.75rem 1rem; }
        /* Dark mode styles */
        body.dark-mode { background: #181a1b !important; color: #e9ecef !important; }
        .main-content.dark-mode { background: #181a1b !important; color: #e9ecef !important; }
        .sidebar.dark-mode { background: #111315 !important; color: #e9ecef !important; }
        .sidebar.dark-mode .nav-link { color: #b0b8c1; }
        .sidebar.dark-mode .nav-link.active, .sidebar.dark-mode .nav-link:hover { color: #fff; background: #23272b; }
        .sidebar.dark-mode .sidebar-header, .sidebar.dark-mode .user-info { border-bottom: 1px solid #23272b; }
        .sidebar.dark-mode .avatar { background: #23272b; }
        .card.dark-mode { background: #23272b; color: #e9ecef; }
        .table.dark-mode { background: #23272b; color: #e9ecef; }
        .dropdown-menu.dark-mode { background: #23272b; color: #e9ecef; }
        .form-control.dark-mode, .btn.dark-mode { background: #23272b; color: #e9ecef; border-color: #343a40; }
        .toast.dark-mode { background: #23272b; color: #e9ecef; }

        @media (max-width: 991.98px) {
            .sidebar { position: fixed; left: -220px; top: 0; z-index: 1040; width: 220px !important; height: 100vh; box-shadow: 2px 0 8px rgba(0,0,0,0.1); transition: left 0.3s, width 0.3s; }
            .sidebar.open { left: 0; }
            .main-content { margin-left: 0 !important; }
            .sidebar.collapsed { width: 60px !important; }
            .sidebar.collapsed.open { left: 0; }
            .sidebar .collapse-btn { display: none; }
            .mobile-sidebar-toggle { display: inline-block !important; }
        }
        @media (max-width: 575.98px) {
            .main-content { padding: 1rem !important; }
            .d-flex.align-items-center.justify-content-between.mb-4.flex-wrap.gap-2 { flex-direction: column !important; align-items: stretch !important; gap: 0.5rem !important; }
            .sidebar { width: 100vw !important; }
        }
        .mobile-sidebar-toggle { display: none; background: none; border: none; color: #212529; font-size: 2rem; margin-right: 1rem; }
        .sidebar-backdrop { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.3); z-index: 1039; }
        .sidebar.open ~ .sidebar-backdrop { display: block; }
    </style>
</head>
<body>
<div class="d-flex">
    <button class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Open sidebar"><i class="bi bi-list"></i></button>
    <nav class="sidebar position-relative" id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-between">
            <span><i class="bi bi-shield-lock"></i> Grievance</span>
            <i class="bi bi-chevron-double-left collapse-btn" id="collapseBtn"></i>
        </div>
        <div class="user-info d-flex align-items-center">
            <div class="avatar"><i class="bi bi-person-circle text-white fs-3"></i></div>
            <span><?php echo htmlspecialchars($user['name'] ?? ''); ?></span>
        </div>
        <ul class="nav flex-column mt-3">
            <li class="nav-item"><a class="nav-link" href="/src/pages/dashboard.php"><i class="bi bi-speedometer nav-icon"></i> <span>Dashboard</span></a></li>
            <li class="nav-item"><a class="nav-link" href="/src/pages/grievance.php"><i class="bi bi-list-task nav-icon"></i> <span>Grievances</span></a></li>
            <li class="nav-item"><a class="nav-link" href="/src/pages/user_management.php"><i class="bi bi-people nav-icon"></i> <span>User Management</span></a></li>
            <li class="nav-item"><a class="nav-link" href="/src/pages/admin.php"><i class="bi bi-gear nav-icon"></i> <span>Admin Panel</span></a></li>
            <?php if (isset($user['role']) && ($user['role'] === 'admin' || $user['role'] === 'superadmin')): ?>
            <li class="nav-item"><a class="nav-link" href="/src/pages/admin_smtp.php"><i class="bi bi-envelope nav-icon"></i> <span>SMTP Settings</span></a></li>
            <li class="nav-item"><a class="nav-link" href="/src/pages/admin_whatsapp.php"><i class="bi bi-whatsapp nav-icon"></i> <span>WhatsApp API Settings</span></a></li>
            <li class="nav-item"><a class="nav-link" href="/src/pages/admin_storage.php"><i class="bi bi-hdd-network nav-icon"></i> <span>File Storage Settings</span></a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="/src/pages/logout.php"><i class="bi bi-box-arrow-right nav-icon"></i> <span>Logout</span></a></li>
        </ul>
    </nav>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <div class="main-content flex-grow-1 p-4">
        <!-- Header with search, notifications, profile -->
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <form class="d-flex flex-grow-1 me-3" method="get" action="/src/pages/search.php" style="max-width: 400px;">
                <input class="form-control me-2" type="search" name="q" placeholder="Search grievances, users, categories..." aria-label="Search">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <div class="d-flex align-items-center gap-3">
                <!-- Notification bell -->
                <button class="btn btn-link position-relative" id="notifBtn" title="Notifications">
                    <i class="bi bi-bell fs-4"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.7em;display:none;" id="notifBadge">0</span>
                </button>
                <!-- Dark mode toggle -->
                <button class="btn btn-link" id="darkModeToggle" title="Toggle dark mode">
                    <i class="bi bi-moon-stars fs-4" id="darkModeIcon"></i>
                </button>
                <!-- Toast container for notifications -->
                <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
                    <div id="toastContainer"></div>
                </div>
                <!-- Profile dropdown -->
                <div class="dropdown">
                    <button class="btn btn-link dropdown-toggle d-flex align-items-center" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-4 me-1"></i>
                        <span class="d-none d-md-inline">Profile</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="/src/pages/profile.php"><i class="bi bi-person"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="/src/pages/change_password.php"><i class="bi bi-key"></i> Change Password</a></li>
                        <li><a class="dropdown-item" href="/src/pages/notification_prefs.php"><i class="bi bi-bell-slash"></i> Notification Preferences</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/src/pages/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php if (isset($page_title)): ?>
            <h1 class="mb-4 animate__animated animate__fadeInDown"><?php echo htmlspecialchars($page_title); ?></h1>
        <?php endif; ?>
        <?php if (isset($content)) echo $content; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script>
    // Sidebar collapse/expand
    document.getElementById('collapseBtn').onclick = function() {
        var sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('collapsed');
        document.querySelector('.main-content').classList.toggle('collapsed');
    };
    // Animate nav-link active state
    var links = document.querySelectorAll('.sidebar .nav-link');
    links.forEach(function(link) {
        if (link.href === window.location.href) {
            link.classList.add('active');
        }
    });
    // Toast notification system
    function showToast(message, type = 'info') {
        var toastId = 'toast-' + Date.now();
        var icon = type === 'success' ? 'bi-check-circle-fill' : type === 'error' ? 'bi-x-circle-fill' : 'bi-info-circle-fill';
        var bg = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-primary';
        var toastHtml = `<div id="${toastId}" class="toast align-items-center text-white ${bg} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">`
            + `<div class="d-flex">`
            + `<div class="toast-body"><i class="bi ${icon} me-2"></i>${message}</div>`
            + `<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>`
            + `</div></div>`;
        var container = document.getElementById('toastContainer');
        container.insertAdjacentHTML('beforeend', toastHtml);
        var toastEl = document.getElementById(toastId);
        var toast = new bootstrap.Toast(toastEl);
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', function() { toastEl.remove(); });
    }
    // Demo: show notification on bell click
    document.getElementById('notifBtn').onclick = function() {
        showToast('You have no new notifications.', 'info');
    };
    // Dark mode toggle logic
    function setDarkMode(enabled) {
        document.body.classList.toggle('dark-mode', enabled);
        document.querySelector('.main-content').classList.toggle('dark-mode', enabled);
        document.getElementById('sidebar').classList.toggle('dark-mode', enabled);
        document.querySelectorAll('.card, .table, .dropdown-menu, .form-control, .btn, .toast').forEach(function(el) {
            if (enabled) el.classList.add('dark-mode');
            else el.classList.remove('dark-mode');
        });
        document.getElementById('darkModeIcon').className = enabled ? 'bi bi-brightness-high fs-4' : 'bi bi-moon-stars fs-4';
    }
    function getDarkModePref() {
        return localStorage.getItem('darkMode') === 'true';
    }
    function setDarkModePref(enabled) {
        localStorage.setItem('darkMode', enabled ? 'true' : 'false');
    }
    document.getElementById('darkModeToggle').onclick = function() {
        var enabled = !getDarkModePref();
        setDarkModePref(enabled);
        setDarkMode(enabled);
    };
    // On load, apply dark mode if preferred
    document.addEventListener('DOMContentLoaded', function() {
        setDarkMode(getDarkModePref());
    });

    // Responsive sidebar toggle
    var sidebar = document.getElementById('sidebar');
    var mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    var sidebarBackdrop = document.getElementById('sidebarBackdrop');
    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebarBackdrop.style.display = 'none';
    }
    function openSidebar() {
        sidebar.classList.add('open');
        sidebarBackdrop.style.display = 'block';
    }
    mobileSidebarToggle.onclick = function() {
        if (sidebar.classList.contains('open')) closeSidebar();
        else openSidebar();
    };
    sidebarBackdrop.onclick = closeSidebar;
    // Close sidebar on nav click (mobile)
    document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) closeSidebar();
        });
    });
</script>
</body>
</html>
