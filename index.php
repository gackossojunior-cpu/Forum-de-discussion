<?php
require_once __DIR__ . '/actions/database.php';

if (empty($_SESSION['auth']) || empty($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Messages — Forum.';
$messengerCsrfToken = $_SESSION['messenger_csrf_token'] ??= bin2hex(random_bytes(32));
require __DIR__ . '/views/messenger/index.php';
