<?php
require_once __DIR__ . '/../database.php';

if (empty($_SESSION['auth'])) {
    header('Location: ../login.php');
    exit;
}
