<?php
session_start();
$_SESSION['admin_id'] = 1; // Set for testing

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>🖼️ Product Images Test</h2>";

// Test the fixed trial_view.php functionality
echo "<h3>1. Test Product Images from Database</h3>";

$result = $conn->query("SELECT product_id, product_name, product_image, image FROM products LIMIT 5");
if ($result->num_rows > 0) {
    echo "<div style='display: flex; flex-wrap: wrap; gap: 20px;'>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px; width: 200px;'>";
        echo "<h4>" . htmlspecialchars($row['product_name']) . "</h4>";
        
        // Method 1: BLOB data
        if (!empty($row['image'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $row['image']);
            finfo_close($finfo);
            $base64 = base64_encode($row['image']);
            echo "<p><strong>BLOB Image:</strong></p>";
            echo "<img src='data:$mimeType;base64,$base64' style='width: 100%; max-height: 150px; object-fit: cover; border: 1px solid #ccc;' alt='BLOB Image'>";
        }
        // Method 2: File path
        elseif (!empty($row['product_image'])) {
            $filePath = 'uploads/' . $row['product_image'];
            if (file_exists($filePath)) {
                echo "<p><strong>File Image:</strong></p>";
                echo "<img src='$filePath' style='width: 100%; max-height: 150px; object-fit: cover; border: 1px solid #ccc;' alt='File Image'>";
            } else {
                echo "<p style='color: red;'>File not found: $filePath</p>";
            }
        }
        // Method 3: Default
        else {
            echo "<p><strong>Default Image:</strong></p>";
            echo "<img src='ASSETS/icon.jpg' style='width: 100%; max-height: 150px; object-fit: cover; border: 1px solid #ccc;' alt='Default Image'>";
        }
        
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<p>No products found</p>";
}

// Test the API endpoint
echo "<h3>2. Test API Endpoint</h3>";
echo "<div>";
echo "<button onclick='testAPI()' style='padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px;'>Test fetch_products.php API</button>";
echo "<div id='apiResult' style='margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; display: none;'></div>";
echo "</div>";

$conn->close();
?>

<script>
async function testAPI() {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<p>Loading...</p>';
    
    try {
        const response = await fetch('fetch_products.php');
        const data = await response.json();
        
        if (data.success && data.products) {
            let html = '<h4>API Response (First 3 products):</h4>';
            html += '<div style="display: flex; gap: 15px; flex-wrap: wrap;">';
            
            data.products.slice(0, 3).forEach(product => {
                html += `
                    <div style="border: 1px solid #ddd; padding: 10px; border-radius: 5px; width: 180px;">
                        <h5>${product.name}</h5>
                        <img src="${product.image}" style="width: 100%; height: 120px; object-fit: cover;" 
                             alt="${product.name}" onerror="this.src='ASSETS/icon.jpg'">
                        <p><strong>Price:</strong> ₱${product.price}</p>
                        <p><strong>Stock:</strong> ${product.quantity}</p>
                    </div>
                `;
            });
            
            html += '</div>';
            resultDiv.innerHTML = html;
        } else {
            resultDiv.innerHTML = '<p style="color: red;">API Error: ' + (data.error || 'Unknown error') + '</p>';
        }
    } catch (error) {
        resultDiv.innerHTML = '<p style="color: red;">Network Error: ' + error.message + '</p>';
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #410101; }
</style>

<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
    <h3>Test Links:</h3>
    <ul>
        <li><a href="trial_view.php" target="_blank">Test trial_view.php (Fixed version)</a></li>
        <li><a href="fetch_products.php" target="_blank">Test fetch_products.php API</a></li>
        <li><a href="debug_product_images.php" target="_blank">Debug product images</a></li>
        <li><a href="add_order.php" target="_blank">Test add_order.php</a></li>
    </ul>
</div>
