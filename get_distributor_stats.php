<?php
header('Content-Type: application/json');

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$period = $_GET['period'] ?? 'month';

// Build the date condition based on the period
$dateCondition = '';
switch ($period) {
    case 'today':
        $dateCondition = "AND DATE(orders.created_at) = CURDATE()";
        break;
    case 'week':
        $dateCondition = "AND YEARWEEK(orders.created_at, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'month':
        $dateCondition = "AND MONTH(orders.created_at) = MONTH(CURDATE()) AND YEAR(orders.created_at) = YEAR(CURDATE())";
        break;
    case 'year':
        $dateCondition = "AND YEAR(orders.created_at) = YEAR(CURDATE())";
        break;
    default:
        $dateCondition = "AND MONTH(orders.created_at) = MONTH(CURDATE()) AND YEAR(orders.created_at) = YEAR(CURDATE())";
}

// Query to get top distributors based on order count for the specified period
$query = "SELECT 
            ds.distrib_fname, 
            ds.distrib_lname, 
            ds.distrib_outlet,
            COUNT(o.order_id) as order_count
          FROM distributor_signup ds
          LEFT JOIN orders o ON ds.distributor_id = o.distributor_id $dateCondition
          GROUP BY ds.distributor_id, ds.distrib_fname, ds.distrib_lname, ds.distrib_outlet
          ORDER BY order_count DESC
          LIMIT 3";

$result = $conn->query($query);

if ($result) {
    $distributors = [];
    while ($row = $result->fetch_assoc()) {
        $distributors[] = [
            'name' => $row['distrib_fname'] . ' ' . $row['distrib_lname'],
            'outlet' => $row['distrib_outlet'],
            'order_count' => (int)$row['order_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'distributors' => $distributors
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $conn->error
    ]);
}

$conn->close();
?>
