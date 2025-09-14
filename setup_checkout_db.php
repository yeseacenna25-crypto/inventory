<?php
// Quick database setup for enhanced checkout functionality
// Run this PHP file to set up/update the database structure

error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory_negrita";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h2>Setting up Enhanced Checkout Database Structure</h2>";
    
    // Read and execute the update SQL
    $sql = file_get_contents('update_orders_structure.sql');
    
    if ($sql === false) {
        throw new Exception("Could not read update_orders_structure.sql file");
    }
    
    // Split SQL into individual statements and execute them
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) continue;
        
        echo "<p>Executing: " . substr($statement, 0, 50) . "...</p>";
        
        if (!$conn->query($statement)) {
            echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
        } else {
            echo "<p style='color: green;'>✓ Success</p>";
        }
    }
    
    // Test the structure
    echo "<h3>Testing Database Structure</h3>";
    
    // Check orders table structure
    $result = $conn->query("DESCRIBE orders");
    if ($result) {
        echo "<h4>Orders Table Structure:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check if distributor_signup table exists
    $result = $conn->query("SHOW TABLES LIKE 'distributor_signup'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ distributor_signup table exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠ distributor_signup table does not exist. You may need to create it.</p>";
    }
    
    // Check if products table exists
    $result = $conn->query("SHOW TABLES LIKE 'products'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✓ products table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ products table does not exist. This is required for checkout.</p>";
    }
    
    echo "<h3>Setup Complete!</h3>";
    echo "<p>Your database is now ready for the enhanced checkout functionality.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure you have products in the products table</li>";
    echo "<li>Test the checkout functionality in the cart</li>";
    echo "<li>Check admin orders management for new orders</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
