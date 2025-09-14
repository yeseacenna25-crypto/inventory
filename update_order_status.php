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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['order_id']) || !isset($input['status'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    $order_id = intval($input['order_id']);
    $new_status = trim($input['status']);
    
    // Validate status
    $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }
    
    // Check if order exists
    $check_stmt = $conn->prepare("SELECT order_id, status FROM orders WHERE order_id = ?");
    $check_stmt->bind_param("i", $order_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $order = $check_result->fetch_assoc();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    $old_status = $order['status'];
    
    // Simple status update without stock changes
    $update_stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?");
    $update_stmt->bind_param("si", $new_status, $order_id);
    $update_stmt->execute();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Order status updated successfully',
        'old_status' => $old_status,
        'new_status' => $new_status
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to update order status: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>
