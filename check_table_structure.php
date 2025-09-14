<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get column information for distributor_signup table
$result = $conn->query("SHOW COLUMNS FROM distributor_signup");

echo "<h2>distributor_signup Table Structure</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";

$conn->close();
?>