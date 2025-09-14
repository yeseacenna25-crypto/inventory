<?php
if (!isset($_SESSION)) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "inventory_negrita");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch admin name and profile image
$stmt = $conn->prepare("SELECT admin_fname, admin_mname, admin_lname, profile_image FROM admin_signup WHERE admin_id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$fullName = $admin ? ($admin['admin_fname'] . ' ' . $admin['admin_mname'] . ' ' . $admin['admin_lname']) : "Admin";
$profileImage = (!empty($admin['profile_image'])) ? "uploads/" . $admin['profile_image'] : "";

$stmt->close();
$conn->close();
?>

<div class="dashboard_sidebar" id="dashboard_sidebar">
    <!-- Close button for mobile -->
    <button class="sidebar-close-btn">
        <i class="fa fa-times"></i>
    </button>
    
    <h5 class="dashboard_logo fw-bold" id="dashboard_logo">NEGRITA</h5>

    <div class="dashboard_sidebar_user">
        <div class="user-image-wrapper">
            <?php if (!empty($profileImage) && file_exists($profileImage)): ?>
                <img src="<?= htmlspecialchars($profileImage) ?>" alt="User image." id="userImage" />
            <?php else: ?>
                <div class="generic-avatar" id="userImage">
                    <i class="fa fa-user"></i>
                </div>
            <?php endif; ?>
            <a href="edit_profile.php" class="edit-profile-btn" title="Edit Profile">
                <i class="fa fa-pencil"></i>
            </a>
        </div>
        <span id="userName">Welcome! <br> <?= htmlspecialchars($fullName) ?></span>
    </div>

    <div class="dashboard_sidebar_menu">
        <ul class="dashboard_menu_list">

            <li class="liMainMenu">
                <a href="./admin_dashboard.php" class="px-3 py-2 d-inline-block">
                    <i class="fa fa-home" ></i> <span class="menuText"> DASHBOARD </span>
                </a>
            </li>

            <li class="liMainMenu showHideSubMenu">
                <a href="javascript:void(0);" class="px-3 py-2 d-inline-block showHideSubMenu">
                    <i class="fa fa-user-plus showHideSubMenu"></i>
                    <span class="menuText showHideSubMenu"> USERS </span>
                </a>
                <ul class="subMenus">
                    <li><a class="subMenuLink" href="./admin_adduser.php"><i class="fa fa-circle-o"> </i> ADD USER </a></li>
                    <li><a class="subMenuLink" href="staff_list.php"><i class="fa fa-circle-o"> </i> STAFF LIST </a></li>
                    <li><a class="subMenuLink" href="admin_list.php"><i class="fa fa-circle-o"> </i> ADMIN LIST </a></li>
                    <li><a class="subMenuLink" href="distributor_list.php"><i class="fa fa-circle-o"> </i> DISTRIBUTOR LIST </a></li>
                </ul>
            </li>

            <li class="liMainMenu showHideSubMenu">
                <a href="javascript:void(0);" class="px-3 py-2 d-inline-block showHideSubMenu">
                    <i class="fa fa-shopping-bag showHideSubMenu"></i>
                    <span class="menuText showHideSubMenu"> PRODUCTS </span>
                </a>
                
                <ul class="subMenus">
                    <li><a class="subMenuLink" href="trial_add.php"><i class="fa fa-circle-o"> </i> ADD PRODUCT </a></li>
                    <li><a class="subMenuLink" href="trial_view.php"><i class="fa fa-circle-o"> </i> VIEW PRODUCT </a></li>
                </ul>
            </li>

             <li class="liMainMenu showHideSubMenu">
                <a href="javascript:void(0);" class="px-3 py-2 d-inline-block showHideSubMenu">
                    <i class="fa fa-shopping-cart showHideSubMenu"></i>
                    <span class="menuText showHideSubMenu"> ORDERS </span>
                </a>
                <ul class="subMenus">
                    <li><a class="subMenuLink" href="add_order.php"><i class="fa fa-circle-o"> </i> ADD ORDER </a></li>
                    <li><a class="subMenuLink" href="view_order.php"><i class="fa fa-circle-o"> </i> VIEW ORDER </a></li>
                    <li><a class="subMenuLink" href="distributor_notes.php"><i class="fa fa-circle-o"> </i> ORDER NOTES </a></li>

                </ul>
            </li>

            
            <li class="liMainMenu">
                <a href="admin_reports.php" class="px-3 py-2 d-inline-block">
                    <i class="fa fa-file-text"></i> <span class="menuText"> REPORTS </span>
                </a>
            </li>

        </ul> 
    </div>
</div>
