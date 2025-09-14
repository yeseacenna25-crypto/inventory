<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

echo "<h2>Database Structure Fix</h2>";

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = [];
$success = [];

try {
    // Check and fix admin_signup table
    echo "<h3>Fixing admin_signup table...</h3>";
    $result = $conn->query("SHOW COLUMNS FROM admin_signup LIKE 'profile_image'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE admin_signup ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL";
        if ($conn->query($sql)) {
            $success[] = "Added profile_image column to admin_signup table";
        } else {
            $errors[] = "Error adding profile_image to admin_signup: " . $conn->error;
        }
    } else {
        $success[] = "profile_image column already exists in admin_signup table";
    }

    // Check and fix staff_signup table
    echo "<h3>Fixing staff_signup table...</h3>";
    $result = $conn->query("SHOW COLUMNS FROM staff_signup LIKE 'profile_image'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE staff_signup ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL";
        if ($conn->query($sql)) {
            $success[] = "Added profile_image column to staff_signup table";
        } else {
            $errors[] = "Error adding profile_image to staff_signup: " . $conn->error;
        }
    } else {
        $success[] = "profile_image column already exists in staff_signup table";
    }

    // Check and fix distributor_signup table
    echo "<h3>Fixing distributor_signup table...</h3>";
    $result = $conn->query("SHOW COLUMNS FROM distributor_signup LIKE 'distrib_profile_image'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE distributor_signup ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL";
        if ($conn->query($sql)) {
            $success[] = "Added profile_image column to distributor_signup table";
        } else {
            $errors[] = "Error adding profile_image to distributor_signup: " . $conn->error;
        }
    } else {
        $success[] = "profile_image column already exists in distributor_signup table";
    }

    // Create log tables
    echo "<h3>Creating log tables...</h3>";
    
    $log_tables = [
        'admin_profile_logs' => "CREATE TABLE IF NOT EXISTS admin_profile_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            field_name VARCHAR(100) NOT NULL,
            old_value TEXT,
            new_value TEXT,
            changed_by INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_changed_by (changed_by),
            INDEX idx_change_date (change_date)
        )",
        'staff_profile_logs' => "CREATE TABLE IF NOT EXISTS staff_profile_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            field_name VARCHAR(100) NOT NULL,
            old_value TEXT,
            new_value TEXT,
            changed_by INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_changed_by (changed_by),
            INDEX idx_change_date (change_date)
        )",
        'distributor_profile_logs' => "CREATE TABLE IF NOT EXISTS distributor_profile_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            field_name VARCHAR(100) NOT NULL,
            old_value TEXT,
            new_value TEXT,
            changed_by INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_changed_by (changed_by),
            INDEX idx_change_date (change_date)
        )"
    ];

    foreach ($log_tables as $table_name => $sql) {
        if ($conn->query($sql)) {
            $success[] = "Created/verified $table_name table";
        } else {
            $errors[] = "Error creating $table_name: " . $conn->error;
        }
    }

} catch (Exception $e) {
    $errors[] = "Exception: " . $e->getMessage();
}

// Display results
echo "<h3>Results:</h3>";

if (!empty($success)) {
    echo "<h4 style='color: green;'>✅ Successful Operations:</h4>";
    echo "<ul>";
    foreach ($success as $msg) {
        echo "<li style='color: green;'>$msg</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<h4 style='color: red;'>❌ Errors:</h4>";
    echo "<ul>";
    foreach ($errors as $msg) {
        echo "<li style='color: red;'>$msg</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green; font-weight: bold;'>🎉 All database fixes applied successfully!</p>";
}

// Verify final structure
echo "<h3>Final Database Structure:</h3>";
$tables = ['admin_signup', 'staff_signup', 'distributor_signup'];
foreach ($tables as $table) {
    echo "<h4>$table columns:</h4>";
    $result = $conn->query("SHOW COLUMNS FROM $table");
    if ($result) {
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            $style = ($row['Field'] === 'profile_image') ? 'color: green; font-weight: bold;' : '';
            echo "<li style='$style'>{$row['Field']} - {$row['Type']}</li>";
        }
        echo "</ul>";
    }
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #410101; }
</style>

<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
    <h3>Next Steps:</h3>
    <ol>
        <li><a href="edit_universal_profile.php?type=admin&id=<?php echo $_SESSION['admin_id']; ?>">Test the edit profile functionality</a></li>
        <li><a href="test_delete_functionality.php">Test the delete functionality</a></li>
        <li><a href="admin_dashboard.php">Return to dashboard</a></li>
    </ol>
</div>
