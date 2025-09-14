<?php
// This script fetches and displays the hashed password for a given admin_username
$admin_username = 'your_admin_username'; // Change to the username you want to check

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT admin_password FROM admin_signup WHERE admin_username = ?");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$stmt->bind_result($hashedPassword);
if ($stmt->fetch()) {
    echo "Hashed password for $admin_username: $hashedPassword";
} else {
    echo "No such user found.";
}
$stmt->close();
$conn->close();
?>
