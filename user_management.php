<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch current admin info
$stmt = $conn->prepare("SELECT first_name, last_name FROM admin_signup WHERE admin_id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$current_admin = $result->fetch_assoc();
$fullName = $current_admin ? $current_admin['first_name'] . ' ' . $current_admin['last_name'] : "Admin";
$stmt->close();

// Fetch all users from all tables
$all_users = [];

// Fetch admins
$admin_query = "SELECT admin_id as id, 'admin' as type, first_name, last_name, email, username, outlet, created_at FROM admin_signup ORDER BY created_at DESC";
$admin_result = $conn->query($admin_query);
if ($admin_result) {
    while ($admin = $admin_result->fetch_assoc()) {
        $admin['type_label'] = 'Admin';
        $admin['can_delete'] = ($admin['id'] != $_SESSION['admin_id']);
        $all_users[] = $admin;
    }
}

// Fetch staff
$staff_query = "SELECT staff_id as id, 'staff' as type, staff_fname, staff_mname, staff_lname, staff_email, staff_username, staff_outlet, created_at FROM staff_signup ORDER BY created_at DESC";
$staff_result = $conn->query($staff_query);
if ($staff_result) {
    while ($staff = $staff_result->fetch_assoc()) {
        $staff['type_label'] = 'Staff';
        $staff['can_delete'] = true;
        $all_users[] = $staff;
    }
}

// Fetch distributors
$distributor_query = "SELECT distributor_id as id, 'distributor' as type, distrib_fname, distrib_lname, distrib_email, distrib_username, distrib_outlet, created_at FROM distributor_signup ORDER BY created_at DESC";
$distributor_result = $conn->query($distributor_query);
if ($distributor_result) {
    while ($distributor = $distributor_result->fetch_assoc()) {
        $distributor['type_label'] = 'Distributor';
        $distributor['can_delete'] = true;
        $all_users[] = $distributor;
    }
}

// Sort by creation date (newest first)
usort($all_users, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - NIMS</title>
    <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<div id="dashboardMainContainer">
    <!-- SIDEBAR -->
    <?php include('partials/sidebar.php') ?>
    <!-- SIDEBAR -->

    <div class="dashboard_content_container" id="dashboard_content_container">
        <!-- TOP NAVBAR -->
        <?php include('partials/topnav.php') ?>
        <!-- TOP NAVBAR -->

        <div class="dashboard_content">
            <div class="dashboard_content_main">
                
                <!-- Header Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h2 class="card-title mb-0">
                                    <i class="bi bi-people-fill"></i> User Management
                                </h2>
                                <p class="text-muted">Manage all system users - Admin, Staff, and Distributors</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center" style="border-left: 4px solid #410101;">
                            <div class="card-body">
                                <h5 class="card-title">Total Admins</h5>
                                <h3 class="text-primary">
                                    <?php echo count(array_filter($all_users, function($u) { return $u['type'] === 'admin'; })); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center" style="border-left: 4px solid #28a745;">
                            <div class="card-body">
                                <h5 class="card-title">Total Staff</h5>
                                <h3 class="text-success">
                                    <?php echo count(array_filter($all_users, function($u) { return $u['type'] === 'staff'; })); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center" style="border-left: 4px solid #ffc107;">
                            <div class="card-body">
                                <h5 class="card-title">Total Distributors</h5>
                                <h3 class="text-warning">
                                    <?php echo count(array_filter($all_users, function($u) { return $u['type'] === 'distributor'; })); ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="btn-group" role="group">
                            <a href="admin_adduser.php" class="btn btn-success">
                                <i class="fa fa-plus"></i> Add New User
                            </a>
                            <a href="admin_list.php" class="btn btn-outline-primary">
                                <i class="fa fa-users"></i> View Admin List
                            </a>
                            <a href="staff_list.php" class="btn btn-outline-success">
                                <i class="fa fa-user-tie"></i> View Staff List
                            </a>
                            <a href="distributor_list.php" class="btn btn-outline-warning">
                                <i class="fa fa-truck"></i> View Distributor List
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">All Users</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Type</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Username</th>
                                        <th>Outlet</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($all_users)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">No users found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($all_users as $index => $user): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <?php
                                                $badge_class = '';
                                                switch($user['type']) {
                                                    case 'admin': $badge_class = 'bg-primary'; break;
                                                    case 'staff': $badge_class = 'bg-success'; break;
                                                    case 'distributor': $badge_class = 'bg-warning text-dark'; break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo $user['type_label']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                <?php if ($user['type'] === 'admin' && $user['id'] == $_SESSION['admin_id']): ?>
                                                    <small class="text-muted">(You)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['outlet']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" onclick="editUser('<?php echo $user['type']; ?>', <?php echo $user['id']; ?>)">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                    <?php if ($user['can_delete']): ?>
                                                        <button class="btn btn-outline-danger" onclick="deleteUser('<?php echo $user['type']; ?>', <?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-outline-secondary" disabled title="Cannot delete yourself">
                                                            <i class="fa fa-lock"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Sidebar toggle functionality
var sideBarIsOpen = true;
var toggleBtn = document.getElementById('toggleBtn');
var dashboard_sidebar = document.getElementById('dashboard_sidebar');
var dashboard_content_container = document.getElementById('dashboard_content_container');
var dashboard_logo = document.getElementById('dashboard_logo');
var userImage = document.getElementById('userImage');
var userName = document.getElementById('userName');

if (toggleBtn) {
    toggleBtn.addEventListener("click", (event) => {
        event.preventDefault();
        if (sideBarIsOpen) {
            if (dashboard_sidebar) dashboard_sidebar.style.width = '8%';
            if (dashboard_content_container) dashboard_content_container.style.width = '92%';
            if (dashboard_logo) dashboard_logo.style.fontSize = '30px';
            if (userImage) userImage.style.width = '70px';
            if (userName) userName.style.fontSize = '15px';

            let menuIcons = document.getElementsByClassName('menuText');
            for (let i = 0; i < menuIcons.length; i++) {
                menuIcons[i].style.display = 'none';
            }
            let menuList = document.getElementsByClassName('dashboard_menu_list')[0];
            if (menuList) menuList.style.textAlign = 'center';
            sideBarIsOpen = false;
        } else {
            if (dashboard_sidebar) dashboard_sidebar.style.width = '20%';
            if (dashboard_content_container) dashboard_content_container.style.width = '80%';
            if (dashboard_logo) dashboard_logo.style.fontSize = '50px';
            if (userImage) userImage.style.width = '70px';
            if (userName) userName.style.fontSize = '15px';

            let menuIcons = document.getElementsByClassName('menuText');
            for (let i = 0; i < menuIcons.length; i++) {
                menuIcons[i].style.display = 'inline-block';
            }
            let menuList = document.getElementsByClassName('dashboard_menu_list')[0];
            if (menuList) menuList.style.textAlign = 'left';
            sideBarIsOpen = true;
        }
    });
}

// User management functions
function editUser(userType, userId) {
    window.location.href = `edit_universal_profile.php?type=${userType}&id=${userId}`;
}

function deleteUser(userType, userId, userName) {
    Swal.fire({
        title: 'Are you sure?',
        text: `You are about to delete ${userType} "${userName}". This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete user!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the user.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Make delete request using universal delete
            fetch('delete_universal_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_type: userType,
                    user_id: userId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#410101'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to delete user.',
                        icon: 'error',
                        confirmButtonColor: '#410101'
                    });
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while deleting the user.',
                    icon: 'error',
                    confirmButtonColor: '#410101'
                });
            });
        }
    });
}

// Sub menu functionality
document.addEventListener('click', function (e) {
    let clickedElement = e.target;
    if (clickedElement.classList.contains('showHideSubMenu')) {
        let subMenu = clickedElement.closest('li').querySelector('.subMenus');
        let mainMenuIcon = clickedElement.closest('li').querySelector('.mainMenuIconArrow');
        let subMenus = document.querySelectorAll('.subMenus');
        
        subMenus.forEach((sub) => {
            if (subMenu !== sub) sub.style.display = 'none';
        });
        
        if (subMenu != null) {
            if (subMenu.style.display === 'block') {
                subMenu.style.display = 'none';
                if (mainMenuIcon) {
                    mainMenuIcon.classList.remove('fa-angle-down');
                    mainMenuIcon.classList.add('fa-angle-left');
                }
            } else {
                subMenu.style.display = 'block';
                if (mainMenuIcon) {
                    mainMenuIcon.classList.remove('fa-angle-left');
                    mainMenuIcon.classList.add('fa-angle-down');
                }
            }
        }
    }
});
</script>

</body>
</html>
