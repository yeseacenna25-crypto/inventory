<?php

session_start();
// Allow both admin and staff to fetch details
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['staff_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if staff_id is provided
if (!isset($_GET['staff_id']) || empty($_GET['staff_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
    exit();
}

$staff_id = intval($_GET['staff_id']);

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Fetch staff details
// Use correct column names for staff_signup table
$stmt = $conn->prepare("SELECT staff_id, staff_fname, staff_mname, staff_lname, staff_extension, staff_gender, 
                  staff_birthday, staff_age, staff_civil_status, staff_address, staff_outlet, staff_contact_number, 
                  staff_email, staff_username, staff_role, created_at 
              FROM staff_signup 
              WHERE staff_id = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Staff not found']);
    exit();
}

$staff = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Return staff details
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'staff' => $staff
]);
?>
