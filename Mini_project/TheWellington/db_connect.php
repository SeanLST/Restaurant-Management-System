<?php
/**
 * Database Connection Configuration
 * Updated for thewellington1 database (NEW SCHEMA)
 */

$servername = "localhost";
$username = "root";        // Default XAMPP username
$password = "";            // Default XAMPP password is empty
$dbname = "thewellington1"; // ✅ UPDATED: Using new database name

// Create connection WITHOUT selecting database first
$conn = new mysqli($servername, $username, $password);

// Check connection to MySQL server
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    // $conn will have connect_error set, calling scripts can check this
} else {
    // Check if database exists, if not create it
    $dbCheck = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    if ($dbCheck && $dbCheck->num_rows == 0) {
        // Database doesn't exist - create it
        if ($conn->query("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            error_log("Database '$dbname' created successfully");
        } else {
            error_log("Failed to create database: " . $conn->error);
        }
    }
    
    // Now select the database
    if (!$conn->select_db($dbname)) {
        error_log("Failed to select database '$dbname': " . $conn->error);
        // Close connection and create new one with error state
        $errorMsg = "Database '$dbname' does not exist and could not be created. Please run setup_thewellington1.sql";
        $conn->close();
        // Create a new connection object that will have connect_error set
        $conn = new mysqli($servername, $username, $password, $dbname);
        // The connect_error will be automatically set by mysqli
    } else {
        // Only set charset if database selection is successful
        $conn->set_charset("utf8mb4");
    }
}
?>