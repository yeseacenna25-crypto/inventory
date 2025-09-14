<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['staff_id']) && !isset($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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
    // Get filter parameters
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;
    
    // Add distributor_id filter
    $distributor_id = isset($_GET['distributor_id']) ? intval($_GET['distributor_id']) : 0;
    // Add staff_id filter for staff
    $staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;
    
    // Base SQL for counting total records
    $countSql = "SELECT COUNT(DISTINCT o.order_id) as total FROM orders o";
    
    // Base SQL for fetching records  
    $sql = "SELECT o.order_id, o.customer_name, o.customer_contact, o.customer_address, o.total_amount, o.status, o.created_at, o.updated_at, o.points, o.user_type, 
            CONCAT(a.staff_fname, ' ', IFNULL(a.staff_mname, ''), ' ', a.staff_lname) as created_by_name,
            COALESCE(oi.item_count, 0) as item_count,
            COALESCE(oi.total_quantity, 0) as total_quantity,
            COALESCE(oi.avg_unit_price, 0) as unit_price
            FROM orders o 
            LEFT JOIN staff_signup a ON o.created_by = a.staff_id 
            LEFT JOIN (
                SELECT order_id, COUNT(*) as item_count, SUM(quantity) as total_quantity,
                       AVG(unit_price) as avg_unit_price
                FROM order_items 
                GROUP BY order_id
            ) oi ON o.order_id = oi.order_id";
    
    $conditions = [];
    $params = [];
    $types = "";
    
    if ($distributor_id > 0) {
        $conditions[] = "o.distributor_id = ?";
        $params[] = $distributor_id;
        $types .= "i";
    }
    // Staff can see all orders, do not filter by created_by
    if (!empty($status)) {
        $conditions[] = "o.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    if (!empty($date_from)) {
        $conditions[] = "DATE(o.created_at) >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    if (!empty($date_to)) {
        $conditions[] = "DATE(o.created_at) <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
    if (!empty($conditions)) {
        $whereClause = " WHERE " . implode(" AND ", $conditions);
        $countSql .= $whereClause;
        $sql .= $whereClause;
    }
    
    // Get total count first
    $totalStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $totalStmt->bind_param($types, ...$params);
    }
    $totalStmt->execute();
    $totalResult = $totalStmt->get_result();
    $totalData = $totalResult->fetch_assoc();
    $total = $totalData['total'];
    
    // Add ordering and pagination to main query
    $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            'order_id' => $row['order_id'],
            'customer_name' => $row['customer_name'],
            'customer_contact' => $row['customer_contact'],
            'customer_address' => $row['customer_address'],
            'total_amount' => number_format($row['total_amount'], 2),
            'total_amount_raw' => $row['total_amount'],
            'status' => $row['status'],
            'status_badge' => getStatusBadge($row['status']),
            'created_by' => $row['created_by_name'] ?: 'Unknown',
            'created_at' => date('M d, Y g:i A', strtotime($row['created_at'])),
            'updated_at' => date('M d, Y g:i A', strtotime($row['updated_at'])),
            'points' => isset($row['points']) ? intval($row['points']) : 0,
            'user_type' => isset($row['user_type']) ? $row['user_type'] : 'unknown',
            'item_count' => intval($row['item_count']),
            'total_quantity' => intval($row['total_quantity']),
            'unit_price' => floatval($row['unit_price']),
            'unit_price_formatted' => number_format($row['unit_price'], 2)
        ];
    }
    
    echo json_encode([
        'success' => true, 
        'orders' => $orders,
        'pagination' => [
            'total' => intval($total),
            'page' => intval($page),
            'limit' => intval($limit),
            'totalPages' => ceil($total / $limit)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch orders: ' . $e->getMessage()]);
} finally {
    $conn->close();
}

function getStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-warning">Pending</span>';
        case 'processing':
            return '<span class="badge bg-info">Processing</span>';
        case 'completed':
            return '<span class="badge bg-success">Completed</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Cancelled</span>';
        default:
            return '<span class="badge bg-secondary">Unknown</span>';
    }
}

// Fetch staff name by staff_id (example usage)
function getStaffName($conn, $staff_id) {
    $stmt = $conn->prepare("SELECT staff_fname, staff_mname, staff_lname FROM staff_signup WHERE staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff = $result->fetch_assoc();
    $stmt->close();
    if ($staff) {
        return $staff['staff_fname'] . ' ' . $staff['staff_mname'] . ' ' . $staff['staff_lname'];
    } else {
        return "Staff";
    }
}
?>
