<?php
// Script to verify and standardize products table structure
// Run this script to ensure proper connection between orders and products

$conn = new mysqli("localhost", "root", "", "inventory_negrita");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Products Table Structure Verification</h2>";

try {
    // Check current table structure
    $result = $conn->query("DESCRIBE products");
    
    echo "<h3>Current Products Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for required columns and suggest fixes
    echo "<h3>Table Analysis:</h3>";
    
    $required_columns = [
        'product_id' => 'Primary key for products',
        'product_name' => 'Product name',
        'quantity' => 'Available stock (may be named "stock")',
        'price' => 'Product price'
    ];
    
    foreach ($required_columns as $col => $desc) {
        if (in_array($col, $columns)) {
            echo "<p style='color: green;'>✓ Column '$col' exists - $desc</p>";
        } else {
            // Check for alternative names
            if ($col == 'quantity' && in_array('stock', $columns)) {
                echo "<p style='color: orange;'>⚠ Column 'stock' found instead of 'quantity' - needs renaming</p>";
            } else {
                echo "<p style='color: red;'>✗ Column '$col' missing - $desc</p>";
            }
        }
    }
    
    // Check for image column variations
    if (in_array('product_image', $columns)) {
        echo "<p style='color: green;'>✓ Column 'product_image' exists</p>";
    } elseif (in_array('image', $columns)) {
        echo "<p style='color: orange;'>⚠ Column 'image' found instead of 'product_image' - should be renamed</p>";
    } else {
        echo "<p style='color: red;'>✗ Image column missing</p>";
    }
    
    // Test connection with orders
    echo "<h3>Testing Orders-Products Connection:</h3>";
    
    // Check if order_items table exists
    $tables_result = $conn->query("SHOW TABLES LIKE 'order_items'");
    if ($tables_result->num_rows > 0) {
        echo "<p style='color: green;'>✓ order_items table exists</p>";
        
        // Try to create a test join
        $join_test = $conn->query("
            SELECT COUNT(*) as count 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.product_id 
            LIMIT 1
        ");
        
        if ($join_test) {
            echo "<p style='color: green;'>✓ Join between order_items and products works</p>";
        } else {
            echo "<p style='color: red;'>✗ Join test failed: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ order_items table doesn't exist - run create_orders_tables.sql first</p>";
    }
    
    echo "<h3>Recommendations:</h3>";
    echo "<ol>";
    
    if (in_array('stock', $columns) && !in_array('quantity', $columns)) {
        echo "<li>Rename 'stock' column to 'quantity' for consistency</li>";
    }
    
    if (in_array('image', $columns) && !in_array('product_image', $columns)) {
        echo "<li>Rename 'image' column to 'product_image' for consistency</li>";
    }
    
    if (!in_array('category', $columns)) {
        echo "<li>Add 'category' column for better product organization</li>";
    }
    
    echo "<li>Run the connect_orders_to_products.sql script to apply these fixes</li>";
    echo "<li>Add foreign key constraint between order_items and products</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Products Table Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <p><a href="admin_dashboard.php">← Back to Dashboard</a></p>
    <script>
      // Prevent browser back navigation
      history.pushState(null, null, location.href);
      window.onpopstate = function () {
        history.go(1);
      };
    </script>
</body>
</html>
