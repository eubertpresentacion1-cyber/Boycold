<?php
require_once __DIR__ . '/../db_config.php';

// Function to check if column exists
function columnExists($connect, $table, $column) {
    $result = $connect->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// Add branch_id to ingredients table
if (!columnExists($connect, 'ingredients', 'branch_id')) {
    $sql = "ALTER TABLE `ingredients` ADD COLUMN `branch_id` int DEFAULT NULL AFTER `unit`, ADD INDEX `idx_branch_id` (`branch_id`)";
    if ($connect->query($sql)) {
        echo "✓ Added branch_id to ingredients table\n";
        // Update existing records
        $connect->query("UPDATE `ingredients` SET `branch_id` = 1 WHERE `branch_id` IS NULL");
        echo "✓ Updated existing ingredients records to branch_id = 1\n";
    } else {
        echo "✗ Failed to add branch_id to ingredients: " . $connect->error . "\n";
    }
} else {
    echo "✓ branch_id already exists in ingredients table\n";
}

// Add branch_id to daily_sales_summary table
if (!columnExists($connect, 'daily_sales_summary', 'branch_id')) {
    $sql = "ALTER TABLE `daily_sales_summary` ADD COLUMN `branch_id` int DEFAULT NULL AFTER `sale_date`, ADD INDEX `idx_branch_id` (`branch_id`)";
    if ($connect->query($sql)) {
        echo "✓ Added branch_id to daily_sales_summary table\n";
        // Update existing records
        $connect->query("UPDATE `daily_sales_summary` SET `branch_id` = 1 WHERE `branch_id` IS NULL");
        echo "✓ Updated existing daily_sales_summary records to branch_id = 1\n";
    } else {
        echo "✗ Failed to add branch_id to daily_sales_summary: " . $connect->error . "\n";
    }
} else {
    echo "✓ branch_id already exists in daily_sales_summary table\n";
}

// Add branch_id to ingredient_usage_daily table
if (!columnExists($connect, 'ingredient_usage_daily', 'branch_id')) {
    $sql = "ALTER TABLE `ingredient_usage_daily` ADD COLUMN `branch_id` int DEFAULT NULL AFTER `usage_date`, ADD INDEX `idx_branch_id` (`branch_id`)";
    if ($connect->query($sql)) {
        echo "✓ Added branch_id to ingredient_usage_daily table\n";
        // Update existing records
        $connect->query("UPDATE `ingredient_usage_daily` SET `branch_id` = 1 WHERE `branch_id` IS NULL");
        echo "✓ Updated existing ingredient_usage_daily records to branch_id = 1\n";
    } else {
        echo "✗ Failed to add branch_id to ingredient_usage_daily: " . $connect->error . "\n";
    }
} else {
    echo "✓ branch_id already exists in ingredient_usage_daily table\n";
}

echo "\nMigration completed.\n";
