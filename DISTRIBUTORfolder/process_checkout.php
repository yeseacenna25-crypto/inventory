<?php
// Suppress errors and clean output
ini_set('display_errors', 0);
error_reporting(0);
ob_start();
session_start();
header('Content-Type: application/json');

// Check if user is logged in (distributor)
if (!isset($_SESSION['distributor_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    ob_end_clean();
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    ob_end_clean();
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    ob_end_clean();
    exit();
}
// Debug: Output current database and orders table columns
$db_result = $conn->query("SELECT DATABASE() AS db");
$db_name = $db_result ? $db_result->fetch_assoc()['db'] : 'unknown';
$columns_result = $conn->query("SHOW COLUMNS FROM orders");
$columns = [];
if ($columns_result) {
    while ($row = $columns_result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
}
file_put_contents(__DIR__ . '/debug_db_orders.txt', "DB: $db_name\nColumns: " . implode(', ', $columns));

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    ob_end_clean();
    exit();
    }
    
    // Validate required fields
    if (!isset($input['customer_info']) || !isset($input['items']) || empty($input['items'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    ob_end_clean();
    exit();
    }
    
    $customer_info = $input['customer_info'];
    $items = $input['items'];
    // Get order notes from input
    $order_notes = isset($input['order_notes']) ? trim($input['order_notes']) : '';
    // Debug: Log received order_notes
    file_put_contents(__DIR__ . '/debug_order_notes.txt', "Received order_notes: " . $order_notes . "\n", FILE_APPEND);
    
    // Validate customer information
    if (empty($customer_info['name']) || empty($customer_info['contact']) || empty($customer_info['address'])) {
    echo json_encode(['success' => false, 'message' => 'Customer information is incomplete']);
    ob_end_clean();
    exit();
    }
    
    $customer_name = trim($customer_info['name']);
    $customer_contact = trim($customer_info['contact']);
    $customer_address = trim($customer_info['address']);
    $total_amount = 0;
    
    // Calculate total and validate items
    foreach ($items as $item) {
        if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['price']) || !isset($item['name'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid item data']);
            ob_end_clean();
            exit();
        }
        
        $quantity = intval($item['quantity']);
        $unit_price = floatval($item['price']);
        
        if ($quantity <= 0 || $unit_price < 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid quantity or price']);
            ob_end_clean();
            exit();
        }
        
        $total_amount += $quantity * $unit_price;
    }
    
    // Add 10% tax as handling fee
    $handling_fee = round($total_amount * 0.10, 2);
    $final_total = $total_amount + $handling_fee;

    // Calculate points (1 point per product quantity)
    $points = 0;
    foreach ($items as $item) {
        $points += intval($item['quantity']);
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Get the user ID (either distributor or admin)
    $created_by = isset($_SESSION['distributor_id']) ? $_SESSION['distributor_id'] : $_SESSION['admin_id'];
    $user_type = isset($_SESSION['distributor_id']) ? 'distributor' : 'admin';
    
    // Insert order with additional fields including points and order_notes
    $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_contact, customer_address, customer_outlet, total_amount, handling_fee, final_total, points, status, order_notes, created_by, distributor_id, user_type, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        $db_result = $conn->query("SELECT DATABASE() AS db");
        $db_name = $db_result ? $db_result->fetch_assoc()['db'] : 'unknown';
        $columns_result = $conn->query("SHOW COLUMNS FROM orders");
        $columns = [];
        if ($columns_result) {
            while ($row = $columns_result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
        }
        $error_message = "Failed to prepare order insert: " . $conn->error;
        file_put_contents(__DIR__ . '/debug_db_orders.txt', "DB: $db_name\nColumns: " . implode(', ', $columns) . "\nError: $error_message");
        throw new Exception($error_message);
    }

    $status = 'pending';
    // Make sure all variables are set
    if (!isset($customer_outlet)) $customer_outlet = '';
    if (!isset($distributor_id)) $distributor_id = $created_by;
    if (!isset($points)) $points = 0;
    $stmt->bind_param(
        "ssssdddisssss",
        $customer_name, $customer_contact, $customer_address, $customer_outlet,
        $total_amount, $handling_fee, $final_total, $points, $status,
        $order_notes, $created_by, $distributor_id, $user_type
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to create order: " . $stmt->error);
    }

    $order_id = $conn->insert_id;
    $stmt->close();
    
    // Insert order items and update product quantities
    $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$item_stmt) {
        throw new Exception("Failed to prepare order item insert: " . $conn->error);
    }
    
    // Also prepare stock update statement
    $stock_stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ? AND quantity >= ?");
    if (!$stock_stmt) {
        throw new Exception("Failed to prepare stock update: " . $conn->error);
    }
    
    foreach ($items as $item) {
        $product_id = intval($item['id']);
        $product_name = $item['name'];
        $quantity = intval($item['quantity']);
        $unit_price = floatval($item['price']);
        $item_total = $quantity * $unit_price;
        
        // Insert order item
        $item_stmt->bind_param("iisidd", $order_id, $product_id, $product_name, $quantity, $unit_price, $item_total);
        if (!$item_stmt->execute()) {
            throw new Exception("Failed to insert order item: " . $item_stmt->error);
        }
        
        // Update product stock
        $stock_stmt->bind_param("iii", $quantity, $product_id, $quantity);
        if (!$stock_stmt->execute()) {
            throw new Exception("Failed to update product stock for product ID: " . $product_id);
        }
        
        // Check if stock update was successful (affected rows > 0)
        if ($stock_stmt->affected_rows === 0) {
            // Check if product exists and has sufficient stock
            $check_stmt = $conn->prepare("SELECT quantity FROM products WHERE product_id = ?");
            $check_stmt->bind_param("i", $product_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                $check_stmt->close();
                ob_end_clean();
                throw new Exception("Product with ID $product_id not found");
            } else {
                $row = $result->fetch_assoc();
                $available_stock = $row['quantity'];
                $check_stmt->close();
                ob_end_clean();
                throw new Exception("Insufficient stock for {$product_name}. Available: {$available_stock}, Required: {$quantity}");
            }
        }
    }
    
    $item_stmt->close();
    $stock_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    // Log the successful order
    error_log("Order created successfully - ID: $order_id, Customer: $customer_name, Total: $final_total, Created by: $user_type ID $created_by");
    
    // After committing the order, remove checked out products from the distributor's cart
    if (isset($_SESSION['distributor_id'])) {
        $distributor_id = intval($_SESSION['distributor_id']);
        $delete_sql = "DELETE FROM cart WHERE distributor_id = $distributor_id";
        $result = $conn->query($delete_sql);
        if ($result) {
            error_log("Direct SQL cart cleanup: $delete_sql");
            error_log("All cart items deleted for distributor_id: $distributor_id, affected_rows: " . $conn->affected_rows);
        } else {
            error_log("Direct SQL cart cleanup failed: $delete_sql, error: " . $conn->error);
        }
    } else {
        error_log("Distributor session not set. Session: " . print_r($_SESSION, true));
    }

    // Debug log for product subtraction
    foreach ($items as $item) {
        $product_id = intval($item['id']);
        $quantity = intval($item['quantity']);
        $result = $conn->query("SELECT quantity FROM products WHERE product_id = $product_id");
        $row = $result ? $result->fetch_assoc() : null;
        $current_qty = $row ? $row['quantity'] : 'N/A';
        if ($row) {
            error_log("Product stock after checkout for product_id: $product_id, subtracted: $quantity, remaining: $current_qty");
        } else {
            error_log("Product not found for product_id: $product_id");
        }
    }
    
    ob_end_clean();
    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully!',
        'order_id' => $order_id,
        'order_details' => [
            'customer_name' => $customer_name,
            'customer_contact' => $customer_contact,
            'total_items' => count($items),
            'subtotal' => $total_amount,
            'handling_fee' => $handling_fee,
            'final_total' => $final_total,
            'status' => $status,
            'order_date' => date('Y-m-d H:i:s'),
            'order_notes' => $order_notes
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    error_log("Checkout error: " . $e->getMessage());
    
    http_response_code(500);
    ob_end_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to process order: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>
