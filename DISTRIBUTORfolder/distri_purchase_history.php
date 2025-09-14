<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PURCHASE HISTORY</title>
    <link rel="stylesheet" type="text/css" href="distributor.css?v=<?= time(); ?>" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link
      rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
  </head>

  <body>

    <!-- Mobile menu overlay -->
    <div class="sidebar-overlay"></div>
    
    <!-- Mobile menu button -->
    <button class="mobile-menu-btn" id="mobile-menu-btn">
        <i class="fa fa-bars"></i>
    </button>

    <div id="dashboardMainContainer">

      <!-- SIDEBAR -->
        <?php include('distri_partials/d_sidebar.php') ?>
        <!-- SIDEBAR -->

        <div class="dashboard_content_container" id="dashboard_content_container">

          <!-- TOP NAVBAR -->
          <?php include('distri_partials/d_topnav.php') ?>
          <!-- TOP NAVBAR -->

            <div class="dashboard_content">
      <div class="dashboard_content_main">
      <?php
      if (session_status() === PHP_SESSION_NONE) {
        session_start();
      }
      if (!isset($_SESSION['distributor_id'])) {
        header('Location: distri_login.php');
        exit();
      }
      $conn = new mysqli('localhost', 'root', '', 'inventory_negrita');
      if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
      }
      $distributor_id = $_SESSION['distributor_id'];
      $sql = "SELECT o.order_id, o.created_at, o.final_total, o.order_notes,
          GROUP_CONCAT(oi.product_name SEPARATOR ', ') AS products,
          SUM(oi.quantity) AS total_quantity
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.distributor_id = ?
        GROUP BY o.order_id
        ORDER BY o.created_at DESC";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param('i', $distributor_id);
      $stmt->execute();
      $result = $stmt->get_result();
      ?>
      <div class="container mt-4">
                <h2 class="mb-2" style="color:#6a0000;font-weight:bold;text-align:center;font-size:2.5rem;letter-spacing:1px;">Purchase History</h2>
                <div style="width:120px;height:6px;background:none;margin:0 auto 24px auto;">
                  <hr style="border:0;border-top:6px solid #6a0000;width:100px;margin:0 auto;">
                </div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered" style="border-collapse:collapse;">
            <thead style="background:#410101;color:#fff;">
              <tr>
                <th style="font-weight:bold;text-align:left;">#</th>
                <th style="font-weight:bold;text-align:left;">Order ID</th>
                <th style="font-weight:bold;text-align:left;">Date</th>
                <th style="font-weight:bold;text-align:left;">Products</th>
                <th style="font-weight:bold;text-align:left;">Quantity</th>
                <th style="font-weight:bold;text-align:left;">Total</th>
                <th style="font-weight:bold;text-align:left;">Notes</th>
              </tr>
            </thead>
            <tbody style="background:#fff;">
            <?php 
            $rownum = 1;
            if ($result && $result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td style="text-align:left;"><?= $rownum++ ?></td>
                  <td style="text-align:left;"><?= htmlspecialchars($row['order_id']) ?></td>
                  <td style="text-align:left;"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))) ?></td>
                  <td style="text-align:left;"><?= htmlspecialchars($row['products']) ?></td>
                  <td style="text-align:left;"><?= htmlspecialchars($row['total_quantity']) ?></td>
                  <td style="text-align:left;">₱<?= number_format($row['final_total'], 2) ?></td>
                  <td style="text-align:left;"><?= nl2br(htmlspecialchars($row['order_notes'])) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="7" class="text-center">No purchase history found.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php
      $stmt->close();
      $conn->close();
      ?>
      </div>
</div>
</div>
</div>







    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Include responsive sidebar functionality -->
    <script src="../sidebar-drawer.js"></script>



    

    
    
  </body>
</html>