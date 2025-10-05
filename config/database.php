<?php

/**
 * Database Configuration
 * RentFinder SL Database Connection
 */

// Database configuration
$host = 'localhost';
$dbname = 'rentfinder_sl';
$username = 'root';
$password = '123321555';
$port = 3306;

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Log error with timestamp
    $errorMessage = "[" . date('Y-m-d H:i:s') . "] [ERROR] Database connection failed: " . $e->getMessage() . " [config/database.php:" . __LINE__ . "]";
    error_log($errorMessage, 3, 'logs/error.log');

    // Show user-friendly message
    if (basename($_SERVER['PHP_SELF']) !== 'setup_database.php') {
        die("Database connection failed. Please check your database configuration.");
    }
}
