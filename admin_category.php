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

// Fetch admin name
$stmt = $conn->prepare("SELECT admin_fname, admin_mname, admin_lname FROM admin_signup WHERE admin_id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

$fullName = $admin ? $admin['admin_fname']: "Admin";

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CATEGORIES</title>
    <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css?v=<?= time(); ?>" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
    <script>
      history.pushState(null, null, location.href);
      window.onpopstate = function () {
        history.go(1);
      };
    </script>
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
            <!-- Content here -->
          </div>
        </div>
      </div>
    </div>


        <div class="dashboard_content">
          <div class="dashboard_content_main">
            <!-- Content here -->
          </div>
        </div>
      </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="sidebar-drawer.js"></script>
  </body>
</html>
