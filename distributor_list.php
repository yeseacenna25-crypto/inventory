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

// Fetch current admin name for display
$stmt = $conn->prepare("SELECT admin_fname, admin_mname, admin_lname FROM admin_signup WHERE admin_id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$current_admin = $result->fetch_assoc();

$fullName = $current_admin ? $current_admin['admin_fname']: "Admin";
$stmt->close();

// Fetch all admin data for distributor list
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT distributor_id, distrib_fname, distrib_mname, distrib_lname, distrib_extension, distrib_gender, distrib_birthday, distrib_age, 
               distrib_civil_status, distrib_address, distrib_outlet, distrib_contact_number, distrib_email, distrib_username, created_at 
        FROM distributor_signup WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (distrib_fname LIKE ? OR distrib_mname LIKE ? OR distrib_lname LIKE ? OR distrib_username LIKE ? OR distrib_email LIKE ? OR distrib_outlet LIKE ?)";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($search)) {
    $searchTerm = "%$search%";
    $stmt->bind_param("ssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

$stmt->execute();
$result = $stmt->get_result();
$distributors = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DISTRIBUTOR LIST</title>
    <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
    />
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
      .swal-wide {
        max-width: 95% !important;
      }
      
      .purchase-summary {
        border: 1px solid #dee2e6;
      }
      
      .status-group {
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 15px;
      }
      
      .table-sm th {
        font-weight: 600;
        font-size: 0.875rem;
      }
      
      .table-sm td {
        font-size: 0.875rem;
      }
      
      .btn-purchase-history {
        background: #6c757d;
        border: none;
        color: white;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
      }
      
      .btn-purchase-history:hover {
        background: #5a6268;
      }
      
      .order-row-clickable:hover {
        background-color: #f8f9fa !important;
        cursor: pointer;
      }
      
      .order-row-clickable {
        transition: background-color 0.2s;
      }
      
      .products-cell {
        max-width: 200px;
        word-wrap: break-word;
        white-space: normal;
        font-size: 0.8rem;
        line-height: 1.2;
      }
    </style>
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

          <div class="search-container">
          <form action="" method="GET" class="search-form">
          <input type="text" name="search" placeholder="Search distributors..." class="search-input">
          <button type="submit" class="search-button">
          <i class="fa fa-search"></i>
        </button>
      </form>
    </div>
    <div class="users"> 
        <table>
          <thead>
            <tr class="text-center">
              <th>#</th>
              <th>PTS</th>         
              <th>Name</th>             
              <th>Ext.</th>
              <th>Address</th>
              <th>Outlet</th>
              <th>Phone Number</th>
              <th>Email</th>
              <th>Username</th>
              <th>Action</th>
            </tr>      
          </thead>
          <tbody>
              <?php if (empty($distributors)): ?>
                <tr>
                  <td colspan="16" class="text-center">No distributors found</td>
                </tr>
              <?php else: ?>
                <?php foreach ($distributors as $index => $distributor): ?>
                <tr>
                  <td><?php echo $index + 1; ?></td>
                  <td>
                    <?php
                      $conn2 = new mysqli("localhost", "root", "", "inventory_negrita");
                      $points = 0;
                      if (!$conn2->connect_error) {
                        $stmt2 = $conn2->prepare("SELECT SUM(points) AS total_points FROM orders WHERE distributor_id = ?");
                        $stmt2->bind_param("i", $distributor['distributor_id']);
                        $stmt2->execute();
                        $result2 = $stmt2->get_result();
                        $row2 = $result2->fetch_assoc();
                        $points = $row2 ? intval($row2['total_points']) : 0;
                        $stmt2->close();
                      }
                      $conn2->close();
                      echo $points;
                    ?>
                  </td>
                  <td><?php echo htmlspecialchars($distributor['distrib_fname'] . ' ' . $distributor['distrib_mname'] . ' ' . $distributor['distrib_lname']); ?></td>
                  <td><?php echo htmlspecialchars($distributor['distrib_extension']); ?></td>
                  <td><?php echo htmlspecialchars($distributor['distrib_address']); ?></td>
                  <td><?php echo htmlspecialchars($distributor['distrib_outlet']); ?></td>
                  <td><?php echo htmlspecialchars($distributor['distrib_contact_number']); ?></td>
                  <td><?php echo htmlspecialchars($distributor['distrib_email']); ?></td>
                  <td><?php echo htmlspecialchars($distributor['distrib_username']); ?></td>
                  <td>
                    <?php if (!empty($distributor['distributor_id']) && $distributor['distributor_id'] > 0): ?>
                    <button class="btn btn-sm btn-info" onclick="viewDistributor(<?php echo intval($distributor['distributor_id']); ?>)" data-bs-toggle="tooltip" title="View Distributor Details">
                      <i class="fa fa-eye text-white"></i>
                    </button>
                    <?php if (isset($_SESSION['admin_id'])): ?>
                      <a href="edit_universal_profile.php?type=distributor&id=<?php echo intval($distributor['distributor_id']); ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit Distributor">
                        <i class="fa fa-edit"></i> 
                      </a>
                      <button class="btn btn-sm btn-danger" onclick="deleteDistributor(<?php echo intval($distributor['distributor_id']); ?>)" data-bs-toggle="tooltip" title="Delete Distributor">
                        <i class="fa fa-trash"></i> 
                      </button>
                    <?php endif; ?>
                    <?php else: ?>
                      <span class="text-danger">Invalid ID</span>
                    <?php endif; ?>
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
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jsPDF for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

 <!-- Include responsive sidebar functionality -->
    <script src="sidebar-drawer.js"></script>
<?php if (isset($_GET['new_distributor_id'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var row = document.getElementById('newDistributorRow');
  if (row) {
    row.scrollIntoView({behavior: 'smooth', block: 'center'});
    Swal.fire({
      icon: 'success',
      title: 'New Distributor Added',
      text: 'The new distributor has been added and highlighted.',
      confirmButtonColor: '#168b20ff'
    });
  }
});
</script>
<?php endif; ?>

    <script>
      // Distributor management functions
      function viewDistributor(distributorId) {
        // Fetch distributor details via AJAX
        fetch(`get_distributor_details.php?distributor_id=${distributorId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const distributor = data.distributor;
              const fullName = `${distributor.distrib_fname} ${distributor.distrib_mname ? distributor.distrib_mname + ' ' : ''}${distributor.distrib_lname}${distributor.distrib_extension ? ' ' + distributor.distrib_extension : ''}`;
              const createdDate = new Date(distributor.created_at).toLocaleDateString();
              // Fetch orders for this distributor to show who placed them
              fetch(`fetch_orders.php?distributor_id=${distributorId}`)
                .then(resp => resp.json())
                .then(orderData => {
                  let orderHtml = '';
                  if (orderData.success && orderData.orders && orderData.orders.length > 0) {
                    orderHtml += '<h5>Orders:</h5><table class="table table-bordered"><thead><tr><th>Order ID</th><th>Points</th><th>Placed By</th></tr></thead><tbody>';
                    orderData.orders.forEach(order => {
                      orderHtml += `<tr><td>${order.order_id}</td><td>${order.points}</td><td>${order.user_type}</td></tr>`;
                    });
                    orderHtml += '</tbody></table>';
                  } else {
                    orderHtml += '<p>No orders found for this distributor.</p>';
                  }
                  Swal.fire({
                    title: 'Distributor Details',
                    html: `
                      <div class="distributor-details" style="text-align: left;">
                        <div class="row">
                          <div class="col-md-6">
                            <p><strong>ID:</strong> ${distributor.distributor_id}</p>
                            <p><strong>Name:</strong> ${fullName}</p>
                            <p><strong>Email:</strong> ${distributor.distrib_email || 'N/A'}</p>
                            <p><strong>Username:</strong> ${distributor.distrib_username || 'N/A'}</p>
                            <p><strong>Gender:</strong> ${distributor.distrib_gender || 'N/A'}</p>
                          </div>
                          <div class="col-md-6">
                            <p><strong>Contact:</strong> ${distributor.distrib_contact_number || 'N/A'}</p>
                            <p><strong>Outlet:</strong> ${distributor.distrib_outlet || 'N/A'}</p>
                            <p><strong>Address:</strong> ${distributor.distrib_address || 'N/A'}</p>
                            <p><strong>Birthday:</strong> ${distributor.distrib_birthday || 'N/A'}</p>
                            <p><strong>Age:</strong> ${distributor.distrib_age || 'N/A'}</p>
                            <p><strong>Civil Status:</strong> ${distributor.distrib_civil_status || 'N/A'}</p>
                            <p><strong>Created:</strong> ${createdDate}</p>
                          </div>
                        </div>

                        <button class="btn btn-secondary mt-3 btn-purchase-history" onclick="showPurchaseHistory(${distributorId})">
                          <i class="bi bi-clock-history"></i> PURCHASE HISTORY
                        </button>
                       
                      </div>
                    `,
                    width: '800px',
                    showCloseButton: true,
                    showConfirmButton: false,
                    customClass: {
                      popup: 'swal-wide'
                    }
                  });
                });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to fetch distributor details'
              });
            }
          })
          .catch(error => {
            console.error('Error fetching distributor details:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Failed to fetch distributor details'
            });
          });
      }

      // Function to show purchase history for a specific distributor
      function showPurchaseHistory(distributorId) {
        // Store the current distributor ID for filtering
        currentDistributorId = distributorId;
        
        // Show loading
        Swal.fire({
          title: 'Loading Purchase History...',
          text: 'Please wait while we fetch the order data.',
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
            Swal.showLoading();
          }
        });

        // Fetch distributor orders
        fetch(`fetch_orders.php?distributor_id=${distributorId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.orders && data.orders.length > 0) {
              let orderHistory = '';
              let totalSpent = 0;
              let totalPoints = 0;
              
              // Filter to only show completed (successful) orders
              const completedOrders = data.orders.filter(order => {
                const status = order.status || 'pending';
                return status === 'completed';
              });
              
              // Calculate totals for completed orders only
              completedOrders.forEach(order => {
                totalSpent += parseFloat(order.total_amount || 0);
                totalPoints += parseInt(order.points || 0);
              });

              // Fetch distributor details for the customer info section
              fetch(`get_distributor_details.php?distributor_id=${distributorId}`)
                .then(resp => resp.json())
                .then(distData => {
                  let distributorName = 'Unknown Distributor';
                  
                  if (distData.success) {
                    const distributor = distData.distributor;
                    distributorName = `${distributor.distrib_fname} ${distributor.distrib_mname ? distributor.distrib_mname + ' ' : ''}${distributor.distrib_lname}`;
                    distributorOutlet = distributor.distrib_outlet || 'N/A';
                    distributorEmail = distributor.distrib_email || 'N/A';
                    distributorContact = distributor.distrib_contact_number || 'N/A';
                  }

                  // Check if there are any completed orders
                  if (completedOrders.length > 0) {
                    // Build HTML for order history with distributor info
                    orderHistory = `
                      <div class="purchase-summary mb-3 p-3" style="background: #f8f9fa; border-radius: 8px;">
                        <div class="row">
                          <div class="col-md-3">
                            <p><strong>Name:</strong> ${distributorName}</p>
                          </div>
                          <div class="col-md-3">
                            <strong>Completed Orders:</strong> ${completedOrders.length}
                          </div>
                          <div class="col-md-3">
                            <strong>Total Spent:</strong> ₱${totalSpent.toFixed(2)}
                          </div>
                          <div class="col-md-3">
                            <strong>Total Points:</strong> ${totalPoints}
                          </div>
                        </div>
                      </div>

                      
                      </div>
                    `;

                    // Show only completed orders
                    orderHistory += `
                      <div class="status-group mb-3">
                       
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

                             


                        <div class="table-responsive">
                          <table class="table table-sm table-bordered">
                            <thead class="table-light">
                              <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Products</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Points</th>
                              </tr>
                            </thead>
                            <tbody>
                    `;

                    // Process each order and fetch product details
                    const orderPromises = completedOrders.map(order => {
                      return fetch(`fetch_order_details.php?id=${order.order_id}`)
                        .then(response => response.json())
                        .then(data => {
                          if (data.success && data.items) {
                            order.products = data.items.map(item => item.product_name).join(', ');
                          } else {
                            order.products = 'N/A';
                          }
                          return order;
                        })
                        .catch(error => {
                          console.error(`Error fetching details for order ${order.order_id}:`, error);
                          order.products = 'Error loading';
                          return order;
                        });
                    });

                    // Wait for all product details to be fetched
                    Promise.all(orderPromises).then(ordersWithProducts => {
                      ordersWithProducts.forEach(order => {
                        const orderDate = new Date(order.created_at).toLocaleDateString();
                        const totalQuantity = order.total_quantity || 0;
                        const productNames = order.products || 'N/A';
                        
                        // Create a row for order details
                        orderHistory += `
                          <tr title="Order details">
                            <td><strong>#${order.order_id}</strong></td>
                            <td>${orderDate}</td>
                            <td class="products-cell">${productNames}</td>
                            <td>${totalQuantity}</td>
                            <td>₱${parseFloat(order.unit_price || 0).toFixed(2)}</td>
                            <td>₱${parseFloat(order.total_amount_raw || 0).toFixed(2)}</td>
                            <td>${order.points || 0}</td>
                          </tr>
                        `;
                      });

                      orderHistory += `
                              </tbody>
                            </table>
                          </div>
                        </div>
                      `;

                      // Show the purchase history in a new SweetAlert
                      Swal.fire({
                        title: 'Successful Purchase History',
                        
                        html: orderHistory,
                        width: '90%',
                        showCloseButton: true,
                        showConfirmButton: false,
                        customClass: {
                          popup: 'swal-wide'
                        },
                        footer: `
                          <div style="text-align: center; padding: 10px;">
                            <button class="btn btn-success me-2" onclick="exportPurchaseHistoryPDF(${distributorId})" style="background: #28a745; border: none; color: white; padding: 8px 16px; border-radius: 4px; margin-right: 10px;">
                              <i class="bi bi-file-earmark-pdf"></i> Download PDF Report
                            </button>
                          </div>
                        `
                      });
                    });
                  } else {
                    Swal.fire({
                      icon: 'info',
                      title: 'No Successful Transactions',
                      text: 'This distributor has no completed orders yet.',
                      confirmButtonText: 'OK'
                    });
                  }
                })
                .catch(error => {
                  console.error('Error fetching distributor details:', error);
                  // Fallback to showing orders without distributor info
                  if (completedOrders.length > 0) {
                    orderHistory = `
                      <div class="purchase-summary mb-3 p-3" style="background: #f8f9fa; border-radius: 8px;">
                        <h5>Customer Info</h5>
                        <div class="row">
                          <div class="col-md-4">
                            <strong>Completed Orders:</strong> ${completedOrders.length}
                          </div>
                          <div class="col-md-4">
                            <strong>Total Spent:</strong> ₱${totalSpent.toFixed(2)}
                          </div>
                          <div class="col-md-4">
                            <strong>Total Points:</strong> ${totalPoints}
                          </div>
                        </div>
                      </div>
                    `;
                    // Continue with the rest of the display...
                    Swal.fire({
                      title: 'Successful Purchase History',
                      html: orderHistory,
                      width: '90%',
                      showCloseButton: true,
                      showConfirmButton: false,
                      customClass: {
                        popup: 'swal-wide'
                      }
                    });
                  } else {
                    Swal.fire({
                      icon: 'info',
                      title: 'No Successful Transactions',
                      text: 'This distributor has no completed orders yet.',
                      confirmButtonText: 'OK'
                    });
                  }
                });

            } else {
              Swal.fire({
                icon: 'info',
                title: 'No Successful Transactions',
                text: 'This distributor has no completed orders yet.',
                confirmButtonText: 'OK'
              });
            }
          })
          .catch(error => {
            console.error('Error fetching purchase history:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Failed to fetch purchase history. Please try again.'
            });
          });
      }

      // Function to export purchase history as PDF (based on admin_reports.php)
      function exportPurchaseHistoryPDF(distributorId) {
        // Show loading
        Swal.fire({
          title: 'Generating PDF Report...',
          text: 'Please wait while we prepare your PDF file.',
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
            Swal.showLoading();
          }
        });

        // Fetch distributor orders first
        fetch(`fetch_orders.php?distributor_id=${distributorId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.orders && data.orders.length > 0) {
              // Filter to only show completed orders
              const completedOrders = data.orders.filter(order => {
                const status = order.status || 'pending';
                return status === 'completed';
              });

              if (completedOrders.length === 0) {
                Swal.fire({
                  icon: 'info',
                  title: 'No Successful Transactions',
                  text: 'This distributor has no completed orders to export.'
                });
                return;
              }

              // Get distributor name from first order or fetch separately
              fetch(`get_distributor_details.php?distributor_id=${distributorId}`)
                .then(resp => resp.json())
                .then(distData => {
                  if (distData.success) {
                    const distributor = distData.distributor;
                    const distributorName = `${distributor.distrib_fname} ${distributor.distrib_mname ? distributor.distrib_mname + ' ' : ''}${distributor.distrib_lname}`;
                    
                    generatePDF(completedOrders, distributorName, distributor);
                  } else {
                    generatePDF(completedOrders, 'Unknown Distributor', {});
                  }
                })
                .catch(error => {
                  console.error('Error fetching distributor details:', error);
                  generatePDF(completedOrders, 'Unknown Distributor', {});
                });

            } else {
              Swal.fire({
                icon: 'info',
                title: 'No Data Available',
                text: 'No order data available to export.'
              });
            }
          })
          .catch(error => {
            console.error('Error fetching orders:', error);
            Swal.fire({
              icon: 'error',
              title: 'Export Failed',
              text: 'Failed to fetch order data. Please try again.'
            });
          });
      }

      // Generate PDF function
      function generatePDF(orders, distributorName, distributorInfo) {
        try {
          const { jsPDF } = window.jspdf;
          const doc = new jsPDF();

          // Calculate totals
          let totalSpent = 0;
          let totalPoints = 0;
          orders.forEach(order => {
            totalSpent += parseFloat(order.total_amount_raw || 0);
            totalPoints += parseInt(order.points || 0);
          });

            doc.setFontSize(12);
            doc.setTextColor(218, 165, 32); // Gold color
            doc.setFont('helvetica', 'bold');
            // Add logo image
            doc.addImage('ASSETS/BZ.jpg', 'JPEG', 95, 15, 20, 20);

            // Main company header 
            doc.setFontSize(13);
            doc.setTextColor(0, 0, 0); // Black
            doc.setFont('times', 'bold');
            doc.text('BZ MOMS HEALTH AND WELLNESS CENTER', 105, 40, { align: 'center' });
  
           
            doc.setFontSize(10);
            doc.setTextColor(0, 0, 0); // Black
            doc.setFont('times', 'normal');
            doc.text('Purok 8, Calao East (Pob.), City of Santiago, Isabela, Philippines 3311', 105, 45, { align: 'center' });
          
            doc.setFontSize(10);
            doc.setTextColor(0, 0, 0); // Black
            doc.setFont('times', 'bold');
            doc.text('HAZEL C. BOLISAY - Proprietress', 105, 50, { align: 'center' });


          // Add distributor information with proper spacing
          doc.setFontSize(10);
          doc.setFont('times', 'bold');
          doc.text('DISTRIBUTOR INFORMATION', 20, 60);
          
          doc.setFont('times', 'normal');
          doc.setFontSize(10);
          doc.text('NAME: ' + distributorName, 20, 65);
          doc.text('CONTACT: ' + (distributorInfo.distrib_contact_number || 'N/A'), 20, 70);
          doc.text('OUTLET: ' + (distributorInfo.distrib_outlet || 'N/A'), 20, 75);

           doc.setFontSize(13);
            doc.setTextColor(0, 0, 0); // Black
            doc.setFont('times', 'bold');
            doc.text('PURCHASE HISTORY', 105, 85, { align: 'center' });

          // Fetch product details for each order and then generate PDF
          const orderPromises = orders.map(order => {
            return fetch(`fetch_order_details.php?id=${order.order_id}`)
              .then(response => response.json())
              .then(data => {
                if (data.success && data.items) {
                  order.products = data.items.map(item => item.product_name).join(', ');
                } else {
                  order.products = 'N/A';
                }
                return order;
              })
              .catch(error => {
                console.error(`Error fetching details for order ${order.order_id}:`, error);
                order.products = 'Error loading';
                return order;
              });
          });

          Promise.all(orderPromises).then(ordersWithProducts => {
            // Prepare table data with products
            const tableData = ordersWithProducts.map(order => {
              const orderDate = new Date(order.created_at).toLocaleDateString();
              const productNames = (order.products || 'N/A').length > 40 ? 
                (order.products || 'N/A').substring(0, 37) + '...' : 
                (order.products || 'N/A');
              const totalQuantity = order.total_quantity || 0;
              const unitPrice = '₱' + parseFloat(order.unit_price || 0).toFixed(2);
              const amount = '₱' + parseFloat(order.total_amount_raw || 0).toFixed(2);
              const points = order.points || 0;
              
              return [
                '#' + order.order_id,
                orderDate,
                productNames,
                totalQuantity.toString(),
                unitPrice,
                amount,
                points.toString()
              ];
            });

            // Add table with adjusted startY position
            doc.autoTable({
              startY: 90,
              head: [['Order ID', 'Date', 'Products', 'QTY', 'Unit Price', 'Amount', 'Points']],
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
                2: {cellWidth: 50}, // Products column width
                0: {cellWidth: 15}, // Order ID column width
                1: {cellWidth: 20}, // Date column width
                3: {cellWidth: 15}, // Quantity column width
                4: {cellWidth: 30}, // Unit Price column width
                5: {cellWidth: 30}, // Amount column width
                6: {cellWidth: 15}  // Points column width
              },
              margin: { top: 130, left: 20, right: 20 }
            });

            // Get the final Y position after the table
            const finalY = doc.lastAutoTable.finalY;

            

            // Add footer - Generation timestamp with system info
            doc.setFontSize(9);
            doc.setTextColor(100, 100, 100);
            
            // Add system information line
            doc.text('BZ MOMS Health and Wellness Center - IM-DISTRACK Inventory System', 105, finalY + 20, { align: 'center' });
            
            const generatedOn = new Date().toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            doc.text(`Report Generated on ${generatedOn}`, 105, finalY + 25, { align: 'center' });

            // Create filename
            const today = new Date().toISOString().split('T')[0];
            const filename = `Purchase_History_${distributorName.replace(/\s+/g, '_')}_${today}.pdf`;

            // Save the PDF
            doc.save(filename);

            // Show success message
            Swal.fire({
              icon: 'success',
              title: 'PDF Generated Successfully!',
              text: `Purchase history report has been downloaded as ${filename}`,
              timer: 3000,
              showConfirmButton: false
            });
          });

        } catch (error) {
          console.error('PDF generation error:', error);
          Swal.fire({
            icon: 'error',
            title: 'PDF Generation Failed',
            text: 'Failed to generate PDF report. Please try again.'
          });
        }
      }

      // Function to show detailed order information
      function showOrderDetails(orderId) {
        // Show loading
        Swal.fire({
          title: 'Loading Order Details...',
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
            Swal.showLoading();
          }
        });

        // Fetch detailed order information
        fetch(`fetch_order_details.php?id=${orderId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.order) {
              const order = data.order;
              const items = data.items || [];
              
              let itemsHtml = '';
              let subtotal = 0;
              
              if (items.length > 0) {
                itemsHtml = `
                  <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered">
                      <thead class="table-light">
                        <tr>
                          <th>Product</th>
                          <th>Quantity</th>
                          <th>Price</th>
                          <th>Total</th>
                        </tr>
                      </thead>
                      <tbody>
                `;
                
                items.forEach(item => {
                  const itemTotal = parseFloat(item.quantity) * parseFloat(item.unit_price_raw || 0);
                  subtotal += itemTotal;
                  itemsHtml += `
                    <tr>
                      <td>${item.product_name || 'N/A'}</td>
                      <td>${item.quantity}</td>
                      <td>₱${parseFloat(item.unit_price_raw || 0).toFixed(2)}</td>
                      <td>₱${itemTotal.toFixed(2)}</td>
                    </tr>
                  `;
                });
                
                itemsHtml += `
                      </tbody>
                    </table>
                  </div>
                `;
              }

              const orderDate = new Date(order.created_at).toLocaleDateString();
              const statusBadge = {
                'pending': 'warning',
                'processing': 'info', 
                'completed': 'success',
                'cancelled': 'danger'
              };

              Swal.fire({
                title: `Order Details #${order.order_id}`,
                html: `
                  <div class="order-details" style="text-align: left;">
                    <div class="row mb-3">
                      <div class="col-md-6">
                        <p><strong>Order Date:</strong> ${orderDate}</p>
                        <p><strong>Customer:</strong> ${order.customer_name || 'N/A'}</p>
                        <p><strong>Contact:</strong> ${order.customer_contact || 'N/A'}</p>
                      </div>
                      <div class="col-md-6">
                        <p><strong>Status:</strong> <span class="badge bg-${statusBadge[order.status] || 'secondary'}">${order.status || 'N/A'}</span></p>
                        <p><strong>Points Earned:</strong> ${order.points || 0}</p>
                        <p><strong>Total Amount:</strong> <strong>₱${parseFloat(order.total_amount_raw || 0).toFixed(2)}</strong></p>
                      </div>
                    </div>
                    ${order.customer_address ? `<p><strong>Delivery Address:</strong> ${order.customer_address}</p>` : ''}
                    
                    <h6>Order Items:</h6>
                    ${itemsHtml || '<p class="text-muted">No items found for this order.</p>'}
                  </div>
                `,
                width: '800px',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                  popup: 'swal-wide'
                }
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to fetch order details.'
              });
            }
          })
          .catch(error => {
            console.error('Error fetching order details:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Failed to fetch order details.'
            });
          });
      }

      // Date filtering functions for purchase history
      let currentDistributorId = null;
      let allOrders = [];

      function filterOrdersByDate() {
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        
        if (!dateFrom && !dateTo) {
          Swal.fire({
            icon: 'warning',
            title: 'Date Required',
            text: 'Please select at least one date to filter.'
          });
          return;
        }

        if (dateFrom && dateTo && dateFrom > dateTo) {
          Swal.fire({
            icon: 'error',
            title: 'Invalid Date Range',
            text: 'From date cannot be later than To date.'
          });
          return;
        }

        // Show loading
        Swal.fire({
          title: 'Filtering Orders...',
          text: 'Please wait while we filter the orders.',
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
            Swal.showLoading();
          }
        });

        // Build filter URL
        let filterUrl = `fetch_orders.php?distributor_id=${currentDistributorId}`;
        if (dateFrom) filterUrl += `&date_from=${dateFrom}`;
        if (dateTo) filterUrl += `&date_to=${dateTo}`;

        // Fetch filtered orders
        fetch(filterUrl)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.orders) {
              // Filter to only show completed orders
              const completedOrders = data.orders.filter(order => {
                const status = order.status || 'pending';
                return status === 'completed';
              });

              if (completedOrders.length > 0) {
                refreshPurchaseHistory(completedOrders, currentDistributorId);
              } else {
                Swal.fire({
                  icon: 'info',
                  title: 'No Results',
                  text: 'No completed orders found for the selected date range.'
                });
              }
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Filter Failed',
                text: 'Failed to filter orders. Please try again.'
              });
            }
          })
          .catch(error => {
            console.error('Error filtering orders:', error);
            Swal.fire({
              icon: 'error',
              title: 'Filter Failed',
              text: 'Failed to filter orders. Please try again.'
            });
          });
      }

      function clearDateFilter() {
        document.getElementById('dateFrom').value = '';
        document.getElementById('dateTo').value = '';
        
        // Show all orders again
        showPurchaseHistory(currentDistributorId);
      }

      // Helper function to refresh purchase history with filtered data
      function refreshPurchaseHistory(orders, distributorId) {
        let orderHistory = '';
        let totalSpent = 0;
        let totalPoints = 0;

        // Calculate totals
        orders.forEach(order => {
          totalSpent += parseFloat(order.total_amount_raw || 0);
          totalPoints += parseInt(order.points || 0);
        });

        // Get distributor name
        fetch(`get_distributor_details.php?distributor_id=${distributorId}`)
          .then(resp => resp.json())
          .then(distData => {
            let distributorName = 'Unknown Distributor';
            
            if (distData.success) {
              const distributor = distData.distributor;
              distributorName = `${distributor.distrib_fname} ${distributor.distrib_mname ? distributor.distrib_mname + ' ' : ''}${distributor.distrib_lname}`;
            }

            // Build filtered results HTML
            orderHistory = `
              <div class="purchase-summary mb-3 p-3" style="background: #f8f9fa; border-radius: 8px;">
                <div class="row">
                  <div class="col-md-3">
                    <p><strong>Name:</strong> ${distributorName}</p>
                  </div>
                  <div class="col-md-3">
                    <strong>Filtered Orders:</strong> ${orders.length}
                  </div>
                  <div class="col-md-3">
                    <strong>Total Spent:</strong> ₱${totalSpent.toFixed(2)}
                  </div>
                  <div class="col-md-3">
                    <strong>Total Points:</strong> ${totalPoints}
                  </div>
                </div>
              </div>

              <div class="status-group mb-3">
                <div class="d-flex gap-2 mb-3">
                  <input type="date" id="dateFrom" class="form-control form-control-sm" style="width: auto;" placeholder="From Date">
                  <input type="date" id="dateTo" class="form-control form-control-sm" style="width: auto;" placeholder="To Date">
                  <button class="btn btn-primary btn-sm" onclick="filterOrdersByDate()">
                    <i class="bi bi-funnel"></i> Filter
                  </button>
                  <button class="btn btn-outline-secondary btn-sm" onclick="clearDateFilter()">
                    <i class="bi bi-x-circle"></i> Clear
                  </button>
                </div>

                <div class="table-responsive">
                  <table class="table table-sm table-bordered">
                    <thead class="table-light">
                      <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Products</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Amount</th>
                        <th>Points</th>
                      </tr>
                    </thead>
                    <tbody>
            `;

            // Process orders and fetch product details
            const orderPromises = orders.map(order => {
              return fetch(`fetch_order_details.php?id=${order.order_id}`)
                .then(response => response.json())
                .then(data => {
                  if (data.success && data.items) {
                    order.products = data.items.map(item => item.product_name).join(', ');
                  } else {
                    order.products = 'N/A';
                  }
                  return order;
                })
                .catch(error => {
                  console.error(`Error fetching details for order ${order.order_id}:`, error);
                  order.products = 'Error loading';
                  return order;
                });
            });

            // Wait for all product details and display results
            Promise.all(orderPromises).then(ordersWithProducts => {
              ordersWithProducts.forEach(order => {
                const orderDate = new Date(order.created_at).toLocaleDateString();
                const totalQuantity = order.total_quantity || 0;
                const productNames = order.products || 'N/A';
                
                orderHistory += `
                  <tr title="Order details">
                    <td><strong>#${order.order_id}</strong></td>
                    <td>${orderDate}</td>
                    <td class="products-cell">${productNames}</td>
                    <td>${totalQuantity}</td>
                    <td>₱${parseFloat(order.unit_price || 0).toFixed(2)}</td>
                    <td>₱${parseFloat(order.total_amount_raw || 0).toFixed(2)}</td>
                    <td>${order.points || 0}</td>
                  </tr>
                `;
              });

              orderHistory += `
                    </tbody>
                  </table>
                </div>
              </div>
              `;

              // Show the filtered results
              Swal.fire({
                title: 'Filtered Purchase History',
                html: orderHistory,
                width: '90%',
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                  popup: 'swal-wide'
                },
                footer: `
                  <div style="text-align: center; padding: 10px;">
                    <button class="btn btn-success me-2" onclick="exportPurchaseHistoryPDF(${distributorId})" style="background: #28a745; border: none; color: white; padding: 8px 16px; border-radius: 4px; margin-right: 10px;">
                      <i class="bi bi-file-earmark-pdf"></i> Download PDF Report
                    </button>
                  </div>
                `
              });
            });
          });
      }

      function editDistributor(distributorId) {
        window.location.href = 'DISTRIBUTORfolder/d_edit_universal_profile.php?type=distributor&id=' + distributorId;
      }

      function deleteDistributor(distributorId) {
        console.log('Delete function called with distributorId:', distributorId);
        console.log('Type of distributorId:', typeof distributorId);
        
        if (!distributorId || distributorId <= 0) {
          Swal.fire('Error!', 'Invalid distributor ID', 'error');
          return;
        }
        
        Swal.fire({
          title: 'Are you sure?',
          text: "You won't be able to revert this!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            console.log('Sending delete request for distributor ID:', distributorId);
            
            // You can implement delete functionality here
            fetch('delete_distributor.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({distributor_id: parseInt(distributorId)})
            })
            .then(response => {
              console.log('Response status:', response.status);
              console.log('Response OK:', response.ok);
              
              if (!response.ok) {
                return response.text().then(text => {
                  throw new Error(`HTTP ${response.status}: ${text}`);
                });
              }
              
              return response.json();
            })
            .then(data => {
              console.log('Response data:', data);
              if (data.success) {
                Swal.fire('Deleted!', data.message || 'Distributor has been deleted.', 'success')
                .then(() => location.reload());
              } else {
                Swal.fire('Error!', data.message || 'Failed to delete distributor.', 'error');
              }
            })
            .catch(error => {
              console.error('Delete error:', error);
              let errorMessage = 'An error occurred while deleting.';
              if (error.message) {
                errorMessage += ' Details: ' + error.message;
              }
              Swal.fire('Error!', errorMessage, 'error');
            });
          }
        });
      }
    </script>
    
  </body>
</html>