<?php
header('Content-Type: application/json');
session_start();

// Check if user is logged in (admin or distributor)
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['distributor_id'])) {
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

$period = isset($_GET['period']) ? $_GET['period'] : 'month';

// Build query based on period and user type
$whereClause = '';
if (isset($_SESSION['distributor_id'])) {
    $whereClause = " AND distributor_id = " . intval($_SESSION['distributor_id']);
}

switch ($period) {
    case 'today':
        $sql = "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()" . $whereClause;
        break;
    case 'week':
        $sql = "SELECT COUNT(*) as count FROM orders WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)" . $whereClause;
        break;
    case 'month':
        $sql = "SELECT COUNT(*) as count FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())" . $whereClause;
        break;
    case 'year':
        $sql = "SELECT COUNT(*) as count FROM orders WHERE YEAR(created_at) = YEAR(CURRENT_DATE())" . $whereClause;
        break;
    default:
        $sql = "SELECT COUNT(*) as count FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())" . $whereClause;
}

$result = $conn->query($sql);
if ($result) {
    $data = $result->fetch_assoc();
    echo json_encode([
        'success' => true, 
        'count' => number_format($data['count']),
        'period' => $period
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to fetch order statistics'
    ]);
}

$conn->close();
?>
