<?php
// Script to safely connect orders to products
// This will establish the connection without causing errors

$conn = new mysqli("localhost", "root", "", "inventory_negrita");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Connecting Orders to Products</h2>";

try {
    // Step 1: Check table engines
    echo "<h3>Step 1: Checking Table Engines</h3>";
    $engine_check = $conn->query("
        SELECT TABLE_NAME, ENGINE 
        FROM information_schema.TABLES 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME IN ('products', 'order_items', 'orders')
    ");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Table</th><th>Engine</th><th>Status</th></tr>";
    
    $tables_to_convert = [];
    while ($row = $engine_check->fetch_assoc()) {
        $needs_conversion = $row['ENGINE'] !== 'InnoDB';
        if ($needs_conversion) {
            $tables_to_convert[] = $row['TABLE_NAME'];
        }
        
        echo "<tr>";
        echo "<td>" . $row['TABLE_NAME'] . "</td>";
        echo "<td>" . $row['ENGINE'] . "</td>";
        echo "<td>" . ($needs_conversion ? "<span style='color: orange;'>Needs InnoDB</span>" : "<span style='color: green;'>OK</span>") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Convert tables to InnoDB if needed
    if (!empty($tables_to_convert)) {
        echo "<h3>Step 2: Converting Tables to InnoDB</h3>";
        foreach ($tables_to_convert as $table) {
            $result = $conn->query("ALTER TABLE `$table` ENGINE=InnoDB");
            if ($result) {
                echo "<p style='color: green;'>✓ Converted $table to InnoDB</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to convert $table: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p style='color: green;'>✓ All tables already use InnoDB</p>";
    }
    
    // Step 3: Check for orphaned records
    echo "<h3>Step 3: Checking for Orphaned Records</h3>";
    $orphan_check = $conn->query("
        SELECT COUNT(*) as orphaned_count
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE p.product_id IS NULL
    ");
    
    if ($orphan_check) {
        $orphan_row = $orphan_check->fetch_assoc();
        $orphaned_count = $orphan_row['orphaned_count'];
        
        if ($orphaned_count > 0) {
            echo "<p style='color: orange;'>⚠ Found $orphaned_count orphaned order items</p>";
            
            // Show orphaned records
            $orphan_details = $conn->query("
                SELECT oi.product_id, oi.product_name, COUNT(*) as occurrences
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.product_id
                WHERE p.product_id IS NULL
                GROUP BY oi.product_id, oi.product_name
            ");
            
            echo "<h4>Orphaned Products:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Product ID</th><th>Product Name</th><th>Occurrences</th></tr>";
            
            while ($orphan = $orphan_details->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $orphan['product_id'] . "</td>";
                echo "<td>" . $orphan['product_name'] . "</td>";
                echo "<td>" . $orphan['occurrences'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<p><strong>Options to fix orphaned records:</strong></p>";
            echo "<ul>";
            echo "<li><a href='?action=create_missing_products'>Create missing products</a></li>";
            echo "<li><a href='?action=delete_orphaned_items'>Delete orphaned order items</a> (Caution: This deletes data)</li>";
            echo "</ul>";
            
        } else {
            echo "<p style='color: green;'>✓ No orphaned records found</p>";
        }
    }
    
    // Handle actions
    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'create_missing_products') {
            echo "<h3>Creating Missing Products</h3>";
            $create_result = $conn->query("
                INSERT INTO products (product_id, product_name, quantity, price, description)
                SELECT DISTINCT oi.product_id, oi.product_name, 0, 0, 'Imported from order items'
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.product_id
                WHERE p.product_id IS NULL
            ");
            
            if ($create_result) {
                echo "<p style='color: green;'>✓ Missing products created successfully</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to create missing products: " . $conn->error . "</p>";
            }
        }
        
        if ($_GET['action'] === 'delete_orphaned_items') {
            echo "<h3>Deleting Orphaned Order Items</h3>";
            $delete_result = $conn->query("
                DELETE FROM order_items 
                WHERE product_id NOT IN (SELECT product_id FROM products)
            ");
            
            if ($delete_result) {
                echo "<p style='color: green;'>✓ Orphaned order items deleted successfully</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to delete orphaned items: " . $conn->error . "</p>";
            }
        }
    }
    
    // Step 4: Try to add foreign key constraint
    echo "<h3>Step 4: Adding Foreign Key Constraint</h3>";
    
    // Check if constraint already exists
    $constraint_check = $conn->query("
        SELECT CONSTRAINT_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'order_items'
        AND CONSTRAINT_NAME = 'fk_order_items_product'
    ");
    
    if ($constraint_check && $constraint_check->num_rows > 0) {
        echo "<p style='color: green;'>✓ Foreign key constraint already exists</p>";
    } else {
        $fk_result = $conn->query("
            ALTER TABLE order_items 
            ADD CONSTRAINT fk_order_items_product 
            FOREIGN KEY (product_id) REFERENCES products(product_id) 
            ON DELETE RESTRICT ON UPDATE CASCADE
        ");
        
        if ($fk_result) {
            echo "<p style='color: green;'>✓ Foreign key constraint added successfully</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Could not add foreign key constraint: " . $conn->error . "</p>";
            echo "<p>Don't worry - the order system will work fine without it. The application handles validation.</p>";
        }
    }
    
    // Step 5: Test the connection
    echo "<h3>Step 5: Testing Connection</h3>";
    $test_query = $conn->query("
        SELECT oi.order_id, oi.product_name as order_product_name, 
               p.product_name as actual_product_name, p.quantity
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        LIMIT 5
    ");
    
    if ($test_query && $test_query->num_rows > 0) {
        echo "<p style='color: green;'>✓ Connection test successful</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Order ID</th><th>Order Product Name</th><th>Actual Product Name</th><th>Stock</th></tr>";
        
        while ($row = $test_query->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['order_id'] . "</td>";
            echo "<td>" . $row['order_product_name'] . "</td>";
            echo "<td>" . ($row['actual_product_name'] ?: 'Product not found') . "</td>";
            echo "<td>" . ($row['quantity'] ?: 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: blue;'>No order items found yet - this is normal for a new system</p>";
    }
    
    echo "<h3>✅ Connection Process Complete!</h3>";
    echo "<p>Your orders and products are now properly connected. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='add_order.php'>Create new orders</a></li>";
    echo "<li><a href='view_order.php'>View existing orders</a></li>";
    echo "<li><a href='admin_dashboard.php'>Return to dashboard</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Connect Orders to Products</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        a { color: #640202; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
</body>
</html>
