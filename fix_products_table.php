<?php
// Migration script to standardize products table column names
// This will fix the inconsistency between trial_view.php and fetch_products.php

$mysqli = new mysqli("localhost", "root", "", "inventory_negrita");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "<h2>Products Table Column Standardization</h2>";

try {
    // Check current table structure
    $result = $mysqli->query("DESCRIBE products");
    $columns = [];
    
    echo "<h3>Current Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $changes_made = false;
    
    // Check if we need to rename 'stock' to 'quantity'
    if (in_array('stock', $columns) && !in_array('quantity', $columns)) {
        echo "<h3>Renaming 'stock' column to 'quantity'...</h3>";
        $mysqli->query("ALTER TABLE products CHANGE COLUMN stock quantity INT NOT NULL DEFAULT 0");
        echo "<p style='color: green;'>✓ Renamed 'stock' to 'quantity'</p>";
        $changes_made = true;
    } elseif (in_array('quantity', $columns)) {
        echo "<p style='color: green;'>✓ 'quantity' column already exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Neither 'stock' nor 'quantity' column found!</p>";
    }
    
    // Check if we need to rename 'image' to 'product_image'
    if (in_array('image', $columns) && !in_array('product_image', $columns)) {
        echo "<h3>Renaming 'image' column to 'product_image'...</h3>";
        $mysqli->query("ALTER TABLE products CHANGE COLUMN image product_image VARCHAR(255)");
        echo "<p style='color: green;'>✓ Renamed 'image' to 'product_image'</p>";
        $changes_made = true;
    } elseif (in_array('product_image', $columns)) {
        echo "<p style='color: green;'>✓ 'product_image' column already exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Neither 'image' nor 'product_image' column found!</p>";
    }
    
    // Add missing columns if needed
    if (!in_array('category', $columns)) {
        echo "<h3>Adding 'category' column...</h3>";
        $mysqli->query("ALTER TABLE products ADD COLUMN category VARCHAR(100) AFTER product_name");
        echo "<p style='color: green;'>✓ Added 'category' column</p>";
        $changes_made = true;
    }
    
    if (!in_array('product_id', $columns)) {
        echo "<h3>Adding 'product_id' primary key...</h3>";
        $mysqli->query("ALTER TABLE products ADD COLUMN product_id INT AUTO_INCREMENT PRIMARY KEY FIRST");
        echo "<p style='color: green;'>✓ Added 'product_id' column</p>";
        $changes_made = true;
    }
    
    if ($changes_made) {
        echo "<h3>Updated Table Structure:</h3>";
        $result = $mysqli->query("DESCRIBE products");
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>✅ Migration Complete!</h3>";
        echo "<p>The products table has been standardized. Now:</p>";
        echo "<ul>";
        echo "<li><a href='trial_view.php'>trial_view.php</a> should work without errors</li>";
        echo "<li><a href='add_order.php'>add_order.php</a> should load products correctly</li>";
        echo "<li><a href='fetch_products.php'>fetch_products.php</a> should return proper data</li>";
        echo "</ul>";
    } else {
        echo "<h3>✅ No Changes Needed</h3>";
        echo "<p>The table structure is already correct.</p>";
    }
    
    // Test the fixed query
    echo "<h3>Testing Product Query:</h3>";
    $test_result = $mysqli->query("SELECT product_name, description, price, quantity, product_image FROM products ORDER BY product_id DESC LIMIT 5");
    
    if ($test_result && $test_result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Query works! Found " . $test_result->num_rows . " products</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Name</th><th>Price</th><th>Quantity</th><th>Image</th></tr>";
        
        while ($row = $test_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
            echo "<td>₱" . number_format($row['price'], 2) . "</td>";
            echo "<td>" . $row['quantity'] . "</td>";
            echo "<td>" . ($row['product_image'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠ No products found. You may need to add some products first.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

$mysqli->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Products Table Migration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <p><a href="trial_view.php">← Back to Trial View</a> | <a href="admin_dashboard.php">Dashboard</a></p>
</body>
</html>
