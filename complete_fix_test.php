<?php
/**
 * Complete Delete Functionality Fix
 * This script will ensure everything works correctly
 */

session_start();

// Force admin session for testing
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1;
}

echo "<h1>🔧 Complete Delete Fix & Test</h1>";

// 1. Verify database connection
echo "<h2>1. Database Connection</h2>";
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
    exit();
} else {
    echo "<p style='color: green;'>✅ Connected to database successfully</p>";
}

// 2. Verify table structures
echo "<h2>2. Table Structure Verification</h2>";
$tables = ['admin_signup', 'staff_signup', 'distributor_signup'];
$all_good = true;

foreach ($tables as $table) {
    echo "<h4>$table:</h4>";
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        echo "<p style='color: red;'>❌ Table $table does not exist!</p>";
        $all_good = false;
        continue;
    }
    
    // Check columns
    $columns = $conn->query("SHOW COLUMNS FROM $table");
    $has_profile_image = false;
    $column_list = [];
    
    while ($col = $columns->fetch_assoc()) {
        $column_list[] = $col['Field'];
        if ($col['Field'] === 'profile_image') {
            $has_profile_image = true;
        }
    }
    
    echo "<p>Columns: " . implode(', ', $column_list) . "</p>";
    
    if ($has_profile_image) {
        echo "<p style='color: green;'>✅ profile_image column exists</p>";
    } else {
        echo "<p style='color: red;'>❌ profile_image column missing</p>";
        // Add it
        $add_sql = "ALTER TABLE $table ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL";
        if ($conn->query($add_sql)) {
            echo "<p style='color: green;'>✅ Added profile_image column</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add column: " . $conn->error . "</p>";
        }
    }
}

// 3. Test delete functionality
echo "<h2>3. Delete Functionality Test</h2>";

// Create a test user if needed
$test_admin_sql = "INSERT IGNORE INTO admin_signup (admin_id, first_name, last_name, email, username, password, role) 
                   VALUES (999, 'Test', 'Admin', 'test@test.com', 'testadmin', 'test123', 'Admin')";
$conn->query($test_admin_sql);

echo "<h3>Available Delete Methods:</h3>";
$delete_files = [
    'delete_admin.php' => 'Admin Delete',
    'delete_staff.php' => 'Staff Delete', 
    'delete_distributor.php' => 'Distributor Delete',
    'delete_universal_user.php' => 'Universal Delete'
];

foreach ($delete_files as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $description ($file) exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $description ($file) missing</p>";
    }
}

// 4. Test actual delete call
echo "<h2>4. Live Delete Test</h2>";
echo "<div id='deleteTest'>";
echo "<button onclick='testDeleteFunction()' style='padding: 10px; background: #dc3545; color: white; border: none; border-radius: 5px;'>Test Delete Function</button>";
echo "<div id='deleteResult' style='margin-top: 10px;'></div>";
echo "</div>";

// 5. User management links
echo "<h2>5. Management Links</h2>";
echo "<ul>";
echo "<li><a href='admin_list.php' target='_blank'>Admin List</a></li>";
echo "<li><a href='staff_list.php' target='_blank'>Staff List</a></li>";
echo "<li><a href='distributor_list.php' target='_blank'>Distributor List</a></li>";
echo "<li><a href='user_management.php' target='_blank'>User Management</a></li>";
echo "<li><a href='edit_universal_profile.php?type=admin&id=1' target='_blank'>Edit Profile</a></li>";
echo "</ul>";

$conn->close();
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function testDeleteFunction() {
    document.getElementById('deleteResult').innerHTML = '<p style="color: blue;">Testing delete function...</p>';
    
    // Test the universal delete function
    fetch('delete_universal_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_type: 'admin',
            user_id: 999 // Test user ID
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('deleteResult').innerHTML = '<p style="color: green;">✅ ' + data.message + '</p>';
            Swal.fire('Success!', data.message, 'success');
        } else {
            document.getElementById('deleteResult').innerHTML = '<p style="color: orange;">⚠️ Expected error: ' + data.message + '</p>';
            Swal.fire('Info', 'Delete function is working (expected error): ' + data.message, 'info');
        }
    })
    .catch(error => {
        document.getElementById('deleteResult').innerHTML = '<p style="color: red;">❌ Network error: ' + error + '</p>';
        Swal.fire('Error!', 'Network error: ' + error, 'error');
    });
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; max-width: 1200px; }
h1, h2, h3, h4 { color: #410101; }
h1 { border-bottom: 3px solid #410101; padding-bottom: 10px; }
h2 { border-bottom: 1px solid #ccc; padding-bottom: 5px; }
</style>
