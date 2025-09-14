<?php
session_start();

// Set session for testing (you can change this to test different users)
$_SESSION['admin_id'] = 1;

echo "<h2>Profile Edit Testing</h2>";

// Test database connection
echo "<h3>1. Database Connection Test</h3>";
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Test uploads directory
echo "<h3>2. Uploads Directory Test</h3>";
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p style='color: green;'>✅ Uploads directory created successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create uploads directory</p>";
    }
} else {
    echo "<p style='color: green;'>✅ Uploads directory exists</p>";
    
    // Check if directory is writable
    if (is_writable($upload_dir)) {
        echo "<p style='color: green;'>✅ Uploads directory is writable</p>";
    } else {
        echo "<p style='color: red;'>❌ Uploads directory is not writable</p>";
    }
}

// Test admin user exists
echo "<h3>3. Admin User Test</h3>";
if ($conn) {
    $stmt = $conn->prepare("SELECT admin_id, first_name, last_name, email, profile_image FROM admin_signup WHERE admin_id = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Admin user found:</p>";
        echo "<ul>";
        echo "<li>ID: " . $user['admin_id'] . "</li>";
        echo "<li>Name: " . $user['first_name'] . " " . $user['last_name'] . "</li>";
        echo "<li>Email: " . $user['email'] . "</li>";
        echo "<li>Profile Image: " . ($user['profile_image'] ? $user['profile_image'] : 'None') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ No admin user found with ID 1</p>";
    }
    $stmt->close();
}

// Test table structure
echo "<h3>4. Table Structure Test</h3>";
$tables = ['admin_signup', 'staff_signup', 'distributor_signup'];
foreach ($tables as $table) {
    echo "<h4>$table table:</h4>";
    $result = $conn->query("SHOW COLUMNS FROM $table");
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        echo "<p style='color: green;'>✅ Columns: " . implode(', ', $columns) . "</p>";
        
        // Check if profile_image column exists
        if (in_array('profile_image', $columns)) {
            echo "<p style='color: green;'>✅ profile_image column exists</p>";
        } else {
            echo "<p style='color: red;'>❌ profile_image column missing</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Table does not exist or cannot be accessed</p>";
    }
}

echo "<h3>5. Profile Edit Links</h3>";
echo "<p><a href='edit_universal_profile.php?type=admin&id=1' target='_blank'>Edit Admin Profile (ID: 1)</a></p>";
echo "<p><a href='edit_universal_profile.php?type=staff&id=1' target='_blank'>Edit Staff Profile (ID: 1)</a></p>";
echo "<p><a href='edit_universal_profile.php?type=distributor&id=1' target='_blank'>Edit Distributor Profile (ID: 1)</a></p>";

echo "<h3>6. File Permissions</h3>";
$files_to_check = [
    'edit_universal_profile.php',
    'update_universal_profile.php',
    'uploads/'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $readable = is_readable($file) ? "✅" : "❌";
        $writable = is_writable($file) ? "✅" : "❌";
        echo "<p>$file: Readable $readable, Writable $writable</p>";
    } else {
        echo "<p style='color: red;'>❌ $file does not exist</p>";
    }
}

if ($conn) {
    $conn->close();
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #410101; }
h3 { color: #666; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
h4 { color: #888; }
</style>
