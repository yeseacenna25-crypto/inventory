<?php
echo "<h2>Testing Distributor Information Fetch</h2>";

$conn = new mysqli('localhost', 'root', '', 'inventory_negrita');
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

echo "<h3>Distributor Signup Table Structure:</h3>";
$result = $conn->query('DESCRIBE distributor_signup');
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Table does not exist or error: " . $conn->error;
}

echo "<h3>Sample Distributor Data:</h3>";
$result = $conn->query('SELECT distributor_id, distrib_fname, distrib_lname, distrib_contact, distrib_address FROM distributor_signup LIMIT 3');
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Contact</th><th>Address</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['distributor_id']}</td>";
        echo "<td>{$row['distrib_fname']}</td>";
        echo "<td>{$row['distrib_lname']}</td>";
        echo "<td>{$row['distrib_contact']}</td>";
        echo "<td>" . htmlspecialchars(substr($row['distrib_address'] ?? '', 0, 50)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No distributor data found or error: " . $conn->error;
}

$conn->close();
?>
