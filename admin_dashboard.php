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

// Fetch dashboard statistics
$stats = [];

// Count total orders
$orderResult = $conn->query("SELECT COUNT(*) as total_orders FROM orders");
$stats['total_orders'] = $orderResult ? $orderResult->fetch_assoc()['total_orders'] : 0;

// Count orders for this month
$thisMonthResult = $conn->query("SELECT COUNT(*) as monthly_orders FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$stats['monthly_orders'] = $thisMonthResult ? $thisMonthResult->fetch_assoc()['monthly_orders'] : 0;

// Count available products (products with quantity > 0)
$productsResult = $conn->query("SELECT COUNT(*) as available_products FROM products WHERE quantity > 0");
$stats['available_products'] = $productsResult ? $productsResult->fetch_assoc()['available_products'] : 0;

// Count out of stock products (products with quantity = 0)
$outOfStockResult = $conn->query("SELECT COUNT(*) as out_of_stock_products FROM products WHERE quantity = 0");
$stats['out_of_stock_products'] = $outOfStockResult ? $outOfStockResult->fetch_assoc()['out_of_stock_products'] : 0;

// Count total distributors
$distributorsResult = $conn->query("SELECT COUNT(*) as total_distributors FROM distributor_signup");
$stats['total_distributors'] = $distributorsResult ? $distributorsResult->fetch_assoc()['total_distributors'] : 0;


// Fetch top distributors by total items bought (points)
$topDistributorsQuery = "SELECT ds.distrib_fname, ds.distrib_lname, ds.distrib_outlet,
    COALESCE(SUM(oi.quantity), 0) AS points
FROM distributor_signup ds
LEFT JOIN orders o ON ds.distributor_id = o.distributor_id AND o.status IN ('completed', 'received')
LEFT JOIN order_items oi ON o.order_id = oi.order_id
GROUP BY ds.distributor_id, ds.distrib_fname, ds.distrib_lname, ds.distrib_outlet
ORDER BY points DESC
LIMIT 3";
$topDistributorsResult = $conn->query($topDistributorsQuery);
$topDistributors = $topDistributorsResult ? $topDistributorsResult->fetch_all(MYSQLI_ASSOC) : [];

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
                    <h2 class="page-title">
                    <i class="fa fa-home me-3"></i>
                    DASHBOARD
                    </h2>

                <div class="row">
                    <!-- Order Statistics Card -->
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-box-seam me-2" style= "color: #748DAE;"></i>
                                    Orders
                                </h5>
                                <div class="d-flex justify-content-between mt-3">
                                    <h2 class="mb-0"><?php echo number_format($stats['monthly_orders']); ?></h2>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
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
                                <p class="text-muted small mt-2">Total orders placed (All time: <?php echo number_format($stats['total_orders']); ?>)</p>
                                
                            </div>
                            <div class="card-footer bg-white border-0">
                                <a href="view_order.php" class="btn btn-sm w-100" style="background-color: #748DAE; border-color: #748DAE; color: white;">View Orders</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Products Card -->
                    <div class="col-md-3 mb-4">
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
                                <a href="trial_view.php" class="btn btn-sm w-100" style="background-color: #437057; border-color: #437057; color: white;">Browse Products</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-bag-x me-2" style="color: #A34343;"></i>
                                    Out of Stock Products
                                </h5>
                                <div class="d-flex justify-content-between mt-3">
                                    <h2 class="mb-0"><?php echo number_format($stats['out_of_stock_products']); ?></h2>
                                    <span class="badge bg-danger align-self-center">Out of Stock</span>
                                </div>
                                <p class="text-muted small mt-2">Products needs to be restocked</p>

                            </div>
                            <div class="card-footer bg-white border-0">
                                <a href="trial_view.php" class="btn btn-sm w-100" style="background-color: #A34343; border-color: #A34343; color: white;">Browse Products</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-person-badge me-2" style="color: #DA8359;"></i>
                                    Distributor
                                </h5>
                                <div class="text-center mt-3">

                                    <div class="mb-3" style="margin-top: -18px;">
                                        <i class="bi bi-person-circle" style="font-size: 2rem; color: #DA8359; "></i>
                                        <h2 class="mb-0 d-inline-block" style="margin-left: 20px;"><?php echo number_format($stats['total_distributors']); ?></h2>
                                    </div>
                                </div>
                                 <p class="text-muted small mt-2" style="margin-top: 20px;;">Number of active distributors</p>

                            </div>
                            
                            <div class="card-footer bg-white border-0">
                                <a href="distributor_list.php" class="btn btn-sm w-100" style="background-color: #DA8359; border-color: #DA8359; color: white;">View Distributor List</a>
                            </div>

                        </div>
                    </div>



                    
                                    
               <!-- Top Distributors Section -->
                
                                      
                        
                                            

                        <div class="modern-card">
                            <div class="section-header" style="margin-bottom: -9px; padding-top: 25px; margin-left: 28px; margin-right: 28px;">
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="bi bi-trophy"></i>
                                        TOP PERFORMING DISTRIBUTORS
                                    </h6>

                                    
                                </div>
                                </div>
                                <div class="form-section" style="margin-left: 28px; margin-right: 28px;">
                                    <div class="table-responsive">
                                        <table class="table table-hover modern-table">
                                            <thead class="table-light">
                                                <tr>
                                                <th class="border-0 text-center">
                                                    <i class="bi bi-hash me-1"></i>Rank
                                                </th>
                                                <th class="border-0 text-center">
                                                    <i class="bi bi-person me-1"></i>Distributor Name
                                                </th>
                                                <th class="border-0 text-center">
                                                    <i class="bi bi-geo-alt me-1"></i>Outlet
                                                </th> 
                                                <th class="border-0 text-center">
                                                    <i class="bi bi-award me-1"></i>points
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($topDistributors)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No distributors found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($topDistributors as $index => $distributor): ?>
                                                <tr>
                                                    <td>
                                                        <?php if ($index == 0): ?>
                                                            <i class="bi bi-award-fill me-2" style="color: #FFD700; font-size: 1.2em;"></i>
                                                            <?php echo $index + 1; ?>
                                                        <?php elseif ($index == 1): ?>
                                                            <i class="bi bi-award-fill me-2" style="color: #C0C0C0; font-size: 1.2em;"></i>
                                                            <?php echo $index + 1; ?>
                                                        <?php elseif ($index == 2): ?>
                                                            <i class="bi bi-award-fill me-2" style="color: #CD7F32; font-size: 1.2em;"></i>
                                                            <?php echo $index + 1; ?>
                                                        <?php else: ?>
                                                            <?php echo $index + 1; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($distributor['distrib_fname'] . ' ' . $distributor['distrib_lname']); ?></td>
                                                    <td><?php echo !empty($distributor['distrib_outlet']) ? htmlspecialchars($distributor['distrib_outlet']) : 'N/A'; ?></td>
                                                    <td><?php echo number_format($distributor['points']); ?></td>
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
</div>
</div>







    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Include responsive sidebar functionality -->
    <script src="sidebar-drawer.js"></script>
    
    <script>
    // Function to update order statistics based on time period
    function updateOrderStats(period) {
        fetch('get_order_stats.php?period=' + period)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the order count
                    document.querySelector('.card-body h2').textContent = data.count;
                    
                    // Update the dropdown button text
                    const dropdownBtn = document.querySelector('.dropdown-toggle');
                    switch(period) {
                        case 'today':
                            dropdownBtn.textContent = 'Today';
                            break;
                        case 'week':
                            dropdownBtn.textContent = 'This Week';
                            break;
                        case 'month':
                            dropdownBtn.textContent = 'This Month';
                            break;
                        case 'year':
                            dropdownBtn.textContent = 'This Year';
                            break;
                    }
                } else {
                    console.error('Error fetching stats:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    // Function to update distributor statistics based on time period
    function updateDistributorStats(period) {
        fetch('get_distributor_stats.php?period=' + period)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the table content
                    const tableBody = document.querySelector('.modern-table tbody');
                    tableBody.innerHTML = '';
                    
                    if (data.distributors.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="4" class="text-center">No distributors found</td></tr>';
                    } else {
                        data.distributors.forEach((distributor, index) => {
                            let rankIcon = '';
                            if (index === 0) {
                                rankIcon = '<i class="bi bi-award-fill me-2" style="color: #FFD700; font-size: 1.2em;"></i>';
                            } else if (index === 1) {
                                rankIcon = '<i class="bi bi-award-fill me-2" style="color: #C0C0C0; font-size: 1.2em;"></i>';
                            } else if (index === 2) {
                                rankIcon = '<i class="bi bi-award-fill me-2" style="color: #CD7F32; font-size: 1.2em;"></i>';
                            }
                            
                            tableBody.innerHTML += `
                                <tr>
                                    <td>${rankIcon}${index + 1}</td>
                                    <td>${distributor.name}</td>
                                    <td>${distributor.outlet}</td>
                                    <td>${distributor.order_count}</td>
                                </tr>
                            `;
                        });
                    }
                    
                    // Update the dropdown button text
                    const selectedPeriod = document.getElementById('selected-period');
                    switch(period) {
                        case 'today':
                            selectedPeriod.textContent = 'Today';
                            break;
                        case 'week':
                            selectedPeriod.textContent = 'This Week';
                            break;
                        case 'month':
                            selectedPeriod.textContent = 'This Month';
                            break;
                        case 'year':
                            selectedPeriod.textContent = 'This Year';
                            break;
                    }
                } else {
                    console.error('Error fetching distributor stats:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    </script>

   



      



    

    
    
  </body>
</html>
