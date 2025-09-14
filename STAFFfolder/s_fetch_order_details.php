<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if order_id is provided
if (!isset($_GET['id']) && !isset($_GET['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

$order_id = intval($_GET['id'] ?? $_GET['order_id']);

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Fetch order details
    $order_stmt = $conn->prepare("
        SELECT o.order_id, o.customer_name, o.customer_contact, o.customer_address, 
               o.total_amount, o.status, o.created_at, o.updated_at, o.points, o.order_notes,
               CONCAT(a.staff_fname, ' ', IFNULL(a.staff_mname, ''), ' ', a.staff_lname) as created_by_name
        FROM orders o 
        LEFT JOIN staff_signup a ON o.created_by = a.staff_id 
        WHERE o.order_id = ?
    ");
    
    $order_stmt->bind_param("i", $order_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    $order = $order_result->fetch_assoc();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Fetch order items
    $items_stmt = $conn->prepare("
        SELECT oi.order_item_id, oi.product_id, oi.product_name, oi.quantity, 
               oi.unit_price, oi.total_price, p.quantity as current_stock
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.order_item_id
    ");
    
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $items = [];
    while ($row = $items_result->fetch_assoc()) {
        $items[] = [
            'order_item_id' => $row['order_item_id'],
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'unit_price' => number_format($row['unit_price'], 2),
            'unit_price_raw' => $row['unit_price'],
            'total_price' => number_format($row['total_price'], 2),
            'total_price_raw' => $row['total_price'],
            'current_stock' => $row['current_stock'] ?: 0
        ];
    }
    
    $response = [
        'success' => true,
        'order' => [
            'order_id' => $order['order_id'],
            'customer_name' => $order['customer_name'],
            'customer_contact' => $order['customer_contact'],
            'customer_address' => $order['customer_address'],
            'total_amount' => number_format($order['total_amount'], 2),
            'total_amount_raw' => $order['total_amount'],
            'status' => $order['status'],
            'points' => isset($order['points']) ? intval($order['points']) : 0,
            'order_notes' => $order['order_notes'] ?: '',
            'created_by' => $order['created_by_name'] ?: 'Unknown',
            'created_at' => date('M d, Y g:i A', strtotime($order['created_at'])),
            'updated_at' => date('M d, Y g:i A', strtotime($order['updated_at']))
        ],
        'items' => $items
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch order details: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
