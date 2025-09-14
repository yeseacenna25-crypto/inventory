<?php
header('Content-Type: application/json');
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

try {
    // Get product ID parameter
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
    $action = isset($_GET['action']) ? $_GET['action'] : 'list';
    
    if ($action === 'single' && $product_id) {
        // Fetch single product with photo
        $stmt = $conn->prepare("SELECT product_id, product_name, description, price, quantity, product_image FROM products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $product = [
                'id' => $row['product_id'],
                'name' => $row['product_name'],
                'description' => $row['description'],
                'price' => number_format(floatval($row['price']), 2),
                'quantity' => $row['quantity'],
                'photo' => getProductPhoto($row)
            ];
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['error' => 'Product not found']);
        }
    } else {
        // Fetch all products with photos
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        $sql = "SELECT product_id, product_name, description, price, quantity, product_image FROM products";
        
        if (!empty($search)) {
            $sql .= " WHERE product_name LIKE ? OR description LIKE ?";
        }
        
        $sql .= " ORDER BY product_id DESC";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id' => $row['product_id'],
                'name' => $row['product_name'],
                'description' => $row['description'],
                'price' => number_format(floatval($row['price']), 2),
                'quantity' => $row['quantity'],
                'photo' => getProductPhoto($row)
            ];
        }
        
        echo json_encode(['success' => true, 'products' => $products]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch products: ' . $e->getMessage()]);
} finally {
    $conn->close();
}

/**
 * Get product photo in base64 format from either BLOB or file path
 */
function getProductPhoto($row) {
    // Display product image from uploads folder if product_image is set
    if (!empty($row['product_image'])) {
        $imagePath = 'uploads/' . basename($row['product_image']);
        if (file_exists($imagePath)) {
            // Return HTML for image
            return '<img src="' . htmlspecialchars($imagePath) . '" class="img-thumbnail" width="100" alt="Product Image">';
        } else {
            // Return placeholder if file not found
            return '<img src="ASSETS/icon.jpg" class="img-thumbnail" width="100" alt="No Image">';
        }
    }
    // No image found
    return '<img src="ASSETS/icon.jpg" class="img-thumbnail" width="100" alt="No Image">';
}
?>