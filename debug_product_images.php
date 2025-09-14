<?php
// Debug product images issue
session_start();
$_SESSION['admin_id'] = 1; // Set for testing

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>🔍 Product Images Debug</h2>";

// 1. Check products table structure
echo "<h3>1. Products Table Structure</h3>";
$result = $conn->query("DESCRIBE products");
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr style='background: #f0f0f0;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>" . ($row['Default'] ?? 'NULL') . "</td></tr>";
}
echo "</table>";

// 2. Check sample product data
echo "<h3>2. Sample Product Data</h3>";
$result = $conn->query("SELECT product_id, product_name, product_image FROM products LIMIT 5");
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Product Image (Path)</th><th>File Exists</th></tr>";
while ($row = $result->fetch_assoc()) {
    $hasFile = !empty($row['product_image']) && file_exists('uploads/' . $row['product_image']);
    $imageStatus = $hasFile ? 'File ✅' : 'No File ❌';
    
    echo "<tr>";
    echo "<td>{$row['product_id']}</td>";
    echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['product_image']) . "</td>";
    echo "<td>$imageStatus</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Test image fetching methods
echo "<h3>3. Test Image Fetching Methods</h3>";

// Get first product for testing
$result = $conn->query("SELECT * FROM products LIMIT 1");
$testProduct = $result->fetch_assoc();

if ($testProduct) {
    echo "<h4>Testing with Product: " . htmlspecialchars($testProduct['product_name']) . "</h4>";
    
    // Since we only have file path method, test that
    if (!empty($testProduct['product_image'])) {
        $filePath = 'uploads/' . $testProduct['product_image'];
        if (file_exists($filePath)) {
            echo "<p>✅ <strong>File Path Method:</strong> Found file at '$filePath'</p>";
            echo "<img src='$filePath' style='max-width: 100px; max-height: 100px; border: 1px solid #ccc;' alt='File Image'>";
        } else {
            echo "<p>❌ <strong>File Path Method:</strong> File '$filePath' does not exist</p>";
        }
    } else {
        echo "<p>❌ <strong>File Path Method:</strong> No file path found</p>";
    }
} else {
    echo "<p>No products found in database</p>";
}

// 4. Check uploads directory
echo "<h3>4. Uploads Directory Check</h3>";
if (is_dir('uploads')) {
    $files = scandir('uploads');
    $imageFiles = array_filter($files, function($file) {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
    });
    
    echo "<p>✅ Uploads directory exists</p>";
    echo "<p>Image files found: " . count($imageFiles) . "</p>";
    
    if (count($imageFiles) > 0) {
        echo "<ul>";
        foreach (array_slice($imageFiles, 0, 5) as $file) {
            echo "<li>$file</li>";
        }
        if (count($imageFiles) > 5) {
            echo "<li>... and " . (count($imageFiles) - 5) . " more</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p>❌ Uploads directory does not exist</p>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #410101; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f0f0f0; }
</style>

<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
    <h3>Quick Fix Options:</h3>
    <ul>
        <li><a href="trial_view.php" target="_blank">Test trial_view.php</a></li>
        <li><a href="fetch_product_photos.php?action=list" target="_blank">Test fetch_product_photos.php</a></li>
        <li><a href="test_product_photos.html" target="_blank">Test product photos page</a></li>
    </ul>
</div>
