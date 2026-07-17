<?php
// Execute the POS cart migration
// Run this file in browser: http://localhost/boycoldv2/boycoldv2/config/migrations/execute_migration.php

require_once __DIR__ . '/../db_config.php';

$sqlFile = __DIR__ . '/migrate_pos_cart_to_database.sql';

if (!file_exists($sqlFile)) {
    die("Error: Migration file not found: $sqlFile");
}

$sql = file_get_contents($sqlFile);

// Split SQL statements
$statements = explode(';', $sql);

$success = true;
$errors = [];

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    try {
        if (!$connect->query($statement)) {
            $errors[] = "Error executing statement: " . $connect->error;
            $success = false;
        }
    } catch (Exception $e) {
        $errors[] = "Exception: " . $e->getMessage();
        $success = false;
    }
}

if ($success) {
    echo "<h2>Migration completed successfully!</h2>";
    echo "<p>The following tables were created/updated:</p>";
    echo "<ul>";
    echo "<li>pos_cart - Stores POS cart items</li>";
    echo "<li>receipt_counters - Stores receipt number counters per branch</li>";
    echo "<li>pos_sessions - Tracks active POS sessions</li>";
    echo "</ul>";
    echo "<p><a href='../../POS/dashboard/pos-menu.php'>Go to POS Menu</a></p>";
} else {
    echo "<h2>Migration completed with errors:</h2>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}
?>
