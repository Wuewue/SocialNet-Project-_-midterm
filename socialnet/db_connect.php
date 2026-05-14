<?php
// ============================================================
// File: db_connect.php
// Description: Centralized database connection using MySQLi.
//              Include this file at the top of any page that
//              needs database access.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');   // Change to your MySQL user
define('DB_PASS', '');       // Change to your MySQL password
define('DB_NAME', 'socialnet');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // In production, log this error — never expose it to the user
    error_log("Database connection failed: " . $conn->connect_error);
    die(json_encode(['error' => 'Database connection error. Please contact the administrator.']));
}

// Set charset to prevent SQL injection via encoding attacks
$conn->set_charset("utf8mb4");
?>
