<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Redirect to the new universal edit profile system
header("Location: edit_universal_profile.php?type=admin&id=" . $_SESSION['admin_id']);
exit();
?>