<?php
require_once 'config/db_config.php';

echo "Adding 'popular' category to top products...\n";

// List of popular products to tag
$popularProducts = [
    'Americano',
    'Cafe Latte',
    'Spanish Latte',
    'Caramel Macchiato',
    'Sea Salt Latte',
    'Dirty Matcha',
    'Matcha Latte',
    'Strawberry Milk',
    'Mango graham',
    'Caramel Frappe',
    'Oreo Frappe',
    'Chocolate waffle',
    'White cocoa',
    'Milky Oreo',
];

foreach ($popularProducts as $productName) {
    $stmt = $connect->prepare("UPDATE products SET category = CONCAT(category, ' popular') WHERE product_name = ? AND category NOT LIKE '%popular%'");
    $stmt->bind_param("s", $productName);
    if ($stmt->execute()) {
        echo "✓ Tagged '$productName' as popular\n";
    } else {
        echo "✗ Failed to tag '$productName': " . $stmt->error . "\n";
    }
}

echo "\nChecking popular products in database:\n";
$stmt = $connect->prepare("SELECT product_name, category FROM products WHERE category LIKE '%popular%' ORDER BY product_name");
$stmt->execute();
$result = $stmt->get_result();
$count = 0;
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['product_name']} (category: {$row['category']})\n";
    $count++;
}

echo "\nTotal popular products: $count\n";
?>
