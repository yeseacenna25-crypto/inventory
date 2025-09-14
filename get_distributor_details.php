<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if distributor_id is provided
if (!isset($_GET['distributor_id']) || empty($_GET['distributor_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Distributor ID is required']);
    exit();
}

$distributor_id = intval($_GET['distributor_id']);

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Fetch distributor details
$stmt = $conn->prepare("SELECT distributor_id, distrib_fname, distrib_mname, distrib_lname, distrib_extension, distrib_gender, 
                              distrib_birthday, distrib_age, distrib_civil_status, distrib_address, distrib_outlet, distrib_contact_number, 
                              distrib_email, distrib_username, created_at 
                       FROM distributor_signup 
                       WHERE distributor_id = ?");
$stmt->bind_param("i", $distributor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Distributor not found']);
    exit();
}

$distributor = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Return distributor details
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'distributor' => $distributor
]);
?>
