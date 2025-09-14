<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h3>Distributor Signup Table Structure:</h3>";
$result = $conn->query("DESCRIBE distributor_signup");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error describing table: " . $conn->error;
}

// Show a row of sample data
echo "<h3>Sample Distributor Data:</h3>";
$result = $conn->query("SELECT * FROM distributor_signup LIMIT 1");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    $row = $result->fetch_assoc();
    
    // Print header row
    echo "<tr>";
    foreach (array_keys($row) as $key) {
        echo "<th>$key</th>";
    }
    echo "</tr>";
    
    // Print data row
    echo "<tr>";
    foreach ($row as $value) {
        echo "<td>" . ($value ?? 'NULL') . "</td>";
    }
    echo "</tr>";
    echo "</table>";
} else {
    echo "No distributor data found or error: " . $conn->error;
}

$conn->close();
?>