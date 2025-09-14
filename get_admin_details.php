<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if admin_id is provided
if (!isset($_GET['admin_id']) || empty($_GET['admin_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Admin ID is required']);
    exit();
}

$admin_id = intval($_GET['admin_id']);

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Fetch admin details
// Use correct column names for admin_signup table
$stmt = $conn->prepare("SELECT admin_id, admin_fname, admin_mname, admin_lname, admin_extension,    admin_gender, admin_birthday, admin_age, admin_civil_status, admin_address, admin_outlet, admin_contact_number, admin_email, admin_username, admin_role, created_at 
                       FROM admin_signup 
                       WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Admin not found']);
    exit();
}

$admin = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Return admin details
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'admin' => $admin
]);
?>
