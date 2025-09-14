<?php
session_start();

// Set admin session for testing
$_SESSION['admin_id'] = 1;

// Test the fetch_products.php endpoint
echo "<h2>Testing fetch_products.php API</h2>";

// Test 1: Direct database query
echo "<h3>1. Direct Database Test</h3>";
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT product_id, product_name, category, quantity, price, description, product_image FROM products LIMIT 5";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Quantity</th><th>Price</th><th>Image</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['product_id'] . "</td>";
        echo "<td>" . $row['product_name'] . "</td>";
        echo "<td>" . $row['category'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . $row['price'] . "</td>";
        echo "<td>" . $row['product_image'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No products found in database";
}

// Test 2: API call simulation
echo "<h3>2. API Response Test</h3>";
$url = 'http://localhost/nims/fetch_products.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$conn->close();
?>
