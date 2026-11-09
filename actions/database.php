<?php
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!defined('FORUM_BOOTSTRAPPED')) {
        define('FORUM_BOOTSTRAPPED', true);
    }
    $databaseConfig = require __DIR__ . '/../config.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $databaseConfig['host'],
        $databaseConfig['port'],
        $databaseConfig['database']
    );
    $bdb = new PDO($dsn, $databaseConfig['username'], $databaseConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}
