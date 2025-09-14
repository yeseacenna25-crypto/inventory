<?php
// Include your database connection file
require_once 'connect_orders_products.php';

// Get distributor ID from request
$distributor_id = isset($_GET['distributor_id']) ? intval($_GET['distributor_id']) : 0;

if ($distributor_id <= 0) {
	echo json_encode(['error' => 'Invalid distributor ID']);
	exit;
}

// Query to get distributor points and owner
$query = "SELECT points, owner_id FROM distributors WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $distributor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
	echo json_encode([
		'distributor_id' => $distributor_id,
		'points' => $row['points'],
		'owner_id' => $row['owner_id']
	]);
} else {
	echo json_encode(['error' => 'Distributor not found']);
}

$stmt->close();
$conn->close();
?>
