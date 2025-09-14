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

// Get filter parameters
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$date_filter = $_GET['date_filter'] ?? 'all';
$status_filter = $_GET['status_filter'] ?? 'all';
$distributor_filter = $_GET['distributor_filter'] ?? 'all';

// Build WHERE clause based on filters
$where_conditions = [];
$params = [];
$types = "";

// Handle date range filter (new implementation)
if (!empty($date_from) || !empty($date_to)) {
    if (!empty($date_from) && !empty($date_to)) {
        $where_conditions[] = "DATE(o.created_at) BETWEEN ? AND ?";
        $params[] = $date_from;
        $params[] = $date_to;
        $types .= "ss";
    } elseif (!empty($date_from)) {
        $where_conditions[] = "DATE(o.created_at) >= ?";
        $params[] = $date_from;
        $types .= "s";
    } elseif (!empty($date_to)) {
        $where_conditions[] = "DATE(o.created_at) <= ?";
        $params[] = $date_to;
        $types .= "s";
    }
} elseif ($date_filter !== 'all') {
    // Fallback to old date filter logic if date range not provided
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(o.created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $where_conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $where_conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }
}

if ($status_filter !== 'all') {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($distributor_filter !== 'all') {
    $where_conditions[] = "o.distributor_id = ?";
    $params[] = $distributor_filter;
    $types .= "i";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Check table structure first and fetch order report data
try {
    // First, let's see what columns exist in the orders table
    $columnsResult = $conn->query("DESCRIBE orders");
    $orderColumns = [];
    while ($col = $columnsResult->fetch_assoc()) {
        $orderColumns[] = $col['Field'];
    }
    
    // Build query based on available columns
    $distributorJoin = "";
    $distributorSelect = "'' as distrib_fname, '' as distrib_mname, '' as distrib_lname, '' as distrib_outlet";

    // Check if we have a column that can link to distributors
    if (in_array('distributor_id', $orderColumns)) {
        $distributorJoin = "LEFT JOIN distributor_signup d ON o.distributor_id = d.distributor_id";
        $distributorSelect = "COALESCE(d.distrib_fname, '') as distrib_fname, COALESCE(d.distrib_mname, '') as distrib_mname, COALESCE(d.distrib_lname, '') as distrib_lname, COALESCE(d.distrib_outlet, '') as distrib_outlet";
    } elseif (in_array('user_id', $orderColumns)) {
        $distributorJoin = "LEFT JOIN distributor_signup d ON o.user_id = d.distributor_id";
        $distributorSelect = "COALESCE(d.distrib_fname, '') as distrib_fname, COALESCE(d.distrib_mname, '') as distrib_mname, COALESCE(d.distrib_lname, '') as distrib_lname, COALESCE(d.distrib_outlet, '') as distrib_outlet";
    }

    $orderQuery = "SELECT 
        o.order_id,
        o.total_amount,
        o.status,
        o.created_at,
        $distributorSelect,
        COUNT(oi.order_item_id) as total_items
        , o.customer_name, o.customer_address
    FROM orders o
    $distributorJoin
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    $where_clause
    GROUP BY o.order_id, o.total_amount, o.status, o.created_at
    ORDER BY o.created_at DESC";

} catch (Exception $e) {
    // Fallback query without distributor join
    $orderQuery = "SELECT 
        o.order_id,
        o.total_amount,
        o.status,
        o.created_at,
        'Unknown' as distrib_fname,
        '' as distrib_mname,
        '' as distrib_lname,
        '' as distrib_outlet,
        0 as total_items
    FROM orders o
    $where_clause
    ORDER BY o.created_at DESC";
}

if (!empty($params)) {
    $stmt = $conn->prepare($orderQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $orderResult = $stmt->get_result();
} else {
    $orderResult = $conn->query($orderQuery);
}

$orders = $orderResult ? $orderResult->fetch_all(MYSQLI_ASSOC) : [];

// Fetch distributors for filter dropdown
$distributorQuery = "SELECT distributor_id, CONCAT(distrib_fname, ' ', distrib_lname) as name, distrib_outlet FROM distributor_signup ORDER BY distrib_fname";
$distributorResult = $conn->query($distributorQuery);
$distributors = $distributorResult ? $distributorResult->fetch_all(MYSQLI_ASSOC) : [];

// Calculate summary statistics  
$totalOrdersQuery = "SELECT 
    COUNT(*) as total_orders,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_order_value
FROM orders o 
$where_clause";

if (!empty($params)) {
    $stmt = $conn->prepare($totalOrdersQuery);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $statsResult = $stmt->get_result();
} else {
    $statsResult = $conn->query($totalOrdersQuery);
}

$stats = $statsResult ? $statsResult->fetch_assoc() : ['total_orders' => 0, 'total_revenue' => 0, 'avg_order_value' => 0];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DASHBOARD</title>
    <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css?v=<?= time(); ?>" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link
      rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
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

                <!-- Page Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="fw-bold" style="color: #410101; font-family: 'Merriweather', sans-serif;">ORDER REPORTS</h1>
                                <?php if (!empty($date_from) || !empty($date_to)): ?>
                                    <div class="mt-1">
                                        <small class="text-primary fw-bold">
                                            <i class="bi bi-funnel-fill"></i>
                                            <?php if (!empty($date_from) && !empty($date_to)): ?>
                                                Filtered: <?= date('M j, Y', strtotime($date_from)) ?> to <?= date('M j, Y', strtotime($date_to)) ?>
                                            <?php elseif (!empty($date_from)): ?>
                                                Filtered: From <?= date('M j, Y', strtotime($date_from)) ?>
                                            <?php elseif (!empty($date_to)): ?>
                                                Filtered: Until <?= date('M j, Y', strtotime($date_to)) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-success" onclick="exportReport()">
                                <i class="bi bi-download"></i> Export Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header" style="background: linear-gradient(135deg, #410101, #5e0202); color: white;">
                                <h5 class="mb-0"><i class="bi bi-funnel"></i> Filters</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" action="">
                                    <!-- Quick Date Presets -->
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label class="form-label fw-bold">Quick Date Filters:</label>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDatePreset('today', this)">Today</button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDatePreset('week', this)">This Week</button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDatePreset('month', this)">This Month</button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDatePreset('year', this)">This Year</button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        
                                        <!-- Date Range Filter -->
                                        <div class="col-md-3 mb-3">
                                            <label for="date_from" class="form-label fw-bold">Date From</label>
                                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                                   value="<?= $_GET['date_from'] ?? '' ?>">
                                        </div>
                                        
                                        <div class="col-md-3 mb-3">
                                            <label for="date_to" class="form-label fw-bold">Date To</label>
                                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                                   value="<?= $_GET['date_to'] ?? '' ?>">
                                        </div>
                                        
                                        <div class="col-md-3 mb-3">
                                            <label for="status_filter" class="form-label fw-bold">Order Status</label>
                                            <select class="form-select" id="status_filter" name="status_filter">
                                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-3 mb-3 d-flex align-items-end gap-2">
                                            <button type="submit" class="btn btn-primary flex-grow-1" style="background: #410101; border-color: #410101;">
                                                <i class="bi bi-search"></i> Filter
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearDateFilter()">
                                                <i class="bi bi-x-circle"></i> Clear
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card text-center h-100" style="border-left: 4px solid #410101;">
                            <div class="card-body">
                                <div class="d-flex justify-content-center align-items-center mb-3">
                                    <i class="bi bi-box-seam" style="font-size: 3rem; color: #410101;"></i>
                                </div>
                                <h2 class="fw-bold" style="color: #410101;"><?= number_format($stats['total_orders']) ?></h2>
                                <p class="text-muted">Total Orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-center h-100" style="border-left: 4px solid #A34343;">
                            <div class="card-body">
                                <div class="d-flex justify-content-center align-items-center mb-3">
                                    <i class="bi bi-cash bi-currency-peso" style="font-size: 3rem; color: #A34343;"></i>
                                </div>
                                <h2 class="fw-bold" style="color: #A34343;">₱<?= number_format($stats['total_revenue'], 2) ?></h2>
                                <p class="text-muted">Total Revenue</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card text-center h-100" style="border-left: 4px solid #748DAE;">
                            <div class="card-body">
                                <div class="d-flex justify-content-center align-items-center mb-3">
                                    <i class="bi bi-graph-up" style="font-size: 3rem; color: #748DAE;"></i>
                                </div>
                                <h2 class="fw-bold" style="color: #748DAE;">₱<?= number_format($stats['avg_order_value'], 2) ?></h2>
                                <p class="text-muted">Average Order Value</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header" style="background: linear-gradient(135deg, #410101, #5e0202); color: white;">
                                <h5 class="mb-0"><i class="bi bi-table"></i> Order History</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($orders)): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                                        <h4 class="text-muted mt-3">No orders found</h4>
                                        <p class="text-muted">Try adjusting your filters to see more results.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover text-center">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Distributor</th>
                                                    <th>Outlet</th>
                                                    <th>Items</th>
                                                    <th>Total Amount</th>
                                                    <th>Status</th>
                                                    <th>Order Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td><strong>#<?= $order['order_id'] ?></strong></td>
                                                           <td>
                                                              <?php 
                                                              $distName = trim($order['distrib_fname'] . ' ' . $order['distrib_lname']);
                                                              if ($distName) {
                                                                  echo htmlspecialchars($distName);
                                                              } elseif (!empty($order['customer_name'])) {
                                                                  echo htmlspecialchars($order['customer_name']);
                                                              } else {
                                                                  echo '<span class="text-muted">N/A</span>'; 
                                                              }
                                                              ?>
                                                          </td>
                                                          <td>
                                                              <?php 
                                                              if (!empty($order['distrib_outlet'])) {
                                                                  echo htmlspecialchars($order['distrib_outlet']);
                                                              } elseif (!empty($order['customer_address'])) {
                                                                  echo htmlspecialchars($order['customer_address']);
                                                              } else {
                                                                  echo '<span class="text-muted">N/A</span>'; 
                                                              }
                                                              ?>
                                                          </td>
                                                      <td><span class="badge bg-info"><?= $order['total_items'] ?> items</span></td>
                                                      <td><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></td>

                                                        
                                                        <td>
                                                            <?php
                                                            $statusClass = '';
                                                            switch ($order['status']) {
                                                                case 'pending': $statusClass = 'bg-warning text-dark'; break;
                                                                case 'processing': $statusClass = 'bg-info'; break;
                                                                case 'completed': $statusClass = 'bg-success'; break;
                                                                case 'cancelled': $statusClass = 'bg-danger'; break;
                                                                default: $statusClass = 'bg-secondary';
                                                            }
                                                            ?>
                                                            <span class="badge <?= $statusClass ?>"><?= ucfirst($order['status']) ?></span>
                                                        </td>
                                                        <td><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></td>
                                                        <td>
                                                            

                                                                <button onclick="printInvoice(<?= $order['order_id'] ?>)" class="btn btn-sm btn-outline-success" >
                                                                    <i class="bi "></i> View Details
                                                                </button>
                                                            </div>

                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>






                   

                        
</div>
</div>
</div>
</div>







    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jsPDF for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    
    <!-- Include responsive sidebar functionality -->
    <script src="sidebar-drawer.js"></script>
    
    <script>
        // Export report functionality
        function exportReport() {
            // Show loading
            Swal.fire({
                title: 'Exporting Report...',
                text: 'Please wait while we prepare your CSV file.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Get current filter parameters
            const params = new URLSearchParams(window.location.search);
            const dateFilter = params.get('date_filter') || 'all';
            const statusFilter = params.get('status_filter') || 'all';
            const distributorFilter = params.get('distributor_filter') || 'all';
            
            // Create CSV content
            let csvContent = "Order ID,Distributor,Outlet,Items,Total Amount,Status,Order Date\n";
            
            // Get table body
            const tableBody = document.querySelector('table tbody');
            if (!tableBody) {
                Swal.fire({
                    icon: 'error',
                    title: 'No Data',
                    text: 'No order data available to export.'
                });
                return;
            }
            
            // Get table rows
            const tableRows = tableBody.querySelectorAll('tr');
            
            if (tableRows.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Orders',
                    text: 'No orders found to export. Try adjusting your filters.'
                });
                return;
            }
            
            // Process each row
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 7) { // Make sure we have enough columns (excluding Actions column)
                    // Extract and clean data from each cell
                    const orderId = cells[0].textContent.trim().replace(/"/g, '""');
                    const distributor = cells[1].textContent.trim().replace(/"/g, '""');
                    const outlet = cells[2].textContent.trim().replace(/"/g, '""');
                    const items = cells[3].textContent.trim().replace(/"/g, '""');
                    const amount = cells[4].textContent.trim().replace(/"/g, '""');
                    const status = cells[5].textContent.trim().replace(/"/g, '""');
                    const date = cells[6].textContent.trim().replace(/"/g, '""');
                    
                    // Add row to CSV (properly escaped)
                    csvContent += `"${orderId}","${distributor}","${outlet}","${items}","${amount}","${status}","${date}"\n`;
                }
            });
            
            // Create filename with current date and filters
            const today = new Date().toISOString().split('T')[0];
            let filename = `order_report_${today}`;
            
            // Add filter info to filename
            if (dateFilter !== 'all') {
                filename += `_${dateFilter}`;
            }
            if (statusFilter !== 'all') {
                filename += `_${statusFilter}`;
            }
            filename += '.csv';
            
            // Create and download the file
            try {
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                
                if (link.download !== undefined) {
                    const url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', filename);
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    // Clean up
                    URL.revokeObjectURL(url);
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Export Successful!',
                        text: `Order report has been downloaded as ${filename}`,
                        timer: 3000,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error('Download not supported');
                }
            } catch (error) {
                console.error('Export error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Export Failed',
                    text: 'Failed to export the report. Please try again.'
                });
            }
        }
        
        // Clear date filter function
        function clearDateFilter() {
            document.getElementById('date_from').value = '';
            document.getElementById('date_to').value = '';
            document.getElementById('status_filter').value = 'all';
            
            // Submit the form to refresh with cleared filters
            const form = document.querySelector('form');
            form.submit();
        }
        
        // Set date presets function
        function setDatePreset(period, buttonElement) {
            const today = new Date();
            let fromDate, toDate;
            
            switch (period) {
                case 'today':
                    fromDate = toDate = today.toISOString().split('T')[0];
                    break;
                case 'week':
                    // Calculate start of current week (Monday-based)
                    const startOfWeek = new Date(today);
                    const day = startOfWeek.getDay();
                    const diff = startOfWeek.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
                    startOfWeek.setDate(diff);
                    fromDate = startOfWeek.toISOString().split('T')[0];
                    toDate = new Date().toISOString().split('T')[0];
                    break;
                case 'month':
                    // First day of current month
                    fromDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                    toDate = new Date().toISOString().split('T')[0];
                    break;
                case 'year':
                    // First day of current year
                    fromDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                    toDate = new Date().toISOString().split('T')[0];
                    break;
            }
            
            // Show loading feedback
            if (buttonElement) {
                const originalText = buttonElement.innerHTML;
                buttonElement.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                buttonElement.disabled = true;
                
                // Restore button after a brief delay
                setTimeout(() => {
                    buttonElement.innerHTML = originalText;
                    buttonElement.disabled = false;
                }, 1000);
            }
            
            document.getElementById('date_from').value = fromDate;
            document.getElementById('date_to').value = toDate;
            
            // Highlight the selected preset button
            document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
            if (buttonElement) {
                buttonElement.classList.add('active');
            }
            
            // Auto-submit the form after setting preset
            setTimeout(() => {
                document.querySelector('form').submit();
            }, 200);
        }
        
        // Auto-submit form when filters change
        document.addEventListener('DOMContentLoaded', function() {
            const filterSelects = document.querySelectorAll('#date_filter, #status_filter, #distributor_filter');
            filterSelects.forEach(select => {
                select.addEventListener('change', function() {
                    // Optional: Auto-submit form when filter changes
                    // this.form.submit();
                });
            });
            
            // Highlight active preset button based on current date filters
            highlightActivePreset();
        });
        
        // Function to highlight active preset based on current date filters
        function highlightActivePreset() {
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            
            if (!dateFrom || !dateTo) return;
            
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            
            // Check if it matches any preset
            let matchingPreset = null;
            
            // Today check
            if (dateFrom === todayStr && dateTo === todayStr) {
                matchingPreset = 'today';
            }
            // Week check (Monday-based)
            else {
                const startOfWeek = new Date(today);
                const day = startOfWeek.getDay();
                const diff = startOfWeek.getDate() - day + (day === 0 ? -6 : 1);
                startOfWeek.setDate(diff);
                const weekStart = startOfWeek.toISOString().split('T')[0];
                
                if (dateFrom === weekStart && dateTo === todayStr) {
                    matchingPreset = 'week';
                }
            }
            // Month check
            if (!matchingPreset) {
                const monthStart = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                if (dateFrom === monthStart && dateTo === todayStr) {
                    matchingPreset = 'month';
                }
            }
            // Year check
            if (!matchingPreset) {
                const yearStart = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
                if (dateFrom === yearStart && dateTo === todayStr) {
                    matchingPreset = 'year';
                }
            }
            
            // Highlight the matching button
            if (matchingPreset) {
                const buttons = document.querySelectorAll('.btn-group .btn');
                buttons.forEach(btn => {
                    const onclick = btn.getAttribute('onclick');
                    if (onclick && onclick.includes(`'${matchingPreset}'`)) {
                        btn.classList.add('active');
                    }
                });
            }
        }
        
        // Print Invoice Function
        function printInvoice(orderId) {
            // Show loading
            Swal.fire({
                title: 'Generating Invoice...',
                text: 'Please wait while we prepare your printable invoice.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Fetch order details
            fetch(`fetch_order_details.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.close();
                        showInvoiceModal(data.order, data.items);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to fetch order details.'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load order details. Please try again.'
                    });
                });
        }

        function showInvoiceModal(order, items) {
            const modal = document.getElementById('invoiceModal');
            
            // Store current invoice data for PDF generation
            currentInvoiceOrder = order;
            currentInvoiceItems = items;
            
            // Calculate totals
            let subtotal = 0;
            items.forEach(item => {
                subtotal += parseFloat(item.total_price_raw || item.total_price);
            });
            
            // Populate invoice data
            document.getElementById('invoiceOrderId').textContent = order.order_id;
            document.getElementById('invoiceDate').textContent = new Date(order.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'numeric',
                day: 'numeric'
            });
            document.getElementById('distributorName').textContent = order.customer_name || 'Unknown Customer';
            document.getElementById('distributorOutlet').textContent = order.customer_address || 'N/A';
            document.getElementById('distributorContact').textContent = order.customer_contact || 'N/A';
            
            // Set status badge with appropriate styling
            const statusElement = document.getElementById('orderStatus');
            statusElement.textContent = order.status;
            statusElement.className = 'badge';
            
            switch (order.status.toLowerCase()) {
                case 'pending':
                    statusElement.classList.add('bg-warning', 'text-dark');
                    break;
                case 'processing':
                    statusElement.classList.add('bg-info');
                    break;
                case 'completed':
                    statusElement.classList.add('bg-success');
                    break;
                case 'cancelled':
                    statusElement.classList.add('bg-danger');
                    break;
                default:
                    statusElement.classList.add('bg-secondary');
            }
            
            // Populate items table with new column structure
            const itemsTableBody = document.getElementById('invoiceItems');
            itemsTableBody.innerHTML = '';
            
            const orderDate = new Date(order.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'numeric',
                day: 'numeric'
            });
            
            items.forEach(item => {
                const row = `
                    <tr style="font-family: 'Times New Roman', serif;">
                        <td class="text-center">#${order.order_id}</td>
                        <td class="text-center">${orderDate}</td>
                        <td class="text-center">${item.product_name}</td>
                        <td class="text-center">${item.quantity}</td>
                        <td class="text-center">₱${parseFloat(item.unit_price_raw || item.unit_price).toFixed(2)}</td>
                        <td class="text-center">₱${parseFloat(item.total_price_raw || item.total_price).toFixed(2)}</td>
                    </tr>
                `;
                itemsTableBody.innerHTML += row;
            });
            
            // Show modal
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        }

        function printInvoiceContent() {
            const printContent = document.getElementById('invoicePrintContent').innerHTML;
            const originalContent = document.body.innerHTML;
            
            // Create print window
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Invoice</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            margin: 20px;
                            color: #333;
                        }
                        .invoice-header { 
                            text-align: center; 
                            margin-bottom: 30px;
                            border-bottom: 2px solid #410101;
                            padding-bottom: 20px;
                        }
                        .company-logo {
                            max-width: 150px;
                            margin-bottom: 10px;
                        }
                        .invoice-details { 
                            margin-bottom: 30px;
                        }
                        .invoice-details table {
                            width: 100%;
                            margin-bottom: 20px;
                        }
                        .invoice-details td {
                            padding: 8px;
                            vertical-align: top;
                        }
                        .items-table { 
                            width: 100%; 
                            border-collapse: collapse; 
                            margin-bottom: 30px;
                        }
                        .items-table th, .items-table td { 
                            border: 1px solid #ddd; 
                            padding: 12px; 
                            text-align: left;
                        }
                        .items-table th { 
                            background-color: #410101; 
                            color: white;
                            font-weight: bold;
                        }
                        .text-center { text-align: center; }
                        .text-end { text-align: right; }
                        .total-section {
                            float: right;
                            width: 300px;
                            margin-top: 20px;
                        }
                        .total-section table {
                            width: 100%;
                            border-collapse: collapse;
                        }
                        .total-section td {
                            padding: 8px;
                            border-bottom: 1px solid #ddd;
                        }
                        .total-section .final-total {
                            font-weight: bold;
                            font-size: 1.2em;
                            background-color: #f8f9fa;
                        }
                        .footer {
                            margin-top: 60px;
                            padding-top: 20px;
                            border-top: 1px solid #ddd;
                            text-align: center;
                            color: #666;
                        }
                        @media print {
                            body { margin: 0; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }

        // Add loading state to filter button and date validation
        document.querySelector('form').addEventListener('submit', function(e) {
            // Date range validation
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            
            if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date Range',
                    text: 'The "From" date cannot be later than the "To" date.',
                    confirmButtonColor: '#410101'
                });
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Filtering...';
            submitBtn.disabled = true;
            
            // Re-enable after a short delay (in case of quick response)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });

        // Global variables to store current invoice data for PDF generation
        let currentInvoiceOrder = null;
        let currentInvoiceItems = null;

        // Download PDF from modal - Optimized version
        function downloadInvoicePDF() {
            if (!currentInvoiceOrder || !currentInvoiceItems) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No invoice data available for PDF generation.'
                });
                return;
            }

            // Show loading with shorter timeout
            Swal.fire({
                title: 'Generating PDF...',
                text: 'Please wait while we prepare your PDF invoice.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Use requestAnimationFrame for better performance
            requestAnimationFrame(() => {
                try {
                    generateInvoicePDF(currentInvoiceOrder, currentInvoiceItems);
                    Swal.close();
                    Swal.fire({
                        icon: 'success',
                        title: 'PDF Generated!',
                        text: 'Your PDF invoice has been downloaded successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } catch (error) {
                    console.error('PDF generation error:', error);
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'PDF Generation Failed',
                        text: 'Failed to generate PDF. Please try again.'
                    });
                }
            });
        }

        function generateInvoicePDF(order, items) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Pre-calculate values to avoid repeated operations
            const distributorName = order.customer_name || 'Unknown Customer';
            const outlet = order.customer_address || 'N/A';
            const orderDate = new Date(order.created_at).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'numeric',
                day: 'numeric'
            });
            const subtotal = items.reduce((sum, item) => sum + parseFloat(item.total_price_raw || item.total_price), 0);
            
            // Header - optimized font operations
            doc.setFontSize(12);
            doc.setTextColor(218, 165, 32);
            doc.setFont('helvetica', 'bold');
            doc.addImage('ASSETS/BZ.jpg', 'JPEG', 95, 15, 20, 20);

            // Company header with minimal font changes
            doc.setFontSize(13);
            doc.setTextColor(0, 0, 0);
            doc.setFont('times', 'bold');
            doc.text('BZ MOMS HEALTH AND WELLNESS CENTER', 105, 40, { align: 'center' });
  
            doc.setFontSize(10);
            doc.setFont('times', 'normal');
            doc.text('Purok 8, Calao East (Pob.), City of Santiago, Isabela, Philippines 3311', 105, 45, { align: 'center' });
          
            doc.setFont('times', 'bold');
            doc.text('HAZEL C. BOLISAY - Proprietress', 105, 50, { align: 'center' });

            // Distributor information - batch text operations
            doc.setFontSize(10);
            doc.setFont('times', 'bold');
            doc.text('DISTRIBUTOR INFORMATION', 30, 60);
          
            doc.setFont('times', 'normal');
            const distributorInfo = [
                `NAME: ${distributorName}`,
                `CONTACT: ${order.customer_contact || 'N/A'}`,
                `OUTLET: ${outlet}`,
            ];

            doc.setFont('times', 'normal');
            
            // Left side distributor info
            const leftInfo = [
                `NAME: ${distributorName}`,
                `CONTACT: ${order.customer_contact || 'N/A'}`,
                `OUTLET: ${outlet}`
            ];
            
            leftInfo.forEach((text, index) => {
                doc.text(text, 30, 65 + (index * 5));
            });
            
            // Right side order info
            const rightInfo = [
                `INVOICE #: ${order.order_id}`,
                `DATE: ${orderDate}`,
                `STATUS: ${order.status}`
            ];
            
            rightInfo.forEach((text, index) => {
                doc.text(text, 120, 65 + (index * 5));
            });
            
            distributorInfo.forEach((text, index) => {
                doc.text(text, 30, 65 + (index * 5));
            });
        
            
            doc.setFontSize(13);
            doc.setTextColor(0, 0, 0); // Black
            doc.setFont('times', 'bold');
            doc.text('SALES INVOICE', 105, 90, { align: 'center' });

            // Calculate table start position - simplified
            const tableStartY = 95;
            
            // Prepare table data - items only (no totals in grid)
            const tableData = items.map(item => [
                `#${order.order_id}`,
                orderDate,
                item.product_name,
                item.quantity.toString(),
                `₱${parseFloat(item.unit_price_raw || item.unit_price).toFixed(2)}`,
                `₱${parseFloat(item.total_price_raw || item.total_price).toFixed(2)}`
            ]);
            
            // Add items table without totals in grid
            doc.autoTable({
                startY: tableStartY,
                head: [['Order ID', 'Date', 'Product', 'QTY', 'Unit Price', 'Total']],
                body: tableData,
                theme: 'grid',
                styles: {
                    fontSize: 8,
                    cellPadding: 2
                },
                headStyles: {
                    fillColor: [41, 128, 185],
                    textColor: 255,
                    fontStyle: 'bold'
                },
                alternateRowStyles: {
                    fillColor: [245, 245, 245]
                },
                columnStyles: {
                    0: {cellWidth: 20}, // Order ID column width
                    1: {cellWidth: 25}, // Date column width
                    2: {cellWidth: 55}, // Products column width
                    3: {cellWidth: 15}, // Quantity column width
                    4: {cellWidth: 30}, // Unit Price column width
                    5: {cellWidth: 30}, // Amount column width
                },
                margin: { left: 20, right: 20 }
            });
            
            // Position totals as separate text elements where they would appear in the table
            const finalY = doc.lastAutoTable.finalY;
            const rightColumnX = 180; // Position aligned with the Total column
            const labelColumnX = 150;  // Position aligned with the Unit Price column
            
            // Set styling for totals
            doc.setFontSize(8);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(0, 0, 0);
            
            // Add subtotal (positioned where it would be in the grid)
            doc.text('SUBTOTAL:', labelColumnX, finalY + 8, { align: 'right' });
            doc.text(`₱${subtotal.toFixed(2)}`, rightColumnX, finalY + 8, { align: 'right' });
            
            // Add background color for total amount row
            doc.setFillColor(41, 128, 185); // Same blue color as table header
            doc.rect(20, finalY + 12, 175, 10, 'F'); // Rectangle covering the total amount row
            
            // Add total amount with white text (positioned where it would be in the grid)
            doc.setTextColor(255, 255, 255); // White text for contrast
            doc.text('TOTAL AMOUNT:', labelColumnX, finalY + 18, { align: 'right' });
            doc.text(`₱${subtotal.toFixed(2)}`, rightColumnX, finalY + 18, { align: 'right' });
            
            // Reset text color back to black for footer
            doc.setTextColor(0, 0, 0);
            
            // Update final Y position for footer
            const adjustedFinalY = finalY + 30;
            
            // Footer section - matching the desired format
            doc.setFontSize(9);
            doc.setTextColor(100, 100, 100);
            
            // Add system information line
            doc.setFont('times', 'bold');
            doc.text('BZ MOMS Health and Wellness Center - IM-DISTRACK Inventory System', 105, adjustedFinalY + 20, { align: 'center' });
            
            // Generation timestamp with "at" format like the image
            const generatedOn = new Date().toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            const generatedTime = new Date().toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            doc.text(`Report Generated on ${generatedOn} at ${generatedTime}`, 105, adjustedFinalY + 25, { align: 'center' });
            
            // Save the PDF with optimized filename generation
            const filename = `Invoice_${order.order_id}_${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(filename);
        }
    </script>

    <!-- Invoice Modal -->
    <div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoiceModalLabel">
                        <i class="bi bi-receipt"></i> Invoice Preview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="invoicePrintContent">
                        <!-- Invoice Header -->
                        <div class="invoice-header text-center mb-4">
                            <img src="ASSETS/BZ.jpg" alt="Company Logo" class="company-logo mb-3" style="max-width: 100px;">
                            <h5 style="font-family: 'Times New Roman', serif; font-weight: bold; margin-bottom: 5px;">BZ MOMS HEALTH AND WELLNESS CENTER</h5>
                            <p style="font-family: 'Times New Roman', serif; margin: 2px 0; font-size: 0.9rem;">Purok 8, Calao East (Pob.), City of Santiago, Isabela, Philippines 3311</p>
                            <p style="font-family: 'Times New Roman', serif; font-weight: bold; margin: 2px 0; font-size: 0.9rem;">HAZEL C. BOLISAY - Proprietress</p>
                        </div>

                        <!-- Distributor Information Section -->
                        <div class="mb-3">
                            <h6 style="font-family: 'Times New Roman', serif; font-weight: bold; margin-bottom: 10px;">DISTRIBUTOR INFORMATION</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p style="font-family: 'Times New Roman', serif; margin: 2px 0; font-size: 0.9rem;"><strong>NAME:</strong> <span id="distributorName">--</span></p>
                                    <p style="font-family: 'Times New Roman', serif; margin: 2px 0; font-size: 0.9rem;"><strong>CONTACT:</strong> <span id="distributorContact">N/A</span></p>
                                    <p style="font-family: 'Times New Roman', serif; margin: 2px 0; font-size: 0.9rem;"><strong>OUTLET:</strong> <span id="distributorOutlet">--</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p style="font-family: 'Times New Roman', serif; margin: 2px 0; font-size: 0.9rem;"><strong>INVOICE #:</strong> <span id="invoiceOrderId">#000</span></p>
                                    <p style="font-family: 'Times New Roman', serif; margin: 2px 0; font-size: 0.9rem;"><strong>DATE:</strong> <span id="invoiceDate">--</span></p>
                                    <p style="font-family: 'Times New Roman', serif; margin: 2px 0; font-size: 0.9rem;"><strong>STATUS:</strong> <span id="orderStatus" class="badge bg-primary">--</span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Sales Invoice Title -->
                        <div class="text-center mb-3">
                            <h5 style="font-family: 'Times New Roman', serif; font-weight: bold;">SALES INVOICE</h5>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered items-table" style="font-family: 'Times New Roman', serif; font-size: 0.9rem;">
                                <thead style="background-color: #2980b9; color: white;">
                                    <tr>
                                        <th class="text-center">Order ID</th>
                                        <th class="text-center">Date</th>
                                        <th class="text-center">Product</th>
                                        <th class="text-center">QTY</th>
                                        <th class="text-center">Unit Price</th>
                                        <th class="text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="invoiceItems">
                                    <!-- Items will be populated here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer -->
                        <div class="footer text-center mt-4 pt-3" style="border-top: 1px solid #ddd;">
                            <p style="font-family: 'Times New Roman', serif; font-weight: bold; margin-bottom: 5px; font-size: 0.9rem;">BZ MOMS Health and Wellness Center - IM-DISTRACK Inventory System</p>
                            <small style="font-family: 'Times New Roman', serif; font-size: 0.8rem;">Report Generated on <?= date('F d, Y') ?> at <?= date('g:i A') ?></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Close
                    </button>
                    <button type="button" class="btn btn-success" onclick="downloadInvoicePDF()">
                        <i class="bi bi-download"></i> Download PDF
                    </button>
                    <button type="button" class="btn btn-primary" onclick="printInvoiceContent()">
                        <i class="bi bi-printer"></i> Print Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>
    
  </body>
</html>
