<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin_login.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle status updates
if ($_POST['action'] ?? '' === 'update_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    $allowed_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        
        if ($stmt->execute()) {
            header("Location: admin_order_management.php?msg=Order status updated successfully");
        } else {
            header("Location: admin_order_management.php?error=Failed to update order status");
        }
        $stmt->close();
    }
    exit();
}

// Get orders with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where_conditions = [];
$params = [];
$types = '';

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(o.customer_name LIKE ? OR o.customer_contact LIKE ? OR o.order_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM orders o $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $per_page);

// Get orders data
$sql = "SELECT o.order_id, o.customer_name, o.customer_contact, o.customer_address, 
               o.total_amount, o.handling_fee, o.final_total, o.status, o.user_type, 
               o.created_by, o.created_at, o.updated_at,
               COUNT(oi.order_item_id) as total_items
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        $where_clause
        GROUP BY o.order_id
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";

$all_params = $params; // Copy the existing params
$all_params[] = $per_page;
$all_params[] = $offset;
$all_types = $types . 'ii';

$stmt = $conn->prepare($sql);
if (!empty($all_params)) {
    $stmt->bind_param($all_types, ...$all_params);
} else {
    $stmt->bind_param('ii', $per_page, $offset);
}
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Negrita Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .navbar { background-color: #410101 !important; }
        .btn-primary { background-color: #410101; border-color: #410101; }
        .btn-primary:hover { background-color: #5e0202; border-color: #5e0202; }
        .status-badge { font-size: 0.875rem; padding: 0.5rem 0.75rem; }
        .order-card { transition: all 0.3s ease; }
        .order-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-box-seam me-2"></i>Negrita Inventory - Orders
            </a>
            <div class="d-flex">
                <a href="../admin_dashboard.php" class="btn btn-outline-light me-2">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <a href="../logout.php" class="btn btn-outline-light">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filters and Search -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search orders..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
            <div class="col-md-6">
                <form method="GET" class="d-flex justify-content-end">
                    <select name="status" class="form-select me-2" style="width: auto;">
                        <option value="">All Status</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-secondary">Filter</button>
                </form>
            </div>
        </div>

        <!-- Orders Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Orders Overview</h5>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="p-3">
                                    <h3 class="text-primary"><?= $total_orders ?></h3>
                                    <p class="mb-0">Total Orders</p>
                                </div>
                            </div>
                            <?php 
                            $status_counts = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
                            $status_data = [];
                            while ($row = $status_counts->fetch_assoc()) {
                                $status_data[$row['status']] = $row['count'];
                            }
                            ?>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <h3 class="text-warning"><?= $status_data['pending'] ?? 0 ?></h3>
                                    <p class="mb-0">Pending</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <h3 class="text-info"><?= $status_data['processing'] ?? 0 ?></h3>
                                    <p class="mb-0">Processing</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <h3 class="text-success"><?= $status_data['completed'] ?? 0 ?></h3>
                                    <p class="mb-0">Completed</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders List -->
        <div class="row">
            <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card order-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Order #<?= $order['order_id'] ?></h6>
                                <span class="badge status-badge <?php
                                    switch($order['status']) {
                                        case 'pending': echo 'bg-warning text-dark'; break;
                                        case 'processing': echo 'bg-info'; break;
                                        case 'completed': echo 'bg-success'; break;
                                        case 'cancelled': echo 'bg-danger'; break;
                                        default: echo 'bg-secondary';
                                    }
                                ?>"><?= ucfirst($order['status']) ?></span>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($order['customer_name']) ?></h6>
                                <p class="card-text">
                                    <small class="text-muted">
                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['customer_contact']) ?><br>
                                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars(substr($order['customer_address'], 0, 50)) ?>...
                                    </small>
                                </p>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <small class="text-muted">Items</small><br>
                                        <strong><?= $order['total_items'] ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">Subtotal</small><br>
                                        <strong>₱<?= number_format($order['total_amount'], 2) ?></strong>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">Total</small><br>
                                        <strong>₱<?= number_format($order['final_total'], 2) ?></strong>
                                    </div>
                                </div>
                                
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                                </small>
                            </div>
                            <div class="card-footer">
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                    <select name="status" class="form-select form-select-sm" required>
                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h4 class="mt-3">No Orders Found</h4>
                        <p class="text-muted">No orders match your current filters.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Orders pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= max(1, $page - 1) ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>">Previous</a>
                    </li>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= min($total_pages, $page + 1) ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
