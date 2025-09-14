<?php
// Check orders table structure
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Orders Table Structure Analysis</h2>";

// Check if orders table exists
$result = $conn->query("SHOW TABLES LIKE 'orders'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>❌ Orders table does not exist!</p>";
    echo "<p>This might be why the delete function is failing.</p>";
} else {
    echo "<p style='color: green;'>✅ Orders table exists</p>";
    
    // Show orders table structure
    echo "<h3>Orders table columns:</h3>";
    $columns = $conn->query("SHOW COLUMNS FROM orders");
    if ($columns) {
        echo "<ul>";
        $has_staff_id = false;
        $has_distributor_id = false;
        
        while ($col = $columns->fetch_assoc()) {
            echo "<li>{$col['Field']} - {$col['Type']}</li>";
            if ($col['Field'] === 'staff_id') {
                $has_staff_id = true;
            }
            if ($col['Field'] === 'distributor_id') {
                $has_distributor_id = true;
            }
        }
        echo "</ul>";
        
        if ($has_staff_id) {
            echo "<p style='color: green;'>✅ staff_id column exists in orders table</p>";
        } else {
            echo "<p style='color: red;'>❌ staff_id column missing in orders table</p>";
        }
        
        if ($has_distributor_id) {
            echo "<p style='color: green;'>✅ distributor_id column exists in orders table</p>";
        } else {
            echo "<p style='color: red;'>❌ distributor_id column missing in orders table</p>";
        }
    } else {
        echo "<p style='color: red;'>Error checking orders columns: " . $conn->error . "</p>";
    }
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #410101; }
</style>
