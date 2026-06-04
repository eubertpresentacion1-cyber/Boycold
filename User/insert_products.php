<?php
// insert_products.php – run ONCE to populate products, then delete
session_start();
require_once '../config/db_config.php';

$products = [
    ['Americano', 'Classic Americano', 69, '/picture/Americano.png', 'coffee'],
    ['Cafe Latte', 'Smooth and creamy latte', 85, '/picture/Cafe Latte.png', 'coffee'],
    ['Spanish Latte', 'Rich condensed milk latte', 95, '/picture/Spanish Latte.png', 'coffee'],
    ['Dark Mocha', 'Bold mocha blend', 99, '/picture/Dark Mocha.png', 'coffee'],
    ['White Mocha', 'Smooth white chocolate mocha', 99, '/picture/White Mocha.png', 'coffee'],
    ['Caramel Macchiato', 'Caramel layered macchiato', 89, '/picture/Caramel Macchiato.png', 'coffee'],
    ['Hazelnut Latte', 'Nutty hazelnut latte', 85, '/picture/Hazelnut Latte.png', 'coffee'],
    ['Tiramisu Latte', 'Tiramisu flavored latte', 95, '/picture/Tiramisu Latte.png', 'coffee'],
    ['Sea Salt Latte', 'Savory sea salt latte', 115, '/picture/Sea salt Latte.png', 'special-coffee'],
    ['Salted Mango Dream', 'Mango with a hint of salt', 139, '/picture/Salted Mango Dream.png', 'special-coffee'],
    ['Biscoff Creamy Latte', 'Biscoff cookie latte', 109, '/picture/Biscoff Creamy Latte.png', 'special-coffee'],
    ['Butter scotch latte', 'Butterscotch flavored latte', 105, '/picture/Butter scotch latte.png', 'special-coffee'],
    ['Nutella Hazelnut latte', 'Nutella and hazelnut blend', 99, '/picture/Nutella Hazelnut latte.png', 'special-coffee'],
    ['Salted Caramel', 'Salted caramel delight', 99, '/picture/Salted Caramel.png', 'special-coffee'],
    ['Salted Macadamia', 'Macadamia nut with sea salt', 119, '/picture/Salted Macadamia.png', 'special-coffee'],
    ['Pure matcha', 'Traditional pure matcha', 85, '/picture/Pure matcha.png', 'matcha-fusion'],
    ['Dirty Matcha', 'Matcha with espresso', 119, '/picture/Dirty Matcha.png', 'matcha-fusion'],
    ['Matcha Latte', 'Creamy matcha latte', 95, '/picture/Matcha Latte.png', 'matcha-fusion'],
    ['Cheesecake Matcha', 'Cheesecake matcha fusion', 125, '/picture/Cheesecake Matcha.png', 'matcha-fusion'],
    ['Choco Matcha', 'Chocolate matcha blend', 105, '/picture/Choco Matcha.png', 'matcha-fusion'],
    ['Lavender Matcha', 'Lavender infused matcha', 109, '/picture/Lavender Matcha.png', 'matcha-fusion'],
    ['Strawberry Matcha', 'Strawberry matcha fusion', 105, '/picture/Strawberry Matcha.png', 'matcha-fusion'],
    ['Seasalt Matcha', 'Matcha with sea salt', 99, '/picture/Seasalt Matcha.png', 'matcha-fusion'],
    ['Matcha Frappe', 'Cold matcha frappe', 99, '/picture/Matcha Frappe.png', 'matcha-fusion'],
    ['Matcha Freddo', 'Iced matcha freddo', 89, '/picture/Matcha Freddo.png', 'matcha-fusion'],
    ['Matcha banana Pudding', 'Matcha with banana', 119, '/picture/Matcha banana Pudding.png', 'matcha-fusion'],
    ['Matcha waffle', 'Matcha flavored waffle', 139, '/picture/Matcha waffle.png', 'matcha-fusion'],
    ['Strawberry Milk', 'Fresh strawberry milk shake', 79, '/picture/Strawberry Milk.png', 'fruit-shake'],
    ['Blueberry Milk', 'Blueberry milk shake', 79, '/picture/Blueberry Milk.png', 'fruit-shake'],
    ['BLUEBERRY SHAKE', 'Premium blueberry shake', 85, '/picture/BLUEBERRY SHAKE 1.png', 'fruit-shake'],
    ['Strawberry shake', 'Fresh strawberry shake', 79, '/picture/Strawberry shake.png', 'fruit-shake'],
    ['Mango graham', 'Mango with graham', 89, '/picture/Mango graham.png', 'fruit-shake'],
    ['Mango matcha', 'Mango and matcha fusion', 99, '/picture/Mango matcha.png', 'fruit-shake'],
    ['Berry mango', 'Berry and mango blend', 89, '/picture/Berry mango.png', 'fruit-shake'],
    ['Berry Caramel Bliss', 'Berry with caramel', 99, '/picture/Berry Caramel Bliss.png', 'fruit-shake'],
    ['Berry Oreo', 'Berry with Oreo', 99, '/picture/Berry Oreo.png', 'fruit-shake'],
    ['mango oreo', 'Mango and Oreo blend', 99, '/picture/mango oreo.png', 'fruit-shake'],
    ['Caramel Frappe', 'Caramel iced frappe', 99, '/picture/Caramel Frappe.png', 'frappe-series'],
    ['Oreo Frappe', 'Oreo cookie frappe', 99, '/picture/Oreo Frappe.png', 'frappe-series'],
    ['Biscoff frappe', 'Biscoff cookie frappe', 99, '/picture/Biscoff frappe.png', 'frappe-series'],
    ['Cheesecake Frappe', 'Cheesecake flavored frappe', 99, '/picture/Cheesecake Frappe.png', 'frappe-series'],
    ['Nuttela Hazelnut Frappe', 'Nutella hazelnut frappe', 99, '/picture/Nuttela Hazelnut Frappe.png', 'frappe-series'],
    ['Chocolate waffle', 'Chocolate flavored waffle', 129, '/picture/Chocolate waffle.png', 'waffles'],
    ['Biscoff waffle', 'Biscoff cookie waffle', 139, '/picture/Biscoff waffle.png', 'waffles'],
    ['Oreo waffle', 'Oreo cookie waffle', 139, '/picture/Oreo waffle.png', 'waffles'],
    ['Strawberry waffle', 'Fresh strawberry waffle', 139, '/picture/Strawberry waffle.png', 'waffles'],
    ['tiramisu waffle', 'Tiramisu flavored waffle', 149, '/picture/tiramisu waffle.png', 'waffles'],
    ['ube waffle', 'Ube flavored waffle', 149, '/picture/ube waffle.png', 'waffles'],
    ['Franch Vanilla', 'French vanilla drink', 75, '/picture/Franch Vanilla.png', 'non-coffee'],
    ['White cocoa', 'White chocolate cocoa', 85, '/picture/White cocoa.png', 'non-coffee'],
    ['Cheesecake Latte', 'Cheesecake flavored latte', 99, '/picture/Cheesecake Latte.png', 'non-coffee'],
    ['Choco Vanilla Cookie', 'Chocolate vanilla cookie drink', 129, '/picture/Choco Vanilla Cookie.png', 'non-coffee'],
    ['Choco Banana Pudding', 'Chocolate banana pudding drink', 179, '/picture/Choco Banana Pudding.png', 'non-coffee'],
    ['Milky Oreo', 'Oreo milk drink', 89, '/picture/Milky Oreo.png', 'non-coffee'],
    ['Java Chips', 'Java chips blended drink', 99, '/picture/Java Chips.png', 'non-coffee'],
    ['Fries', 'Crispy French fries', 65, '/picture/Fries.png', 'bites'],
    ['Chicken Poppers', 'Crispy chicken poppers', 89, '/picture/Chicken Poppers.png', 'bites'],
    ['Chicken poppers and fries', 'Poppers with fries combo', 99, '/picture/Chicken poppers and fries.png', 'bites'],
    ['Beef Natchos', 'Beef loaded nachos', 149, '/picture/Beef Natchos.png', 'bites'],
    ['Fries and Chicken Poppers', 'Fries with chicken poppers', 99, '/picture/Chicken poppers and fries.png', 'bites'],
    ['Beef Quesadilla', 'Grilled beef quesadilla', 149, '/picture/Beef Quesadilla.png', 'quesadilla'],
    ['Chicken Quesadilla', 'Grilled chicken quesadilla', 159, '/picture/Chicken Quesadilla.png', 'quesadilla'],
    ['Messy Tuna Spinach', 'Tuna and spinach quesadilla', 129, '/picture/Messy Tuna Spinach.png', 'quesadilla'],
];

$stmt = $connect->prepare("INSERT INTO products (product_name, description, price, image, category, is_available) VALUES (?, ?, ?, ?, ?, 1)");
if (!$stmt) {
    die("Prepare failed: " . $connect->error);
}

$count = 0;
$errors = [];

foreach ($products as $p) {
    $stmt->bind_param("ssdss", $p[0], $p[1], $p[2], $p[3], $p[4]);
    if (!$stmt->execute()) {
        $errors[] = "Error inserting '{$p[0]}': " . $stmt->error;
    } else {
        $count++;
    }
}
$stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Product Insertion Result</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: green; font-weight: bold; font-size: 18px; }
        .error { color: red; }
        ul { margin: 10px 0; }
        li { margin: 5px 0; }
        a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #6F4E37; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✓ Product Data Insertion Complete</h1>
        <p class="success">Successfully inserted: <?= $count ?> products</p>
        
        <?php if (!empty($errors)): ?>
            <h2>Warnings:</h2>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li class="error"><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <p>You can now visit the menu to see all products.</p>
        <a href="menu.php">Go to Menu →</a>
    </div>
</body>
</html>