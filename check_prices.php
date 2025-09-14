<?php
// Quick database check for price issues
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Price Check</h2>";

// Check for NULL or invalid prices
$sql = "SELECT product_id, product_name, price, 
               CASE 
                   WHEN price IS NULL THEN 'NULL'
                   WHEN price = 0 THEN 'ZERO'
                   WHEN price < 0 THEN 'NEGATIVE'
                   ELSE 'VALID'
               END as price_status
        FROM products 
        WHERE quantity > 0
        ORDER BY price_status, product_name";

$result = $conn->query($sql);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Status</th></tr>";

$issues = 0;
while($row = $result->fetch_assoc()) {
    $statusClass = $row['price_status'] === 'VALID' ? 'style="background-color: #d4edda;"' : 'style="background-color: #f8d7da;"';
    if ($row['price_status'] !== 'VALID') $issues++;
    
    echo "<tr $statusClass>";
    echo "<td>" . $row['product_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
    echo "<td>" . $row['price'] . "</td>";
    echo "<td>" . $row['price_status'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>Issues found: $issues</strong></p>";

$conn->close();
?>
