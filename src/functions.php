<?php
// Common functions for the application
function is_logged_in() {
    return isset($_SESSION['user']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /?page=login');
        exit;
    }
}

function has_role($role) {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === $role;
}
