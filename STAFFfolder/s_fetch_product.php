<?php
header('Content-Type: application/json');
session_start();

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

try {
    // Get search parameter if provided
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Base query to fetch products (removed 'image' column since it doesn't exist)
    $sql = "SELECT product_id, product_name, category, quantity, price, description, product_image 
            FROM products 
            WHERE quantity > 0"; // Only show products with stock
    
    // Add search condition if search term is provided
    if (!empty($search)) {
        $sql .= " AND (product_name LIKE ? OR category LIKE ? OR description LIKE ?)";
    }
    
    $sql .= " ORDER BY product_name ASC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($search)) {
        $searchTerm = "%$search%";
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Ensure price is properly formatted
        $price_raw = floatval($row['price']);
        
        // Handle product image
        $image_url = ''; // Default empty
        
        // Check if product has an image
        if (!empty($row['product_image'])) {
            $file_path = 'uploads/' . $row['product_image'];
            if (file_exists($file_path)) {
                $image_url = $file_path;
            }
        }
        
        $products[] = [
            'id' => $row['product_id'],
            'name' => $row['product_name'],
            'category' => $row['category'],
            'quantity' => $row['quantity'],
            'price' => number_format($price_raw, 2),
            'price_raw' => $price_raw,
            'description' => $row['description'],
            'image' => $image_url // Will be empty string if no valid image
        ];
    }
    
    echo json_encode(['success' => true, 'products' => $products]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch products: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
