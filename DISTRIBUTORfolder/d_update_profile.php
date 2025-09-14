<?php
if (!isset($_SESSION)) {
    session_start();
}
if (!isset($_SESSION['distributor_id'])) {
    header('Location: distri_login.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$distributor_id = $_SESSION['distributor_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $distrib_fname = mysqli_real_escape_string($conn, $_POST['distrib_fname'] ?? '');
    $distrib_mname = mysqli_real_escape_string($conn, $_POST['distrib_mname'] ?? '');
    $distrib_lname = mysqli_real_escape_string($conn, $_POST['distrib_lname'] ?? '');
    $profile_image = $_FILES['profile_image']['name'] ?? '';
    $profile_image_tmp = $_FILES['profile_image']['tmp_name'] ?? '';
    $profile_image_db = '';

    if (!empty($profile_image) && is_uploaded_file($profile_image_tmp)) {
        $target_dir = 'uploads/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $profile_image_db = time() . '_' . basename($profile_image);
        $profile_image_path = $target_dir . $profile_image_db;
        move_uploaded_file($profile_image_tmp, $profile_image_path);
        $stmt = $conn->prepare("UPDATE distributor_signup SET distrib_fname=?, distrib_mname=?, distrib_lname=?, distrib_profile_image=? WHERE distributor_id=?");
        $stmt->bind_param("ssssi", $distrib_fname, $distrib_mname, $distrib_lname, $profile_image_db, $distributor_id);
    } else {
        $stmt = $conn->prepare("UPDATE distributor_signup SET distrib_fname=?, distrib_mname=?, distrib_lname=? WHERE distributor_id=?");
        $stmt->bind_param("sssi", $distrib_fname, $distrib_mname, $distrib_lname, $distributor_id);
    }
    $stmt->execute();
    $stmt->close();
    // Redirect back to edit profile after update
    header('Location: d_edit_profile.php?success=1');
    exit();
}
$conn->close();
?>
