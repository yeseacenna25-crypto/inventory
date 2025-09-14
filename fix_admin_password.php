<?php
// Run this script ONCE to fix existing admin passwords in the database
// Usage: Set $admin_id and $new_password, then run in browser or CLI

$admin_id = 1; // Change to the admin_id you want to fix
$new_password = 'yournewpassword'; // Change to the correct password

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE admin_signup SET admin_password = ? WHERE admin_id = ?");
$stmt->bind_param("si", $hashed, $admin_id);
if ($stmt->execute()) {
    echo "Password updated successfully.";
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>
