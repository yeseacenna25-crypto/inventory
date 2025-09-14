<?php
// Simple test to check if products have images
$conn = mysqli_connect('localhost', 'root', '', 'inventory_negrita');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h3>Product Images Test</h3>";
echo "<pre>";

$result = mysqli_query($conn, "SELECT product_id, product_name, product_image FROM products LIMIT 10");

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Product ID: " . $row['product_id'] . "\n";
        echo "Product Name: " . $row['product_name'] . "\n";
        echo "Image Data Type: ";
        
        if (empty($row['product_image'])) {
            echo "EMPTY\n";
        } elseif (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $row['product_image'])) {
            echo "FILENAME: " . $row['product_image'] . "\n";
            // Check if file exists
            $paths = [
                '../uploads/' . $row['product_image'],
                'uploads/' . $row['product_image'],
                $row['product_image']
            ];
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    echo "File exists at: " . $path . "\n";
                    break;
                } else {
                    echo "File not found at: " . $path . "\n";
                }
            }
        } else {
            echo "BINARY DATA (" . strlen($row['product_image']) . " bytes)\n";
            // Show first few bytes
            $header = substr($row['product_image'], 0, 8);
            $hex = bin2hex($header);
            echo "Header (hex): " . $hex . "\n";
            
            // Check image type
            if (substr($row['product_image'], 0, 2) === "\xFF\xD8") {
                echo "Detected as: JPEG\n";
            } elseif ($header === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
                echo "Detected as: PNG\n";
            } else {
                echo "Unknown image format\n";
            }
        }
        echo "-------------------\n";
    }
} else {
    echo "Query failed: " . mysqli_error($conn);
}

echo "</pre>";
mysqli_close($conn);
?>
