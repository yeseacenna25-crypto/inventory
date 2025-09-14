    <?php
// Database structure checker
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Structure Analysis</h2>";

$tables = ['admin_signup', 'staff_signup', 'distributor_signup'];
foreach ($tables as $table) {
    echo "<h3>$table table structure:</h3>";
    $result = $conn->query("SHOW COLUMNS FROM $table");
    if ($result) {
        echo "<ul>";
        $has_profile_image = false;
        while ($row = $result->fetch_assoc()) {
            echo "<li>{$row['Field']} - {$row['Type']}</li>";
            if ($row['Field'] === 'profile_image') {
                $has_profile_image = true;
            }
        }
        echo "</ul>";
        
        if (!$has_profile_image) {
            echo "<p style='color: red;'>❌ Missing profile_image column in $table</p>";
        } else {
            echo "<p style='color: green;'>✅ profile_image column exists in $table</p>";
        }
    } else {
        echo "<p style='color: red;'>Error checking $table: " . $conn->error . "</p>";
    }
    echo "<hr>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #410101; }
</style>
