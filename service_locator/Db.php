<?php
/**
 * db.php
 * Central PDO database connection for ServiceLocator.
 * Include with: require_once 'db.php'; (or '../db.php' from a subfolder)
 */

$db_host = 'localhost';
$db_name = 'service_locator';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Never leak raw DB errors to the end user in production.
    error_log('DB connection failed: ' . $e->getMessage());
    die('Sorry, something went wrong connecting to the database. Please try again later.');
}