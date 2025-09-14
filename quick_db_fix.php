#!/usr/bin/env php
<?php
/**
 * Quick Database Fix Script
 * Run this script to fix the "Unknown column 'profile_image'" error
 */

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error . "\n");
}

echo "🔧 Starting database structure fix...\n\n";

$fixes_applied = 0;
$errors = 0;

// Fix admin_signup table
echo "Checking admin_signup table...\n";
$result = $conn->query("SHOW COLUMNS FROM admin_signup LIKE 'profile_image'");
if ($result->num_rows == 0) {
    if ($conn->query("ALTER TABLE admin_signup ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL")) {
        echo "✅ Added profile_image column to admin_signup\n";
        $fixes_applied++;
    } else {
        echo "❌ Error: " . $conn->error . "\n";
        $errors++;
    }
} else {
    echo "✅ admin_signup already has profile_image column\n";
}

// Fix staff_signup table
echo "Checking staff_signup table...\n";
$result = $conn->query("SHOW COLUMNS FROM staff_signup LIKE 'profile_image'");
if ($result->num_rows == 0) {
    if ($conn->query("ALTER TABLE staff_signup ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL")) {
        echo "✅ Added profile_image column to staff_signup\n";
        $fixes_applied++;
    } else {
        echo "❌ Error: " . $conn->error . "\n";
        $errors++;
    }
} else {
    echo "✅ staff_signup already has profile_image column\n";
}

// Fix distributor_signup table
echo "Checking distributor_signup table...\n";
$result = $conn->query("SHOW COLUMNS FROM distributor_signup LIKE 'profile_image'");
if ($result->num_rows == 0) {
    if ($conn->query("ALTER TABLE distributor_signup ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL")) {
        echo "✅ Added profile_image column to distributor_signup\n";
        $fixes_applied++;
    } else {
        echo "❌ Error: " . $conn->error . "\n";
        $errors++;
    }
} else {
    echo "✅ distributor_signup already has profile_image column\n";
}

// Create log tables
echo "\nCreating log tables...\n";

$log_tables = [
    'admin_profile_logs',
    'staff_profile_logs', 
    'distributor_profile_logs'
];

foreach ($log_tables as $table) {
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        field_name VARCHAR(100) NOT NULL,
        old_value TEXT,
        new_value TEXT,
        changed_by INT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "✅ Created/verified {$table} table\n";
    } else {
        echo "❌ Error creating {$table}: " . $conn->error . "\n";
        $errors++;
    }
}

$conn->close();

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 Database fix completed!\n";
echo "✅ Fixes applied: {$fixes_applied}\n";
echo "❌ Errors: {$errors}\n";

if ($errors == 0) {
    echo "\n🚀 You can now use the edit and delete functionality!\n";
    echo "📝 Try accessing: edit_universal_profile.php\n";
    echo "🗑️  Try the delete functions in user lists\n";
} else {
    echo "\n⚠️  Some errors occurred. Please check the messages above.\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
?>
