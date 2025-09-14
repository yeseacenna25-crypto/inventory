<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

// Check if distributor is logged in
if (!isset($_SESSION['distributor_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    $distributor_id = $_SESSION['distributor_id'];
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

    // Debug: Log the distributor_id
    error_log("Fetching orders for distributor ID: " . $distributor_id);

    // First, check if the orders table has distributor_id column
    $check_column = $conn->query("SHOW COLUMNS FROM orders LIKE 'distributor_id'");
    if ($check_column->num_rows == 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'The orders table does not have a distributor_id column. Please run database updates.',
            'debug_info' => 'Missing distributor_id column'
        ]);
        exit();
    }

    $sql = "SELECT order_id, customer_name, customer_contact, customer_address, total_amount, status, created_at, updated_at FROM orders WHERE distributor_id = ?";
    $conditions = [];
    $params = [$distributor_id];
    $types = "i";

    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }
    if (!empty($date_from)) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    if (!empty($date_to)) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
    $sql .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Debug: Log the number of orders found
    $num_rows = $result->num_rows;
    error_log("Found $num_rows orders for distributor ID: $distributor_id");

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Fetch order items and product images
        $order_id = $row['order_id'];
        $items = [];
        $item_sql = "SELECT oi.product_id, oi.product_name, oi.quantity, oi.unit_price, p.product_image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?";
        $item_stmt = $conn->prepare($item_sql);
        $item_stmt->bind_param("i", $order_id);
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();
        while ($item = $item_result->fetch_assoc()) {
            $items[] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'product_image' => $item['product_image'] ? $item['product_image'] : 'ASSETS/icon.jpg'
            ];
        }
        $item_stmt->close();
        $orders[] = [
            'order_id' => $row['order_id'],
            'customer_name' => $row['customer_name'],
            'customer_contact' => $row['customer_contact'],
            'customer_address' => $row['customer_address'],
            'total_amount' => number_format($row['total_amount'], 2),
            'status' => $row['status'],
            'created_at' => date('M d, Y g:i A', strtotime($row['created_at'])),
            'updated_at' => date('M d, Y g:i A', strtotime($row['updated_at'])),
            'items' => $items
        ];
    }
    
    // Debug: Log final result
    error_log("Returning " . count($orders) . " orders for distributor $distributor_id");
    
    echo json_encode([
        'success' => true, 
        'orders' => $orders, 
        'debug_info' => [
            'distributor_id' => $distributor_id,
            'orders_count' => count($orders),
            'sql_executed' => $sql
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch orders: ' . $e->getMessage(),
        'error_line' => $e->getLine(),
        'error_file' => $e->getFile(),
        'error_trace' => $e->getTraceAsString()
    ]);
} finally {
    $conn->close();
}
?>
