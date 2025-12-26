<?php
/**
 * Script to delete test/debug files
 * Run this once to clean up, then delete this file itself
 */

$filesToDelete = [
    'check_tables.php',
    'create_database.php',
    'create_tables_direct.php',
    'database_diagnostic.php',
    'debug_registration.php',
    'quick_setup_tables.php',
    'setup_tables.php',
    'test_db_connection.php',
    'test_login.php',
    'test_registration.php',
    'CLEANUP_FILES.md',
    'orders_db.json',
    'users_db.json',
];

echo "<h2>Cleaning up test files...</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; }</style>";

foreach ($filesToDelete as $file) {
    $filePath = __DIR__ . '/' . $file;
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo "<p class='success'>✓ Deleted: $file</p>";
        } else {
            echo "<p class='error'>✗ Failed to delete: $file</p>";
        }
    } else {
        echo "<p>ℹ Not found: $file</p>";
    }
}

echo "<h3>Done! You can now delete this file (delete_test_files.php) as well.</h3>";
?>

