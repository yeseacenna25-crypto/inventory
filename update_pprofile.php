<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$first_name = trim($_POST['first_name']);
$middle_name = trim($_POST['middle_name']);
$last_name = trim($_POST['last_name']);
$admin_id = $_SESSION['admin_id'];

// Handle image upload if any
$profileImageName = null;
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
    $profileImageName = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
    $targetPath = 'uploads/' . $profileImageName;
    move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath);
    
    // Optional: delete previous image if stored
    $result = $conn->query("SELECT profile_image FROM admin_signup WHERE admin_id = $admin_id");
    if ($result && $row = $result->fetch_assoc()) {
        $oldImage = $row['profile_image'];
        if ($oldImage && file_exists('uploads/' . $oldImage)) {
            unlink('uploads/' . $oldImage);
        }
    }
    
    // Update with image
    $stmt = $conn->prepare("UPDATE admin_signup SET first_name=?, middle_name=?, last_name=?, profile_image=? WHERE admin_id=?");
    $stmt->bind_param("ssssi", $first_name, $middle_name, $last_name, $profileImageName, $admin_id);
} else {
    // No image update
    $stmt = $conn->prepare("UPDATE admin_signup SET first_name=?, middle_name=?, last_name=? WHERE admin_id=?");
    $stmt->bind_param("sssi", $first_name, $middle_name, $last_name, $admin_id);
}

if ($stmt->execute()) {
    $_SESSION['update_success'] = "Profile updated successfully!";
    header("Location: admin_dashboard.php");
} else {
    echo "Error updating profile: " . $stmt->error;
}

$stmt->close();
$conn->close();
