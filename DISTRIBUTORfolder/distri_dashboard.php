<?php
if (!isset($_SESSION)) {
    session_start();
}
if (!isset($_SESSION['distributor_id'])) {
    header('Location: ../distri_login.php');
    exit();
}

// Fetch statistics
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get available products count
$productsResult = $conn->query("SELECT COUNT(*) as available_products FROM products WHERE quantity > 0");
$stats['available_products'] = $productsResult ? $productsResult->fetch_assoc()['available_products'] : 0;

// Get order statistics for this distributor (default to current month)
$distributor_id = $_SESSION['distributor_id'];
$orderStatsQuery = "SELECT COUNT(*) as total_orders FROM orders WHERE distributor_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$stmt = $conn->prepare($orderStatsQuery);
$stmt->bind_param("i", $distributor_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_orders'] = $result ? $result->fetch_assoc()['total_orders'] : 0;
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DISTRIBUTOR DASHBOARD</title>
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

             <!-- Page Header -->
                    <h2 class="page-title">
                    <i class="fa fa-home me-3"></i>
                    DASHBOARD
                    </h2>
                
                <div class="row">
                    <!-- Order Statistics Card -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-box-seam me-2" style= "color: #748DAE;"></i>
                                    Orders
                                </h5>
                                 <div class="d-flex justify-content-between mt-3">
                                    <h2 class="mb-0" id="orderCount"><?php echo number_format($stats['total_orders']); ?></h2>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" id="periodFilter">
                                            This Month
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="updateOrderStats('today')">Today</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateOrderStats('week')">This Week</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateOrderStats('month')">This Month</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="updateOrderStats('year')">This Year</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <p class="text-muted small mt-2">Total orders placed </p>
                                
                            </div>
                            <div class="card-footer bg-white border-0">
                                <a href="distri_view_order.php" class="btn btn-sm w-100" style="background-color: #748DAE; border-color: #748DAE; color: white;">View Orders</a>
                            </div>
                        </div>
                    </div>

                    <!-- Products Card -->
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-bag-check me-2" style="color: #437057;"></i>
                                    Available Products
                                </h5>
                                <div class="d-flex justify-content-between mt-3">
                                    <h2 class="mb-0"><?php echo number_format($stats['available_products']); ?></h2>
                                    <span class="badge bg-success align-self-center">In Stock</span>
                                </div>
                                <p class="text-muted small mt-2">Products available for distribution</p>
                                
                            </div>
                            <div class="card-footer bg-white border-0">
                                <a href="distri_products.php" class="btn btn-sm w-100" style="background-color: #437057; border-color: #437057; color: white;">Browse Products</a>
                            </div>
                        </div>
                    </div>

                    <!-- Points Card -->

                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-star-fill me-2" style="color: #A34343;"></i>
                                    Earned Points
                                </h5>
                                <div class="text-center mt-3">
                                    <div class="mb-3">
                                        <i class="bi bi-award-fill" style="font-size: 3rem; color: #A34343;"></i>
                                        <?php
                                        $conn = new mysqli("localhost", "root", "", "inventory_negrita");
                                        $points = 0;
                                        if (!$conn->connect_error && isset($_SESSION['distributor_id'])) {
                                            $stmt = $conn->prepare("SELECT SUM(points) AS total_points FROM orders WHERE distributor_id = ? AND status IN ('completed', 'received')");
                                            $stmt->bind_param("i", $_SESSION['distributor_id']);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            $row = $result->fetch_assoc();
                                            $points = $row ? intval($row['total_points']) : 0;
                                            $stmt->close();
                                        }
                                        $conn->close();
                                        ?>
                                        <h2 class="mb-0 d-inline-block" style="margin-left: 20px;"><?php echo $points; ?></h2>
                                    </div>
                                    <p class="text-muted small mt-2">Total points earned from successful orders</p>
                                </div>
                               
                                
                            </div>
                        </div>
                    </div>
                    
                    <!-- Distributor Order List Below Cards -->

                                                 <div class="mt-4">
                                                     <div class="d-flex justify-content-between align-items-center mb-3">
                                                         <h4 class="mb-0"><i class="bi bi-list-check me-2"></i>My Orders</h4>
                                                         <div class="d-flex gap-2">
                                                             <input type="date" id="dateFrom" class="form-control form-control-sm" style="width: auto;" placeholder="From Date">
                                                             <input type="date" id="dateTo" class="form-control form-control-sm" style="width: auto;" placeholder="To Date">
                                                             <button class="btn btn-primary btn-sm" onclick="filterOrdersByDate()">
                                                                 <i class="bi bi-funnel"></i> Filter
                                                             </button>
                                                             <button class="btn btn-outline-secondary btn-sm" onclick="clearDateFilter()">
                                                                 <i class="bi bi-x-circle"></i> Clear
                                                             </button>
                                                         </div>
                                                     </div>
                                                     <!-- Shopee-style tabs -->
                                                     <ul class="nav nav-tabs mb-3" id="orderStatusTabs">
                                                         <li class="nav-item"><a class="nav-link active" href="#" data-status="" onclick="setStatusFilter('')">All <span id="countAll" style="background:none;color:gray !important;padding:2px 8px;border-radius:8px;">0</span></a></li>
                                                         <li class="nav-item"><a class="nav-link" href="#" data-status="pending" onclick="setStatusFilter('pending')">Pending <span id="countPending" style="background:none;color:gray !important;padding:2px 8px;border-radius:8px;">0</span></a></li>
                                                         <li class="nav-item"><a class="nav-link" href="#" data-status="processing" onclick="setStatusFilter('processing')">To Process <span id="countProcessing" style="background:none;color:gray !important;padding:2px 8px;border-radius:8px;">0</span></a></li>
                                                         <li class="nav-item"><a class="nav-link" href="#" data-status="completed" onclick="setStatusFilter('completed')">Processed <span id="countCompleted" style="background:none;color:gray !important;padding:2px 8px;border-radius:8px;">0</span></a></li>
                                                         <li class="nav-item"><a class="nav-link" href="#" data-status="cancelled" onclick="setStatusFilter('cancelled')">Cancelled <span id="countCancelled" style="background:none;color:gray !important;padding:2px 8px;border-radius:8px;">0</span></a></li>
                                                     </ul>
                                                     <div id="ordersList">
                                                         <div class="text-center text-muted">No orders found.</div>
                                                     </div>
                                                 </div>

                            <script>
                            let currentStatus = '';
                            function setStatusFilter(status) {
                                currentStatus = status;
                                document.querySelectorAll('#orderStatusTabs .nav-link').forEach(tab => tab.classList.remove('active'));
                                const activeTab = Array.from(document.querySelectorAll('#orderStatusTabs .nav-link')).find(tab => tab.getAttribute('data-status') === status);
                                if (activeTab) activeTab.classList.add('active');
                                loadOrders();
                            }
                            let allOrders = [];
                            async function loadOrders() {
                                let url = `../fetch_distributor_orders.php?`;
                                console.log('Loading orders from:', url);
                                try {
                                    const response = await fetch(url);
                                    console.log('Response status:', response.status);
                                    
                                    if (!response.ok) {
                                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                                    }
                                    
                                    const data = await response.json();
                                    console.log('Received data:', data);
                                    
                                    allOrders = data.orders || [];
                                    updateOrderCounts(allOrders);
                                    const ordersList = document.getElementById('ordersList');
                                    ordersList.innerHTML = '';
                                    let filteredOrders = allOrders;
                                    
                                    // Apply status filter
                                    if (currentStatus) {
                                        filteredOrders = filteredOrders.filter(o => o.status === currentStatus);
                                    }
                                    
                                    // Apply date filter
                                    if (dateFrom || dateTo) {
                                        filteredOrders = filteredOrders.filter(order => {
                                            return isOrderInDateRange(order.created_at);
                                        });
                                    }
                                    if (data.success) {
                                        if (filteredOrders.length > 0) {
                                            filteredOrders.forEach(order => {
                                                const card = document.createElement('div');
                                                card.className = 'card mb-3';
                                                let itemsHtml = '';
                                                if (order.items && order.items.length > 0) {
                                                    order.items.forEach(item => {
                                                        itemsHtml += `
                                                            <div class="d-flex align-items-center mb-2">
                                                                <img src="../${item.product_image}" alt="Product" class="img-fluid rounded me-2" style="max-width: 60px; max-height: 60px;">
                                                                <div>
                                                                    <strong>${item.product_name}</strong><br>
                                                                    Qty: ${item.quantity}
                                                                </div>
                                                            </div>
                                                        `;
                                                    });
                                                } else {
                                                    itemsHtml = '<div class="text-muted">No products found for this order.</div>';
                                                }
                                                let cancelBtnHtml = '';
                                                let debugStatusHtml = ``;
                                                if (order.status === 'pending') {
                                                    cancelBtnHtml = `<button class="btn btn-danger btn-sm mt-2 cancel-order-btn" data-order-id="${order.order_id}">Cancel</button>`;
                                                }
                                                card.innerHTML = `
                                                    <div class="card-body">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-4 text-start">
                                                                <div><strong>Order ID:</strong> ${order.order_id}</div>
                                                                <div><strong>Order Date:</strong> ${new Date(order.created_at).toLocaleDateString('en-US', { 
                                                                    year: 'numeric', 
                                                                    month: 'short', 
                                                                    day: 'numeric',
                                                                    hour: '2-digit',
                                                                    minute: '2-digit'
                                                                })}</div>
                                                                <div><strong>Name:</strong> ${order.customer_name}</div>
                                                                <div><strong>Contact:</strong> ${order.customer_contact}</div>
                                                                <div><strong>Address:</strong> ${order.customer_address}</div>
                                                                ${debugStatusHtml}
                                                                ${cancelBtnHtml}
                                                            </div>
                                                            <div class="col-md-4">
                                                                ${itemsHtml}
                                                            </div>
                                                            <div class="col-md-2">
                                                                <strong>Total Price:</strong> ₱${order.total_amount}
                                                            </div>
                                                            <div class="col-md-2">
                                                                <span class="badge ${getStatusBadgeClass(order.status)}">${capitalize(order.status)}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                `;
                                                ordersList.appendChild(card);
                                            });
                                        } else {
                                            ordersList.innerHTML = '<div class="text-center text-muted">No orders found for your account yet.</div>';
                                        }
                                    } else {
                                        ordersList.innerHTML = `<div class="text-center text-danger">Failed to load orders: ${data.message || 'Unknown error'}</div>`;
                                        console.error('Server returned error:', data);
                                    }
                                } catch (err) {
                                    console.error('Error loading orders:', err);
                                    document.getElementById('ordersList').innerHTML = `<div class="text-center text-danger">Failed to load orders: ${err.message}</div>`;
                                }
                            }
                            function updateOrderCounts(orders) {
                                // Apply date filter to orders before counting
                                let filteredOrdersForCount = orders;
                                if (dateFrom || dateTo) {
                                    filteredOrdersForCount = orders.filter(order => {
                                        return isOrderInDateRange(order.created_at);
                                    });
                                }
                                
                                let all = filteredOrdersForCount.length;
                                let pending = filteredOrdersForCount.filter(o => o.status === 'pending').length;
                                let processing = filteredOrdersForCount.filter(o => o.status === 'processing').length;
                                let completed = filteredOrdersForCount.filter(o => o.status === 'completed').length;
                                let cancelled = filteredOrdersForCount.filter(o => o.status === 'cancelled').length;
                                
                                document.getElementById('countAll').textContent = all;
                                document.getElementById('countPending').textContent = pending;
                                document.getElementById('countProcessing').textContent = processing;
                                document.getElementById('countCompleted').textContent = completed;
                                document.getElementById('countCancelled').textContent = cancelled;
                            }
                            function getStatusBadgeClass(status) {
                                switch(status) {
                                    case 'pending': return 'bg-warning';
                                    case 'processing': return 'bg-info';
                                    case 'completed': return 'bg-success';
                                    case 'cancelled': return 'bg-danger';
                                    default: return 'bg-secondary';
                                }
                            }
                            function capitalize(str) {
                                return str.charAt(0).toUpperCase() + str.slice(1);
                            }
                            window.onload = loadOrders;
                            // Cancel order button handler
                            document.addEventListener('click', function(e) {
                                if (e.target.classList.contains('cancel-order-btn')) {
                                    const orderId = e.target.getAttribute('data-order-id');
                                    
                                    Swal.fire({
                                        title: 'Cancel Order?',
                                        text: 'Are you sure you want to cancel this order? This action cannot be undone.',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonColor: '#d33',
                                        cancelButtonColor: '#3085d6',
                                        confirmButtonText: 'Yes, Cancel Order',
                                        cancelButtonText: 'No, Keep Order'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            // Show loading state
                                            Swal.fire({
                                                title: 'Cancelling Order...',
                                                text: 'Please wait while we process your cancellation.',
                                                icon: 'info',
                                                allowOutsideClick: false,
                                                showConfirmButton: false,
                                                willOpen: () => {
                                                    Swal.showLoading();
                                                }
                                            });
                                            
                                            // Disable button to prevent double-clicking
                                            e.target.disabled = true;
                                            e.target.textContent = 'Cancelling...';
                                            
                                            fetch('cancel_order.php', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json' },
                                                body: JSON.stringify({ order_id: orderId })
                                            })
                                            .then(res => res.json())
                                            .then(data => {
                                                if (data.success) {
                                                    Swal.fire({
                                                        title: 'Order Cancelled!',
                                                        text: 'Your order has been cancelled successfully. The admin will be notified.',
                                                        icon: 'success',
                                                        confirmButtonColor: '#3085d6',
                                                        confirmButtonText: 'OK'
                                                    }).then(() => {
                                                        loadOrders(); // Reload to show updated status
                                                    });
                                                } else {
                                                    Swal.fire({
                                                        title: 'Cancellation Failed',
                                                        text: data.message || 'An error occurred while cancelling the order.',
                                                        icon: 'error',
                                                        confirmButtonColor: '#3085d6'
                                                    });
                                                    // Re-enable button if error
                                                    e.target.disabled = false;
                                                    e.target.textContent = 'Cancel';
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Cancel error:', error);
                                                Swal.fire({
                                                    title: 'Error',
                                                    text: 'An unexpected error occurred while cancelling the order. Please try again.',
                                                    icon: 'error',
                                                    confirmButtonColor: '#3085d6'
                                                });
                                                // Re-enable button if error
                                                e.target.disabled = false;
                                                e.target.textContent = 'Cancel';
                                            });
                                        }
                                    });
                                }
                            });
                            
                            // Function to update order statistics based on selected period
                            async function updateOrderStats(period) {
                                try {
                                    const response = await fetch(`../get_order_stats.php?period=${period}`);
                                    const data = await response.json();
                                    
                                    if (data.success) {
                                        document.getElementById('orderCount').textContent = data.count;
                                        
                                        // Update dropdown button text
                                        const periodTexts = {
                                            'today': 'Today',
                                            'week': 'This Week', 
                                            'month': 'This Month',
                                            'year': 'This Year'
                                        };
                                        document.getElementById('periodFilter').textContent = periodTexts[period];
                                    } else {
                                        console.error('Error fetching order stats:', data.message);
                                    }
                                } catch (error) {
                                    console.error('Error updating order stats:', error);
                                }
                            }
                            
                            // Date filter variables
                            let dateFrom = null;
                            let dateTo = null;
                            
                            // Filter orders by date range
                            function filterOrdersByDate() {
                                const fromInput = document.getElementById('dateFrom');
                                const toInput = document.getElementById('dateTo');
                                
                                dateFrom = fromInput.value;
                                dateTo = toInput.value;
                                
                                if (!dateFrom && !dateTo) {
                                    Swal.fire({
                                        title: 'No Date Selected',
                                        text: 'Please select at least one date (from or to) to filter orders.',
                                        icon: 'warning',
                                        confirmButtonColor: '#3085d6'
                                    });
                                    return;
                                }
                                
                                // Validate date range
                                if (dateFrom && dateTo && new Date(dateFrom) > new Date(dateTo)) {
                                    Swal.fire({
                                        title: 'Invalid Date Range',
                                        text: 'The "From" date cannot be later than the "To" date.',
                                        icon: 'error',
                                        confirmButtonColor: '#3085d6'
                                    });
                                    return;
                                }
                                
                                loadOrders();
                                updateFilterIndicator();
                            }
                            
                            // Clear date filter
                            function clearDateFilter() {
                                document.getElementById('dateFrom').value = '';
                                document.getElementById('dateTo').value = '';
                                dateFrom = null;
                                dateTo = null;
                                loadOrders();
                                updateFilterIndicator();
                            }
                            
                            // Update filter indicator
                            function updateFilterIndicator() {
                                const heading = document.querySelector('h4');
                                const currentText = heading.innerHTML;
                                
                                // Remove existing filter indicator
                                const baseText = '<i class="bi bi-list-check me-2"></i>My Orders';
                                
                                if (dateFrom || dateTo) {
                                    let filterText = '';
                                    if (dateFrom && dateTo) {
                                        filterText = ` <small class="text-primary">(${dateFrom} to ${dateTo})</small>`;
                                    } else if (dateFrom) {
                                        filterText = ` <small class="text-primary">(from ${dateFrom})</small>`;
                                    } else if (dateTo) {
                                        filterText = ` <small class="text-primary">(until ${dateTo})</small>`;
                                    }
                                    heading.innerHTML = baseText + filterText;
                                } else {
                                    heading.innerHTML = baseText;
                                }
                            }
                            
                            // Helper function to check if order date is within filter range
                            function isOrderInDateRange(orderDate) {
                                if (!dateFrom && !dateTo) return true;
                                
                                const orderDateObj = new Date(orderDate);
                                const fromDateObj = dateFrom ? new Date(dateFrom) : null;
                                const toDateObj = dateTo ? new Date(dateTo) : null;
                                
                                // Set time to end of day for 'to' date to include orders from that entire day
                                if (toDateObj) {
                                    toDateObj.setHours(23, 59, 59, 999);
                                }
                                
                                if (fromDateObj && toDateObj) {
                                    return orderDateObj >= fromDateObj && orderDateObj <= toDateObj;
                                } else if (fromDateObj) {
                                    return orderDateObj >= fromDateObj;
                                } else if (toDateObj) {
                                    return orderDateObj <= toDateObj;
                                }
                                
                                return true;
                            }
                            </script>

                        </div>
                    </div>
                </div>







    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Include responsive sidebar functionality -->
    <script src="../sidebar-drawer.js"></script>



    

    
    
  </body>
</html>