<?php
require_once 'config.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!current_user()) {
        header('Location: ' . BASE_PATH . 'login');
        exit;
    }
}

function require_role($role) {
    require_login();
    if (current_user()['role'] !== $role && current_user()['role'] !== 'admin') {
        echo "Access denied";
        exit;
    }
}
?>
