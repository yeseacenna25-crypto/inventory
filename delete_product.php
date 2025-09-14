<?php
// delete_product.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || intval($_GET['id']) <= 0) {
    header('Location: trial_view.php?msg=invalid_id');
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "inventory_negrita");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$product_id = intval($_GET['id']);
// Check if product exists before deleting
$check = $mysqli->prepare("SELECT product_id FROM products WHERE product_id = ?");
$check->bind_param('i', $product_id);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    $check->close();
    header('Location: trial_view.php?msg=not_found');
    exit();
}
$check->close();

$stmt = $mysqli->prepare("DELETE FROM products WHERE product_id = ?");
$stmt->bind_param('i', $product_id);
if ($stmt->execute()) {
    header("Location: trial_view.php?msg=deleted");
    exit();
} else {
    header('Location: trial_view.php?msg=delete_failed');
    exit();
}
?>
