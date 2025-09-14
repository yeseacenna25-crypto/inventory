<?php
if (!isset($_SESSION)) {
    session_start();
}
if (!isset($_SESSION['staff_id'])) {
    header('Location: staff_login.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$staff_id = $_SESSION['staff_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_fname = mysqli_real_escape_string($conn, $_POST['staff_fname'] ?? '');
    $staff_mname = mysqli_real_escape_string($conn, $_POST['staff_mname'] ?? '');
    $staff_lname = mysqli_real_escape_string($conn, $_POST['staff_lname'] ?? '');
    $profile_image = $_FILES['staff_profile_image']['name'] ?? '';
    $profile_image_tmp = $_FILES['staff_profile_image']['tmp_name'] ?? '';
    $profile_image_db = '';

    if (!empty($profile_image) && is_uploaded_file($profile_image_tmp)) {
        $target_dir = 'uploads/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $profile_image_db = time() . '_' . basename($profile_image);
        $profile_image_path = $target_dir . $profile_image_db;
        move_uploaded_file($profile_image_tmp, $profile_image_path);
        $stmt = $conn->prepare("UPDATE staff_signup SET staff_fname=?, staff_mname=?, staff_lname=?, staff_profile_image=? WHERE staff_id=?");
        $stmt->bind_param("ssssi", $staff_fname, $staff_mname, $staff_lname, $profile_image_db, $staff_id);
    } else {
        $stmt = $conn->prepare("UPDATE staff_signup SET staff_fname=?, staff_mname=?, staff_lname=? WHERE staff_id=?");
        $stmt->bind_param("sssi", $staff_fname, $staff_mname, $staff_lname, $staff_id);
    }
    $stmt->execute();
    $stmt->close();
    // Redirect back to edit profile after update
    header('Location: s_edit_profile.php?success=3');
    exit();
}
$conn->close();
?>
