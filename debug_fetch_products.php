<?php
session_start();
$_SESSION['admin_id'] = 1; // Set admin session for testing

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Debug fetch_products.php</h2>";

// Test the actual query from fetch_products.php
$sql = "SELECT product_id, product_name, category, quantity, price, description, product_image 
        FROM products 
        WHERE quantity > 0 
        ORDER BY product_name ASC LIMIT 5";

$result = $conn->query($sql);

echo "<h3>Raw Database Results:</h3>";
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Quantity</th><th>Price</th><th>Description</th><th>Image</th></tr>";
    
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['product_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>" . htmlspecialchars($row['quantity']) . "</td>";
        echo "<td>" . htmlspecialchars($row['price']) . " (raw: " . var_export($row['price'], true) . ")</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "<td>" . htmlspecialchars($row['product_image']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No products found";
}

// Now test the API response
echo "<h3>API Response Test:</h3>";
$products = [];
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $products[] = [
        'id' => $row['product_id'],
        'name' => $row['product_name'],
        'category' => $row['category'],
        'quantity' => $row['quantity'],
        'price' => number_format($row['price'], 2),
        'price_raw' => $row['price'],
        'description' => $row['description'],
        'image' => $row['product_image'] ? 'uploads/' . $row['product_image'] : 'ASSETS/icon.jpg'
    ];
}

echo "<pre>";
echo "JSON Response would be:\n";
echo json_encode(['success' => true, 'products' => $products], JSON_PRETTY_PRINT);
echo "</pre>";

// Check for any NULL or invalid price values
echo "<h3>Price Validation:</h3>";
foreach ($products as $product) {
    echo "Product ID {$product['id']}: price_raw = ";
    var_dump($product['price_raw']);
    echo " (is numeric: " . (is_numeric($product['price_raw']) ? 'YES' : 'NO') . ")<br>";
}

$conn->close();
?>
