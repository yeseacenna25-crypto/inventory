<?php
if (!isset($_SESSION)) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Fetch staff name and profile image
$stmt = $conn->prepare("SELECT staff_fname, staff_mname, staff_lname, staff_profile_image FROM staff_signup WHERE staff_id = ?");
$stmt->bind_param("i", $_SESSION['staff_id']);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$fullName = $staff ? ($staff['staff_fname'] . ' ' . $staff['staff_mname'] . ' ' . $staff['staff_lname']) : "Staff";
$profileImage = (!empty($staff['staff_profile_image'])) ? "uploads/" . $staff['staff_profile_image'] : "";
$stmt->close();
// Do not close $conn here; let the parent file handle it.
?>


<style>
    .dashboard_sidebar {
        min-width: 270px;
        max-width: 300px;
        background: #4b0c0c;
        color: #fff;
        padding-top: 48px;
        padding-bottom: 48px;
        padding-left: 24px;
        padding-right: 24px;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        height: 100vh;
    }
    .dashboard_logo {
        margin-bottom: 32px;
        font-size: 2rem;
        letter-spacing: 2px;
    }
    .dashboard_sidebar_user {
        margin-bottom: 32px;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .user-image-wrapper {
        margin-bottom: 12px;
    }
    #userName {
        margin-top: 8px;
        text-align: center;
        font-size: 1.1rem;
        font-weight: 500;
    }
    .dashboard_sidebar_menu {
        width: 100%;
    }
    .dashboard_menu_list {
        padding-left: 0;
        margin-top: 16px;
    }
    .liMainMenu {
        margin-bottom: 18px;
    }
    .subMenus {
        margin-left: 24px;
        margin-top: 8px;
        margin-bottom: 8px;
    }
</style>

<div class="dashboard_sidebar" id="dashboard_sidebar">
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
            <a href="s_edit_profile.php" class="edit-profile-btn" title="Edit Profile">
                <i class="fa fa-pencil"></i>
            </a>
        </div>
        <span id="userName">Welcome! <br> <?= htmlspecialchars($fullName) ?></span>
    </div>
    <div class="dashboard_sidebar_menu">
        <ul class="dashboard_menu_list">
            <li class="liMainMenu">
                <a href="./staff_dashboard.php" class="px-3 py-2 d-inline-block">
                    <i class="fa fa-home" ></i> <span class="menuText"> DASHBOARD </span>
                </a>
            </li>
            <li class="liMainMenu showHideSubMenu">
                <a href="javascript:void(0);" class="px-3 py-2 d-inline-block showHideSubMenu">
                    <i class="fa fa-user-plus showHideSubMenu"></i>
                    <span class="menuText showHideSubMenu"> USERS </span>
                </a>
                <ul class="subMenus">
                    <li><a class="subMenuLink" href="s_adduser.php"><i class="fa fa-circle-o"> </i> ADD USER </a></li>
                    <li><a class="subMenuLink" href="s_staff_list.php"><i class="fa fa-circle-o"> </i> STAFF LIST </a></li>
                    <li><a class="subMenuLink" href="s_admin_list.php"><i class="fa fa-circle-o"> </i> ADMIN LIST </a></li>
                    <li><a class="subMenuLink" href="s_distributor_list.php"><i class="fa fa-circle-o"> </i> DISTRIBUTOR LIST </a></li>
                </ul>
            </li>
            <li class="liMainMenu showHideSubMenu">
                <a href="javascript:void(0);" class="px-3 py-2 d-inline-block showHideSubMenu">
                    <i class="fa fa-shopping-bag showHideSubMenu"></i>
                    <span class="menuText showHideSubMenu"> PRODUCTS </span>
                </a>
                <ul class="subMenus">
                    <li><a class="subMenuLink" href="s_add_product.php"><i class="fa fa-circle-o"> </i> ADD PRODUCT </a></li>
                    <li><a class="subMenuLink" href="s_view_product.php"><i class="fa fa-circle-o"> </i> VIEW PRODUCT </a></li>
                </ul>
            </li>
            <li class="liMainMenu showHideSubMenu">
                <a href="javascript:void(0);" class="px-3 py-2 d-inline-block showHideSubMenu">
                    <i class="fa fa-shopping-cart showHideSubMenu"></i>
                    <span class="menuText showHideSubMenu"> ORDERS </span>
                </a>
                <ul class="subMenus">
                    <li><a class="subMenuLink" href="s_add_order.php"><i class="fa fa-circle-o"> </i> ADD ORDER </a></li>
                    <li><a class="subMenuLink" href="s_view_order.php"><i class="fa fa-circle-o"> </i> VIEW ORDER </a></li>
                    <li><a class="subMenuLink" href="s_distributor_notes.php"><i class="fa fa-circle-o"> </i> ORDER NOTES </a></li>
                </ul>
            </li>
        </ul> 
    </div>
</div>
