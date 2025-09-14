<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['distributor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID missing']);
    exit();
}


$order_id = intval($input['order_id']);
$distributor_id = intval($_SESSION['distributor_id']);

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

// Debug: fetch order info before update
$debug_sql = "SELECT status, distributor_id FROM orders WHERE order_id = ?";
$debug_stmt = $conn->prepare($debug_sql);
$debug_stmt->bind_param("i", $order_id);
$debug_stmt->execute();
$debug_result = $debug_stmt->get_result();
if ($debug_result && $debug_result->num_rows > 0) {
    $debug_row = $debug_result->fetch_assoc();
    error_log("Cancel debug: order_id=$order_id, session_distributor_id=$distributor_id, order_distributor_id={$debug_row['distributor_id']}, order_status={$debug_row['status']}");
} else {
    error_log("Cancel debug: order_id=$order_id not found");
}
$debug_stmt->close();

// Only allow cancelling own pending orders
$stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND distributor_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $order_id, $distributor_id);
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        error_log("Order cancelled: order_id=$order_id, distributor_id=$distributor_id");
        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
    } else {
        $debug_msg = '';
        if (isset($debug_row)) {
            $debug_msg = "Session distributor_id: $distributor_id, Order distributor_id: {$debug_row['distributor_id']}, Order status: {$debug_row['status']}";
        } else {
            $debug_msg = "Order not found.";
        }
        error_log("Cancel failed: order_id=$order_id, distributor_id=$distributor_id, SQL={$stmt->error}, $debug_msg");
        echo json_encode(['success' => false, 'message' => 'Unable to cancel order. Debug: ' . $debug_msg]);
    }
} else {
    error_log("Cancel SQL error: order_id=$order_id, distributor_id=$distributor_id, SQL={$stmt->error}");
    echo json_encode(['success' => false, 'message' => 'SQL error: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
?>
