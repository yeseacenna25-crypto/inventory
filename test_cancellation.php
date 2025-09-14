<?php
// Test script to verify order cancellation reflects to admin side
session_start();

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Testing Order Cancellation Reflection</h2>";

// Check if orders table has distributor_id column
echo "<h3>1. Database Schema Check</h3>";
$check_column = $conn->query("SHOW COLUMNS FROM orders LIKE 'distributor_id'");
if ($check_column->num_rows > 0) {
    echo "✓ Orders table has distributor_id column<br>";
} else {
    echo "✗ Orders table missing distributor_id column<br>";
}

// Check recent orders with different statuses
echo "<h3>2. Recent Orders Status Check</h3>";
$recent_orders = $conn->query("SELECT order_id, customer_name, status, distributor_id, created_at FROM orders ORDER BY created_at DESC LIMIT 10");

if ($recent_orders->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Order ID</th><th>Customer</th><th>Status</th><th>Distributor ID</th><th>Created</th></tr>";
    
    while ($row = $recent_orders->fetch_assoc()) {
        $status_color = '';
        switch($row['status']) {
            case 'pending': $status_color = 'orange'; break;
            case 'processing': $status_color = 'blue'; break;
            case 'completed': $status_color = 'green'; break;
            case 'cancelled': $status_color = 'red'; break;
        }
        
        echo "<tr>";
        echo "<td>{$row['order_id']}</td>";
        echo "<td>{$row['customer_name']}</td>";
        echo "<td style='color: $status_color; font-weight: bold'>{$row['status']}</td>";
        echo "<td>{$row['distributor_id']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No orders found in database.<br>";
}

// Check if fetch_orders.php includes cancelled status
echo "<h3>3. Admin Fetch Orders Status Filter Check</h3>";
$fetch_orders_content = file_get_contents('fetch_orders.php');
if (strpos($fetch_orders_content, 'cancelled') !== false) {
    echo "✓ Admin fetch_orders.php handles cancelled status<br>";
} else {
    echo "✗ Admin fetch_orders.php may not handle cancelled status<br>";
}

// Check if update_order_status.php allows cancelled status
$update_status_content = file_get_contents('update_order_status.php');
if (strpos($update_status_content, 'cancelled') !== false) {
    echo "✓ Admin update_order_status.php allows cancelled status<br>";
} else {
    echo "✗ Admin update_order_status.php may not allow cancelled status<br>";
}

// Test distributor cancel_order.php exists and is accessible
echo "<h3>4. Distributor Cancellation File Check</h3>";
if (file_exists('DISTRIBUTORfolder/cancel_order.php')) {
    echo "✓ Distributor cancel_order.php exists<br>";
    
    // Check if it has proper database connection and distributor_id filtering
    $cancel_content = file_get_contents('DISTRIBUTORfolder/cancel_order.php');
    if (strpos($cancel_content, 'distributor_id') !== false) {
        echo "✓ Distributor cancel_order.php filters by distributor_id<br>";
    } else {
        echo "✗ Distributor cancel_order.php may not filter by distributor_id<br>";
    }
    
    if (strpos($cancel_content, "status = 'cancelled'") !== false) {
        echo "✓ Distributor cancel_order.php sets status to cancelled<br>";
    } else {
        echo "✗ Distributor cancel_order.php may not set status to cancelled<br>";
    }
} else {
    echo "✗ Distributor cancel_order.php does not exist<br>";
}

echo "<h3>5. Summary</h3>";
echo "<p><strong>Expected Flow:</strong></p>";
echo "<ol>";
echo "<li>Distributor clicks 'Cancel' button on their order</li>";
echo "<li>POST request sent to DISTRIBUTORfolder/cancel_order.php</li>";
echo "<li>cancel_order.php updates order status to 'cancelled' in database</li>";
echo "<li>Admin refreshes view_order.php or applies cancelled filter</li>";
echo "<li>Admin sees the order marked as cancelled</li>";
echo "</ol>";

echo "<p><strong>Recommendations:</strong></p>";
echo "<ul>";
echo "<li>Test the flow by logging in as a distributor, placing an order, then cancelling it</li>";
echo "<li>Then log in as admin and check if the cancelled order appears in the orders list</li>";
echo "<li>Verify that the admin can see all cancelled orders by using the status filter</li>";
echo "</ul>";

$conn->close();
?>
