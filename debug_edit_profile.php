<?php
session_start();

// Set admin session for testing
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['admin_id'] = 1; // Set a default admin ID for testing
}

echo "<h2>Edit Profile Debug</h2>";

// Test database connection
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h3>Testing Edit Profile Functionality</h3>";

// Test admin profile access
$user_type = 'admin';
$user_id = $_SESSION['admin_id'];

echo "<p><strong>User Type:</strong> $user_type</p>";
echo "<p><strong>User ID:</strong> $user_id</p>";

// Check table and column configuration
$table_config = [
    'admin' => ['table' => 'admin_signup', 'id_col' => 'admin_id'],
    'staff' => ['table' => 'staff_signup', 'id_col' => 'staff_id'],
    'distributor' => ['table' => 'distributor_signup', 'id_col' => 'distributor_id']
];

$config = $table_config[$user_type];
$table = $config['table'];
$id_column = $config['id_col'];

echo "<p><strong>Table:</strong> $table</p>";
echo "<p><strong>ID Column:</strong> $id_column</p>";

// Check if table exists and show its structure
echo "<h4>Table Structure for $table:</h4>";
$result = $conn->query("SHOW COLUMNS FROM $table");
if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['Field']} - {$row['Type']}</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
}

// Test the actual query that's causing issues
echo "<h4>Testing User Data Query:</h4>";
try {
    $stmt = $conn->prepare("SELECT * FROM $table WHERE $id_column = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
    
    if ($user_data) {
        echo "<p style='color: green;'>✅ User data retrieved successfully!</p>";
        echo "<p><strong>User:</strong> " . htmlspecialchars($user_data['first_name'] ?? 'N/A') . " " . htmlspecialchars($user_data['last_name'] ?? 'N/A') . "</p>";
        echo "<p><strong>Profile Image:</strong> " . htmlspecialchars($user_data['profile_image'] ?? 'None') . "</p>";
        
        // Test profile image path
        $profileImage = 'ASSETS/icon.jpg'; // Default
        if (isset($user_data['profile_image']) && !empty($user_data['profile_image'])) {
            $image_path = 'uploads/' . $user_data['profile_image'];
            if (file_exists($image_path)) {
                $profileImage = $image_path;
                echo "<p style='color: green;'>✅ Custom profile image found: $profileImage</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Custom profile image not found, using default</p>";
            }
        } else {
            echo "<p>Using default profile image</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ No user found with ID $user_id</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test access to edit profile page
echo "<h4>Test Links:</h4>";
echo "<ul>";
echo "<li><a href='edit_universal_profile.php?type=admin&id=$user_id' target='_blank'>Edit Admin Profile</a></li>";
echo "<li><a href='admin_list.php' target='_blank'>Admin List (with delete buttons)</a></li>";
echo "<li><a href='user_management.php' target='_blank'>User Management Page</a></li>";
echo "</ul>";

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #410101; }
</style>
