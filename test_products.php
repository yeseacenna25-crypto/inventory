<?php
// Simple test for fetch_products.php
header('Content-Type: text/html');

echo "<h2>Testing fetch_products.php</h2>";

// Test the API endpoint
$url = 'http://localhost/nims/fetch_products.php';
$context = stream_context_create([
    'http' => [
        'header' => 'Cookie: ' . $_SERVER['HTTP_COOKIE'] ?? ''
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "<p style='color: red;'>Failed to fetch from API</p>";
} else {
    echo "<h3>Raw Response:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $data = json_decode($response, true);
    
    if ($data === null) {
        echo "<p style='color: red;'>Invalid JSON response</p>";
    } else {
        echo "<h3>Parsed Data:</h3>";
        echo "<pre>" . print_r($data, true) . "</pre>";
        
        if (isset($data['success']) && $data['success']) {
            echo "<p style='color: green;'>✓ API working correctly</p>";
            echo "<p>Found " . count($data['products']) . " products</p>";
            
            if (!empty($data['products'])) {
                echo "<h3>Sample Products:</h3>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Quantity</th></tr>";
                
                foreach (array_slice($data['products'], 0, 5) as $product) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($product['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($product['price']) . "</td>";
                    echo "<td>" . htmlspecialchars($product['quantity']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p style='color: red;'>✗ API returned error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    }
}

// Also test direct database connection
echo "<h3>Direct Database Test:</h3>";
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "<p style='color: orange;'>⚠ Not logged in - this might cause API issues</p>";
    echo "<p>Please <a href='admin_login.php'>login</a> first</p>";
} else {
    echo "<p style='color: green;'>✓ Logged in as admin_id: " . $_SESSION['admin_id'] . "</p>";
}

try {
    $conn = new mysqli("localhost", "root", "", "inventory_negrita");
    if ($conn->connect_error) {
        echo "<p style='color: red;'>✗ Database connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✓ Database connected</p>";
        
        // Test products table
        $result = $conn->query("SELECT COUNT(*) as count FROM products");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p>Total products in database: " . $row['count'] . "</p>";
            
            if ($row['count'] == 0) {
                echo "<p style='color: orange;'>⚠ No products found. You need to add some products first.</p>";
                echo "<p><a href='add_product.php'>Add a product</a> or <a href='trial_add.php'>Add via trial page</a></p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Error querying products table: " . $conn->error . "</p>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Fetch Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <p><a href="add_order.php">← Back to Add Order</a></p>
</body>
</html>
