<?php
// distributor_notes.php
// Displays notes sent by distributors in their orders

// Start output buffering to prevent "headers already sent" error
ob_start();

session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: staff_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch only orders that have notes
$sql = "SELECT order_id, customer_name, order_notes, created_at 
        FROM orders 
        WHERE order_notes IS NOT NULL AND TRIM(order_notes) != '' 
        ORDER BY created_at DESC";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distributor Notes</title>
    <link rel="stylesheet" type="text/css" href="../CSS/admin_dashboard.css?v=<?= time(); ?>" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
</head>
<body>

<button class="mobile-menu-btn" id="mobile-menu-btn" style="display:none;">
    <i class="fa fa-bars"></i>
</button>
    
<div class="sidebar-overlay"></div>
 

   
<div id="dashboardMainContainer">
        <!-- SIDEBAR -->
        <?php include('STAFFpartials/s_sidebar.php'); ?>
        <!-- SIDEBAR -->

            <div class="dashboard_content">
         <!-- TOP NAVBAR -->
        <?php include('STAFFpartials/s_topnav.php'); ?>
        <!-- TOP NAVBAR -->
          <div class="dashboard_content_main">
      <?php
      // Use the existing session and connection
      // Get all orders with notes - staff should see all notes, not just for a specific distributor
      $sql = "SELECT o.order_id, o.customer_name, o.created_at, o.order_notes 
              FROM orders o
              WHERE o.order_notes IS NOT NULL AND TRIM(o.order_notes) != ''
              ORDER BY o.created_at DESC";
      $result = $conn->query($sql);
      ?>
      <div class="container mt-4">
                <h2 class="mb-2" style="color:#6a0000;font-weight:bold;text-align:center;font-size:2.5rem;letter-spacing:1px;">Order Notes</h2>
                <div style="width:120px;height:6px;background:none;margin:0 auto 24px auto;">
                  <hr style="border:0;border-top:6px solid #6a0000;width:100px;margin:0 auto;">
                </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered" style="border-collapse:collapse;">
            <thead style="background:#410101;color:#fff;">
              <tr>
                <th style="font-weight:bold;text-align:left;">Order ID</th>
                <th style="font-weight:bold;text-align:left;">Customer Name</th>
                <th style="font-weight:bold;text-align:left;">Notes</th>
                <th style="font-weight:bold;text-align:left;">Date</th>
              </tr>
            </thead>
            <tbody style="background:#fff;">
            <?php 
            if ($result && $result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td style="text-align:left;"><?= htmlspecialchars($row['order_id']) ?></td>
                  <td style="text-align:left;"><?= htmlspecialchars($row['customer_name']) ?></td>
                  <td style="text-align:left;"><?= nl2br(htmlspecialchars($row['order_notes'])) ?></td>
                  <td style="text-align:left;"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="4" class="text-center">No order notes found.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      </div>

</div>

<script src="../sidebar-drawer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
// End output buffering and send content to browser
ob_end_flush();
?>
