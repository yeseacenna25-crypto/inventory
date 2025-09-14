<?php
if (!isset($_SESSION)) {
    session_start();
}

$conn = new mysqli("localhost", "root", "", "inventory_negrita");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch distributor name and profile image
$stmt = $conn->prepare("SELECT distrib_fname, distrib_mname, distrib_lname, distrib_profile_image FROM distributor_signup WHERE distributor_id = ?");
$stmt->bind_param("i", $_SESSION['distributor_id']);
$stmt->execute();
$result = $stmt->get_result();
$distributor = $result->fetch_assoc();

$fullName = $distributor ? $distributor['distrib_fname'] . ' ' . $distributor['distrib_mname'] . ' ' . $distributor['distrib_lname'] : "Distributor";
$profileImage = (!empty($distributor['distrib_profile_image'])) ? "uploads/" . $distributor['distrib_profile_image'] : "";

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
            <a href="d_edit_profile.php" class="edit-profile-btn" title="Edit Profile">
                <i class="fa fa-pencil"></i>
            </a>
        </div>
        <span id="userName">Welcome! <br> <?= htmlspecialchars($fullName) ?></span>
    </div>

    <div class="dashboard_sidebar_menu">
        <ul class="dashboard_menu_list">

            <li class="liMainMenu">
                <a href="distri_dashboard.php" class="px-3 py-2 d-inline-block">
                    <i class="fa fa-home"></i> <span class="menuText"> DASHBOARD </span>
                </a>
            </li>

            <li class="liMainMenu">
                <a href="distri_products.php" class="px-3 py-2 d-inline-block">
                    <i class="fa fa-shopping-bag"></i> <span class="menuText"> PRODUCTS </span>
                </a>
            </li>

             <li class="liMainMenu">
                <a href="distri_cart.php" class="px-3 py-2 d-inline-block">
                    <i class="fa fa-shopping-cart"></i> <span class="menuText"> CART </span>
                </a>
            </li>

             <li class="liMainMenu showHideSubMenu">
                <a href="javascript:void(0);" class="px-3 py-2 d-inline-block showHideSubMenu">
                    <i class="fa fa-file-text showHideSubMenu"></i>
                    <span class="menuText showHideSubMenu"> REPORTS </span>
                </a>
                
                <ul class="subMenus">
                    <li><a class="subMenuLink" href="distri_my_orders.php"><i class="fa fa-circle-o"> </i> MY ORDERS </a></li>
                     <li><a class="subMenuLink" href="distri_purchase_history.php"><i class="fa fa-circle-o"> </i> PURCHASE HISTORY </a></li>
                </ul>
            </li>

        </ul> 
    </div>
</div>
