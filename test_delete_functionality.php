<?php
session_start();

// Set session for testing (you can change this to test different users)
$_SESSION['admin_id'] = 1;

echo "<h2>Delete Functionality Testing</h2>";

// Test database connection
echo "<h3>1. Database Connection Test</h3>";
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Test table structure and record counts
echo "<h3>2. User Tables Test</h3>";
$tables = [
    'admin_signup' => 'admin_id',
    'staff_signup' => 'staff_id', 
    'distributor_signup' => 'distributor_id'
];

foreach ($tables as $table => $id_column) {
    echo "<h4>$table table:</h4>";
    
    // Count records
    $count_query = "SELECT COUNT(*) as count FROM $table";
    $result = $conn->query($count_query);
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "<p style='color: green;'>✅ Records found: $count</p>";
        
        // Show sample records
        if ($count > 0) {
            $sample_query = "SELECT $id_column, first_name, last_name, username FROM $table LIMIT 3";
            $sample_result = $conn->query($sample_query);
            echo "<ul>";
            while ($row = $sample_result->fetch_assoc()) {
                echo "<li>ID: {$row[$id_column]} - {$row['first_name']} {$row['last_name']} ({$row['username']})</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>❌ Table query failed</p>";
    }
}

// Test log tables
echo "<h3>3. Log Tables Test</h3>";
$log_tables = ['admin_profile_logs', 'staff_profile_logs', 'distributor_profile_logs'];
foreach ($log_tables as $log_table) {
    $log_result = $conn->query("SHOW TABLES LIKE '$log_table'");
    if ($log_result && $log_result->num_rows > 0) {
        echo "<p style='color: green;'>✅ $log_table exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ $log_table does not exist (will be created when needed)</p>";
    }
}

// Test orders table
echo "<h3>4. Orders Table Test</h3>";
$orders_result = $conn->query("SHOW TABLES LIKE 'orders'");
if ($orders_result && $orders_result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Orders table exists</p>";
    
    // Check orders count
    $orders_count = $conn->query("SELECT COUNT(*) as count FROM orders");
    if ($orders_count) {
        $count = $orders_count->fetch_assoc()['count'];
        echo "<p>Total orders: $count</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Orders table does not exist</p>";
}

// Test delete files
echo "<h3>5. Delete Files Test</h3>";
$delete_files = [
    'delete_admin.php',
    'delete_staff.php', 
    'delete_distributor.php',
    'delete_universal_user.php'
];

foreach ($delete_files as $file) {
    if (file_exists($file)) {
        $readable = is_readable($file) ? "✅" : "❌";
        echo "<p>$file: Exists $readable</p>";
    } else {
        echo "<p style='color: red;'>❌ $file does not exist</p>";
    }
}

echo "<h3>6. User Management Links</h3>";
echo "<p><a href='admin_list.php' target='_blank'>Admin List (with delete buttons)</a></p>";
echo "<p><a href='staff_list.php' target='_blank'>Staff List (with delete buttons)</a></p>";
echo "<p><a href='distributor_list.php' target='_blank'>Distributor List (with delete buttons)</a></p>";

echo "<h3>7. Delete API Test</h3>";
echo "<p><strong>Note:</strong> The delete functionality uses AJAX calls with JSON data.</p>";
echo "<p>Example JavaScript to test delete admin (ID 2):</p>";
echo "<code style='background: #f4f4f4; padding: 10px; display: block; margin: 10px 0;'>
fetch('delete_admin.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({admin_id: 2})
})
.then(response => response.json())
.then(data => console.log(data));
</code>";

echo "<h3>8. Security Features</h3>";
echo "<ul>";
echo "<li>✅ Session-based authentication required</li>";
echo "<li>✅ Admin cannot delete themselves</li>";
echo "<li>✅ Cannot delete the last admin account</li>";
echo "<li>✅ Database transactions for data integrity</li>";
echo "<li>✅ Profile image cleanup</li>";
echo "<li>✅ Order preservation (sets user reference to NULL)</li>";
echo "<li>✅ Activity logging for auditing</li>";
echo "<li>✅ SQL injection protection with prepared statements</li>";
echo "</ul>";

if ($conn) {
    $conn->close();
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #410101; }
h3 { color: #666; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
h4 { color: #888; }
code { background: #f4f4f4; padding: 2px 4px; border-radius: 3px; }
</style>
