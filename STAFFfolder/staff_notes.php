<?php
// staff_notes.php
// Displays notes sent by distributors in their orders (STAFF view)

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

// Fetch distributor order notes
$sql = "SELECT o.order_id, o.distributor_id, d.distrib_fname, o.order_notes, o.created_at FROM orders o JOIN distributor_signup d ON o.distributor_id = d.distributor_id WHERE o.order_notes IS NOT NULL AND o.order_notes != '' ORDER BY o.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distributor Notes (Staff)</title>
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

    <div class="dashboard_content_container" id="dashboard_content_container">
        <div class="dashboard_content">
            <div class="modern-container">
                <div class="modern-card p-4 mt-4">
                    <h2 class="page-title">Distributor Notes</h2>
                    <div class="table-responsive mt-4">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Distributor Name</th>
                                    <th>Order Date</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['order_id']) ?></td>
                                            <td><?= htmlspecialchars($row['distrib_fname']) ?></td>
                                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                                            <td><?= nl2br(htmlspecialchars($row['order_notes'])) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center">No distributor notes found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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
?>
