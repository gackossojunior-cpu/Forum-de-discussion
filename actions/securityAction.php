<?php
require('database.php');
if (!isset($_SESSION["auth"]) || !$_SESSION["auth"]) {
    header('Location: login.php');
    exit();
}