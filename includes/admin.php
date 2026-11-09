<?php
require_once __DIR__ . '/../actions/database.php';

function forum_require_admin()
{
    global $bdb;

    if (empty($_SESSION['auth']) || empty($_SESSION['id'])) {
        header('Location: ../login.php');
        exit;
    }

    $statement = $bdb->prepare('SELECT role FROM users WHERE id = ?');
    $statement->execute([$_SESSION['id']]);
    $role = $statement->fetchColumn();

    if ($role !== 'admin') {
        unset($_SESSION['role']);
        header('Location: ../index.php');
        exit;
    }

    $_SESSION['role'] = $role;
}

function forum_admin_csrf_token()
{
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_csrf_token'];
}

function forum_admin_validate_csrf_token($token)
{
    return isset($_SESSION['admin_csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['admin_csrf_token'], $token);
}
