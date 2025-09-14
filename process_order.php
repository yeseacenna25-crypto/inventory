<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Debug: Log input and session
    file_put_contents('order_debug.log', "\n==== NEW REQUEST ====".PHP_EOL.
        "Session: ".json_encode($_SESSION).PHP_EOL.
        "Raw Input: ".file_get_contents('php://input').PHP_EOL, FILE_APPEND);
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit();
    }
    
    // Validate required fields
    if (!isset($input['customer_name']) || !isset($input['items']) || empty($input['items'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    $customer_name = trim($input['customer_name']);
    $customer_contact = isset($input['customer_contact']) ? trim($input['customer_contact']) : '';
    $customer_address = isset($input['customer_address']) ? trim($input['customer_address']) : '';
    $items = $input['items'];
    $total_amount = 0;
    // Get distributor_id from input if present
    $distributor_id = isset($input['distributor_id']) && !empty($input['distributor_id']) ? intval($input['distributor_id']) : null;
    // If admin is ordering, require distributor_id
    if (isset($_SESSION['admin_id']) && !isset($_SESSION['distributor_id']) && (is_null($distributor_id) || $distributor_id <= 0)) {
        echo json_encode(['success' => false, 'message' => 'Please select a distributor for this order.']);
        exit();
    }
    
    // Validate customer name
    if (empty($customer_name)) {
        echo json_encode(['success' => false, 'message' => 'Customer name is required']);
        exit();
    }
    
    // Calculate total and validate items
    foreach ($items as $item) {
        if (!isset($item['product_id']) || !isset($item['quantity']) || !isset($item['unit_price'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid item data']);
            exit();
        }
        
        $quantity = intval($item['quantity']);
        $unit_price = floatval($item['unit_price']);
        
        if ($quantity <= 0 || $unit_price < 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid quantity or price']);
            exit();
        }
        
        $total_amount += $quantity * $unit_price;
    }
    
    // Start transaction
    $conn->begin_transaction();
    file_put_contents('order_debug.log', "Starting transaction".PHP_EOL, FILE_APPEND);
    
    // Insert order
    // $distributor_id already set above
    // Calculate total points for this order
    $total_points = 0;
    foreach ($items as $item) {
        $total_points += intval($item['quantity']);
    }
    $user_type = 'admin';
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_contact, customer_address, total_amount, created_by, distributor_id, points, user_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        file_put_contents('order_debug.log', "Order insert prepare failed: ".$conn->error.PHP_EOL, FILE_APPEND);
        throw new Exception("Failed to prepare order insert: ".$conn->error);
    }
    $stmt->bind_param("sssdisis", $customer_name, $customer_contact, $customer_address, $total_amount, $_SESSION['admin_id'], $distributor_id, $total_points, $user_type);
    if (!$stmt->execute()) {
        file_put_contents('order_debug.log', "Order insert execute failed: ".$stmt->error.PHP_EOL, FILE_APPEND);
        throw new Exception("Failed to create order: ".$stmt->error);
    }
    $order_id = $conn->insert_id;
    
    // Insert order items and update product quantities
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$item_stmt) {
        file_put_contents('order_debug.log', "Order item insert prepare failed: ".$conn->error.PHP_EOL, FILE_APPEND);
        throw new Exception("Failed to prepare order item insert: ".$conn->error);
    }
    
    foreach ($items as $item) {
        $product_id = intval($item['product_id']);
        $product_name = $item['product_name'];
        $quantity = intval($item['quantity']);
        $unit_price = floatval($item['unit_price']);
        $total_price = $quantity * $unit_price;
        
        // Check if enough stock is available (handle both 'quantity' and 'stock' column names)
        $stock_check = $conn->prepare("SELECT quantity, product_name FROM products WHERE product_id = ?");
        if (!$stock_check) {
            file_put_contents('order_debug.log', "Stock check prepare failed: ".$conn->error.PHP_EOL, FILE_APPEND);
            throw new Exception("Failed to prepare stock check: ".$conn->error);
        }
        $stock_check->bind_param("i", $product_id);
        $stock_check->execute();
        $stock_result = $stock_check->get_result();
        $stock_row = $stock_result->fetch_assoc();
        if (!$stock_row) {
            file_put_contents('order_debug.log', "Product not found: $product_id".PHP_EOL, FILE_APPEND);
            throw new Exception("Product not found: " . $product_name);
        }
        if ($stock_row['quantity'] < $quantity) {
            file_put_contents('order_debug.log', "Insufficient stock for product: $product_id (Available: {$stock_row['quantity']}, Requested: $quantity)".PHP_EOL, FILE_APPEND);
            throw new Exception("Insufficient stock for product: " . $product_name . " (Available: " . $stock_row['quantity'] . ", Requested: " . $quantity . ")");
        }
        $current_product_name = $stock_row['product_name'] ?: $product_name;
        $item_stmt->bind_param("iisidd", $order_id, $product_id, $current_product_name, $quantity, $unit_price, $total_price);
        if (!$item_stmt->execute()) {
            file_put_contents('order_debug.log', "Order item insert execute failed: ".$item_stmt->error.PHP_EOL, FILE_APPEND);
            throw new Exception("Failed to add order item: " . $current_product_name);
        }
        $update_stock_stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ? AND quantity >= ?");
        if (!$update_stock_stmt) {
            file_put_contents('order_debug.log', "Update stock prepare failed: ".$conn->error.PHP_EOL, FILE_APPEND);
            throw new Exception("Failed to prepare update stock: ".$conn->error);
        }
        $update_stock_stmt->bind_param("iii", $quantity, $product_id, $quantity);
        if (!$update_stock_stmt->execute()) {
            file_put_contents('order_debug.log', "Update stock execute failed: ".$update_stock_stmt->error.PHP_EOL, FILE_APPEND);
            throw new Exception("Failed to update stock for product: " . $current_product_name);
        }
    }
    
    // Commit transaction
    $conn->commit();

    // Award points for each product purchased using distributor name from the order
    $total_points = 0;
    foreach ($items as $item) {
        $total_points += intval($item['quantity']);
    }
    if (!is_null($distributor_id) && $distributor_id > 0) {
        $conn->query("UPDATE distributor_signup SET points = points + $total_points WHERE distributor_id = $distributor_id");
        $conn->query("UPDATE distributor_signup SET points = GREATEST(points, 0) WHERE distributor_id = $distributor_id");
    }

    file_put_contents('order_debug.log', "Order created successfully: $order_id".PHP_EOL, FILE_APPEND);
    echo json_encode([
        'success' => true, 
        'message' => 'Order created successfully',
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    file_put_contents('order_debug.log', "Exception: ".$e->getMessage().PHP_EOL, FILE_APPEND);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to create order: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>
