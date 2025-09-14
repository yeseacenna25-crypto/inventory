<?php
// API: fetch_distributors.php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$result = $conn->query("SELECT distributor_id, CONCAT_WS(' ', distrib_fname, distrib_mname, distrib_lname, distrib_extension) AS distributor_name, distrib_contact_number, distrib_email, distrib_address FROM distributor_signup");
$distributors = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $distributors[] = $row;
    }
}
$conn->close();

echo json_encode([
    'success' => true,
    'data' => $distributors
]);
?>
