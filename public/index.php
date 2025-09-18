<?php
// Main entry point for the application
session_start();

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/functions.php';

$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'login':
        require __DIR__ . '/../src/pages/login.php';
        break;
    case 'logout':
        require __DIR__ . '/../src/pages/logout.php';
        break;
    case 'dashboard':
        require __DIR__ . '/../src/pages/dashboard.php';
        break;
    case 'grievance':
        require __DIR__ . '/../src/pages/grievance.php';
        break;
    case 'admin':
        require __DIR__ . '/../src/pages/admin.php';
        break;
    default:
        require __DIR__ . '/../src/pages/home.php';
        break;
}
