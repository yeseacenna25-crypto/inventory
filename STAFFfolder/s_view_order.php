<?php
session_start();
// Redirect to login if not logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: staff_login.php");
    exit();
}
// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Fetch staff name
$stmt = $conn->prepare("SELECT staff_fname, staff_mname, staff_lname FROM staff_signup WHERE staff_id = ?");
$stmt->bind_param("i", $_SESSION['staff_id']);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$fullName = $staff ? $staff['staff_fname'] . ' ' . $staff['staff_mname'] . ' ' . $staff['staff_lname'] : "Staff";
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ORDER MANAGEMENT</title>
    <link rel="stylesheet" type="text/css" href="../CSS/admin_dashboard.css?v=<?= time(); ?>" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
  </head>

  <body>

    <div id="dashboardMainContainer">

    <!-- SIDEBAR -->
    <?php include('STAFFpartials/s_sidebar.php'); ?>
    <!-- SIDEBAR -->

        <div class="dashboard_content_container" id="dashboard_content_container">

          <!-- TOP NAVBAR -->
          <?php include('STAFFpartials/s_topnav.php') ?>
          <!-- TOP NAVBAR -->

            <div class="dashboard_content">

                <!-- Page Header -->

                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="col-12">
                                <h2 class="page-title" style="text-align: center;">
                                    <i class="fa fa-shopping-cart me-3"></i>
                                    ORDERS
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>

                 <!-- Filter Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header" style="background: linear-gradient(135deg, #410101, #5e0202); color: white;">
                                <h5 class="mb-0"><i class="bi bi-funnel"></i> Filter Orders</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="statusFilter" class="form-label fw-bold">Order Status</label>
                                        <select class="form-select" id="statusFilter">
                                            <option value="">All Statuses</option>
                                            <option value="pending">Pending</option>
                                            <option value="processing">Processing</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="dateFrom" class="form-label fw-bold">Date From</label>
                                        <input type="date" class="form-control" id="dateFrom">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="dateTo" class="form-label fw-bold">Date To</label>
                                        <input type="date" class="form-control" id="dateTo">
                                    </div>
                                    <div class="col-md-3 mb-3 d-flex align-items-end gap-2">
                                        <button class="btn btn-primary flex-grow-1" id="filterBtn" style="background: #410101; border-color: #410101;">
                                            <i class="bi bi-search"></i> Filter
                                        </button>
                                        <button class="btn btn-outline-secondary" id="clearBtn">
                                            <i class="bi bi-x-circle"></i> Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <!-- Orders List -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header" style="background: linear-gradient(135deg, #410101, #5e0202); color: white;">
                                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Purchase Orders</h5>
                            </div>
                            <div class="card-body" style="background-color: #fcfcfcff;">
                                <div id="ordersContainer">
                                    <!-- Orders will be loaded here -->
                                    <div class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <div class="mt-3">Loading orders...</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Pagination Controls -->
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted">
                                        <small id="paginationInfo">Showing 0 - 0 of 0 orders</small>
                                    </div>
                                    <nav aria-label="Orders pagination">
                                        <ul class="pagination pagination-sm mb-0" id="paginationControls">
                                            <li class="page-item disabled">
                                                <a class="page-link" href="#" onclick="loadOrders(currentPage - 1)">
                                                    <i class="bi bi-chevron-left"></i> Previous
                                                </a>
                                            </li>
                                            <li class="page-item active">
                                                <a class="page-link" href="#">1</a>
                                            </li>
                                            <li class="page-item disabled">
                                                <a class="page-link" href="#" onclick="loadOrders(currentPage + 1)">
                                                    Next <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            </div>
        </div>
    </div>

<!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #410101, #5e0202); color: white;">
                    <h5 class="modal-title" id="orderDetailsModalLabel">
                        <i class="bi bi-receipt"></i> Order Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-3">Loading order details...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ffc107, #ffcd39); color: #212529;">
                    <h5 class="modal-title" id="updateStatusModalLabel">
                        <i class="bi bi-pencil-square"></i> Update Order Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newStatus" class="form-label fw-bold">Select New Status</label>
                        <select class="form-select" id="newStatus">
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Note:</strong> Changing the status will update the order and notify relevant parties.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" onclick="updateOrderStatus()" style="background: #410101; border-color: #410101;">
                        <i class="bi bi-check-circle"></i> Update Status
                    </button>
                </div>
            </div>
        </div>
    </div>





    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
<!-- Include responsive sidebar functionality -->
    <script src="sidebar-drawer.js"></script>

    <script>
      let currentOrderId = null;
      let currentPage = 1;
      let totalPages = 1;
      let totalOrders = 0;
      const ordersPerPage = 10;
      
      // Load orders and statistics when page loads
      document.addEventListener('DOMContentLoaded', function() {
          loadOrders();
          loadOrderStatistics();
          
          // Add event listeners for filters
          document.getElementById('statusFilter').addEventListener('change', function() {
              currentPage = 1;
              loadOrders();
          });
          
          document.getElementById('dateFrom').addEventListener('change', function() {
              currentPage = 1;
              loadOrders();
          });
          
          document.getElementById('dateTo').addEventListener('change', function() {
              currentPage = 1;
              loadOrders();
          });
          
          document.getElementById('filterBtn').addEventListener('click', function() {
              currentPage = 1;
              loadOrders();
          });
          
          document.getElementById('clearBtn').addEventListener('click', function() {
              clearFilters();
          });
          
          // Auto-refresh orders every 30 seconds to catch cancellations from distributors
          setInterval(function() {
              loadOrders();
              loadOrderStatistics();
          }, 30000);
      });
      
      // Load order statistics
      function loadOrderStatistics() {
          fetch('get_order_stats.php')
              .then(response => response.json())
              .then(data => {
                  if (data.success) {
                      updateStatisticsCards(data.stats);
                  }
              })
              .catch(error => {
                  console.error('Error loading statistics:', error);
              });
      }
      
      // Update statistics cards
      function updateStatisticsCards(stats) {
          const cards = document.querySelectorAll('#statsCards .card h4');
          if (cards.length >= 5) {
              cards[0].textContent = stats.total || '0';
              cards[1].textContent = stats.completed || '0';
              cards[2].textContent = stats.processing || '0';
              cards[3].textContent = stats.pending || '0';
              cards[4].textContent = stats.cancelled || '0';
          }
      }
      
      // Load orders function
      function loadOrders(page = 1) {
          currentPage = page;
          const container = document.getElementById('ordersContainer');
          container.innerHTML = `
              <div class="text-center py-5">
                  <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                  </div>
                  <div class="mt-3">Loading orders...</div>
              </div>
          `;
          // Build query parameters
          const params = new URLSearchParams();
          const status = document.getElementById('statusFilter').value;
          const dateFrom = document.getElementById('dateFrom').value;
          const dateTo = document.getElementById('dateTo').value;
          if (status) params.append('status', status);
          if (dateFrom) params.append('date_from', dateFrom);
          if (dateTo) params.append('date_to', dateTo);
          params.append('page', page);
          params.append('limit', ordersPerPage);
          // Pass staff_id to backend for filtering
          params.append('staff_id', <?= json_encode($_SESSION['staff_id']) ?>);
          fetch(`s_fetch_orders.php?${params.toString()}`)
              .then(response => response.json())
              .then(data => {
                  if (data.success) {
                      totalOrders = data.pagination.total || 0;
                      totalPages = data.pagination.totalPages || 1;
                      displayOrders(data.orders);
                      updatePaginationControls();
                  } else {
                      container.innerHTML = `
                          <div class="text-center py-5">
                              <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                              <h4 class="text-muted mt-3">No orders found</h4>
                              <p class="text-muted">${data.message || 'Try adjusting your filters to see more results.'}</p>
                          </div>
                      `;
                      updatePaginationControls();
                  }
              })
              .catch(error => {
                  console.error('Error:', error);
                  container.innerHTML = `
                      <div class="alert alert-danger text-center">
                          <i class="bi bi-exclamation-triangle"></i>
                          <strong>Error:</strong> An error occurred while loading orders. Please try again.
                      </div>
                  `;
                  updatePaginationControls();
              });
      }
      
      // Display orders function with modern card design
      function displayOrders(orders) {
          const container = document.getElementById('ordersContainer');
          
          if (orders.length === 0) {
              container.innerHTML = `
                  <div class="text-center py-5">
                      <i class="bi bi-inbox" style="font-size: 4rem; color: #ccc;"></i>
                      <h4 class="text-muted mt-3">No orders found</h4>
                      <p class="text-muted">Try adjusting your filters to see more results.</p>
                  </div>
              `;
              return;
          }
          
          let ordersHtml = '<div class="row">';
          orders.forEach(order => {
              const statusColor = getStatusColor(order.status);
              const statusIcon = getStatusIcon(order.status);
              
              ordersHtml += `
                  <div class="col-lg-6 mb-4">
                      <div class="card h-100 shadow-sm border-0" style="border-left: 4px solid var(--bs-${statusColor}) !important;">
                          <div class="card-header bg-white d-flex justify-content-between align-items-center">
                              <h6 class="mb-0 fw-bold text-dark">
                                  <i class="bi bi-receipt"></i> ORDER #${order.order_id}
                              </h6>
                              <span class="badge bg-${statusColor}">
                                  <i class="bi bi-${statusIcon}"></i> ${order.status.toUpperCase()}
                              </span>
                          </div>
                          <div class="card-body">
                              <div class="row">
                                  <div class="col-12 mb-3">
                                      <h6 class="text-muted mb-2">
                                          <i class="bi bi-person"></i> Customer Information
                                      </h6>
                                      <p class="mb-1"><strong>Name:</strong> ${order.customer_name}</p>
                                      <p class="mb-1"><strong>Contact:</strong> ${order.customer_contact || 'N/A'}</p>
                                      <p class="mb-0"><strong>Address:</strong> ${order.customer_address || 'N/A'}</p>
                                  </div>
                                  <div class="col-6">
                                      <p class="mb-1" "><strong>Total Amount:</strong></p>
                                      <h5 class="text-success mb-0 fw-bold" >₱${parseFloat(order.total_amount_raw).toLocaleString('en-US', {minimumFractionDigits: 2})}</h5>
                                  </div>
                                  <div class="col-6 text-end">
                                      <p class="mb-1 text-muted"><strong>Created:</strong></p>
                                      <small class="text-muted">${new Date(order.created_at).toLocaleDateString()}</small>
                                  </div>
                              </div>
                          </div>
                          <div class="card-footer bg-white border-0">
                              <div class="d-flex gap-2">
                                  <button class="btn btn-outline-primary btn-sm flex-fill" onclick="viewOrderDetails(${order.order_id})">
                                      <i class="bi bi-eye"></i> View Details
                                  </button>
                                  <button class="btn btn-outline-warning btn-sm flex-fill" onclick="showUpdateStatusModal(${order.order_id}, '${order.status}')">
                                      <i class="bi bi-pencil"></i> Update Status
                                  </button>
                              </div>
                          </div>
                      </div>
                  </div>
              `;
          });
          ordersHtml += '</div>';
          
          container.innerHTML = ordersHtml;
      }
      
      // Clear filters function
      function clearFilters() {
          document.getElementById('statusFilter').value = '';
          document.getElementById('dateFrom').value = '';
          document.getElementById('dateTo').value = '';
          currentPage = 1;
          loadOrders();
      }
      
      // Update pagination controls
      function updatePaginationControls() {
          const paginationInfo = document.getElementById('paginationInfo');
          const paginationControls = document.getElementById('paginationControls');
          
          // Update info text
          const startItem = totalOrders === 0 ? 0 : (currentPage - 1) * ordersPerPage + 1;
          const endItem = Math.min(currentPage * ordersPerPage, totalOrders);
          paginationInfo.textContent = `Showing ${startItem} - ${endItem} of ${totalOrders} orders`;
          
          // Build pagination controls
          let paginationHtml = '';
          
          // Previous button
          const prevDisabled = currentPage <= 1 ? 'disabled' : '';
          paginationHtml += `
              <li class="page-item ${prevDisabled}">
                  <a class="page-link" href="#" data-page="${currentPage - 1}" ${prevDisabled ? 'tabindex="-1"' : ''}>
                      <i class="bi bi-chevron-left"></i> Previous
                  </a>
              </li>
          `;
          
          // Page numbers
          const maxVisiblePages = 5;
          let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
          let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
          
          // Adjust start page if we're near the end
          if (endPage - startPage + 1 < maxVisiblePages) {
              startPage = Math.max(1, endPage - maxVisiblePages + 1);
          }
          
          // Add page numbers
          for (let i = startPage; i <= endPage; i++) {
              const active = i === currentPage ? 'active' : '';
              paginationHtml += `
                  <li class="page-item ${active}">
                      <a class="page-link" href="#" data-page="${i}">${i}</a>
                  </li>
              `;
          }
          
          // Next button
          const nextDisabled = currentPage >= totalPages ? 'disabled' : '';
          paginationHtml += `
              <li class="page-item ${nextDisabled}">
                  <a class="page-link" href="#" data-page="${currentPage + 1}" ${nextDisabled ? 'tabindex="-1"' : ''}>
                      Next <i class="bi bi-chevron-right"></i>
                  </a>
              </li>
          `;
          
          paginationControls.innerHTML = paginationHtml;
          
          // Add event listeners to pagination links
          paginationControls.querySelectorAll('a.page-link').forEach(link => {
              link.addEventListener('click', function(e) {
                  e.preventDefault();
                  const page = parseInt(this.getAttribute('data-page'));
                  if (!isNaN(page) && page >= 1 && page <= totalPages && page !== currentPage) {
                      changePage(page);
                  }
              });
          });
      }
      
      // Change page function
      function changePage(page) {
          if (page < 1 || page > totalPages || page === currentPage) {
              return false;
          }
          loadOrders(page);
          return false;
      }
      
      // View order details function
      function viewOrderDetails(orderId) {
          currentOrderId = orderId;
          const modalContent = document.getElementById('orderDetailsContent');
          modalContent.innerHTML = `
              <div class="text-center py-5">
                  <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Loading...</span>
                  </div>
                  <div class="mt-3">Loading order details...</div>
              </div>
          `;
          const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
          modal.show();
          fetch(`s_fetch_order_details.php?order_id=${orderId}`)
              .then(response => response.json())
              .then(data => {
                  if (data.success) {
                      displayOrderDetails(data.order, data.items);
                  } else {
                      modalContent.innerHTML = `
                          <div class="alert alert-danger">
                              <i class="bi bi-exclamation-triangle"></i>
                              <strong>Error:</strong> ${data.message || 'Failed to load order details'}
                          </div>
                      `;
                  }
              })
              .catch(error => {
                  console.error('Error:', error);
                  modalContent.innerHTML = `
                      <div class="alert alert-danger">
                          <i class="bi bi-exclamation-triangle"></i>
                          <strong>Error:</strong> An error occurred while loading order details
                      </div>
                  `;
              });
      }
      
      // Display order details function with modern styling
      function displayOrderDetails(order, items) {
          const modalContent = document.getElementById('orderDetailsContent');
          const statusColor = getStatusColor(order.status);
          const statusIcon = getStatusIcon(order.status);
          
          let itemsHtml = '';
          let totalAmount = 0;
          items.forEach((item, index) => {
              const itemTotal = parseFloat(item.total_price_raw);
              totalAmount += itemTotal;
              // Product image fetch (assume endpoint get_product_image.php?product_id=...)
              const productImageUrl = `get_product_image.php?product_id=${item.product_id}`;
              itemsHtml += `
                  <tr>
                      <td class="text-center">${index + 1}</td>
                      <td>
                          <div class="d-flex align-items-center gap-2">
                              <img src="${productImageUrl}" alt="${item.product_name}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #ccc;">
                              <span>${item.product_name}</span>
                          </div>
                      </td>
                      <td class="text-center">${item.quantity}</td>
                      <td class="text-end">₱${parseFloat(item.unit_price_raw).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                      <td class="text-end">₱${itemTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                      <td class="text-center">
                          <span class="badge bg-info">${item.current_stock || 'N/A'}</span>
                      </td>
                  </tr>
              `;
          });
          
          modalContent.innerHTML = `
              <!-- Order Header -->
              <div class="row mb-4">
                  <div class="col-md-4">
                      <div class="card border-primary h-100">
                          <div class="card-header bg-primary text-white">
                              <h6 class="mb-0"><i class="bi bi-person"></i> Customer Information</h6>
                          </div>
                          <div class="card-body">
                              <p class="mb-2"><strong>Name:</strong> ${order.customer_name}</p>
                              <p class="mb-2"><strong>Contact:</strong> ${order.customer_contact || 'N/A'}</p>
                              <p class="mb-0"><strong>Address:</strong> ${order.customer_address || 'N/A'}</p>
                          </div>
                      </div>
                  </div>
                  <div class="col-md-4">
                      <div class="card border-${statusColor} h-100">
                          <div class="card-header bg-${statusColor} text-white">
                              <h6 class="mb-0"><i class="bi bi-receipt"></i> Order Information</h6>
                          </div>
                          <div class="card-body">
                              <p class="mb-2"><strong>Order ID:</strong> #${order.order_id}</p>
                              <p class="mb-2"><strong>Status:</strong> 
                                  <span class="badge bg-${statusColor}">
                                      <i class="bi bi-${statusIcon}"></i> ${order.status.toUpperCase()}
                                  </span>
                              </p>
                              <p class="mb-2"><strong>Created By:</strong> ${order.created_by}</p>
                              <p class="mb-2"><strong>Created:</strong> ${new Date(order.created_at).toLocaleDateString()}</p>
                              <p class="mb-0"><strong>Last Updated:</strong> ${new Date(order.updated_at).toLocaleDateString()}</p>
                          </div>
                      </div>
                  </div>
                  <div class="col-md-4">
                      <div class="card border-success h-100">
                          <div class="card-header bg-success text-light">
                              <h6 class="mb-0"><i class="bi bi-journal-text"></i> Order Notes</h6>
                          </div>
                          <div class="card-body">
                              <div class="p-2 bg-light border rounded" style="min-height: 100px; max-height: 150px; overflow-y: auto;">
                                  ${order.order_notes ? `<div class="text-dark">${order.order_notes}</div>` : '<span class="text-muted fst-italic">No notes added for this order</span>'}
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
              
              <!-- Order Items -->
              <div class="card">
                  <div class="card-header bg-light">
                      <h6 class="mb-0"><i class="bi bi-list-ul"></i> Order Items</h6>
                  </div>
                  <div class="card-body p-0">
                      <div class="table-responsive">
                          <table class="table table-hover mb-0">
                              <thead class="table-light">
                                  <tr>
                                      <th class="text-center">#</th>
                                      <th>Product Name</th>
                                      <th class="text-center">Quantity</th>
                                      <th class="text-end">Unit Price</th>
                                      <th class="text-end">Total Price</th>
                                      <th class="text-center">Stock Available</th>
                                  </tr>
                              </thead>
                              <tbody>
                                  ${itemsHtml}
                              </tbody>
                          </table>
                      </div>
                  </div>
                  <div class="card-footer bg-light">
                      <div class="row">
                          <div class="col-md-6">
                              <p class="mb-0 text-muted">Total Items: ${items.length}</p>
                          </div>
                          <div class="col-md-6 text-end">
                              <h5 class="mb-0 text-success">
                                  <strong>Total Amount: ₱${totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>
                              </h5>
                          </div>
                      </div>
                  </div>
              </div>
          `;
          
          document.getElementById('orderDetailsModalLabel').innerHTML = `
              <i class="bi bi-receipt"></i> Order #${order.order_id} Details
          `;
      }
      
      // Show update status modal
      function showUpdateStatusModal(orderId, currentStatus) {
          currentOrderId = orderId;
          document.getElementById('newStatus').value = currentStatus;
          const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
          modal.show();
      }
      
      // Update order status function
      function updateOrderStatus() {
          if (!currentOrderId) return;
          
          const newStatus = document.getElementById('newStatus').value;
          
          // Show loading
          const updateBtn = event.target;
          const originalText = updateBtn.innerHTML;
          updateBtn.innerHTML = '<i class="spinner-border spinner-border-sm"></i> Updating...';
          updateBtn.disabled = true;
          
          fetch('update_order_status.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                  order_id: currentOrderId,
                  status: newStatus
              })
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  Swal.fire({
                      icon: 'success',
                      title: 'Success!',
                      text: 'Order status updated successfully!',
                      timer: 1500,
                      showConfirmButton: false
                  }).then(() => {
                      // Close modal and reload orders
                      const modal = bootstrap.Modal.getInstance(document.getElementById('updateStatusModal'));
                      modal.hide();
                      loadOrders();
                      loadOrderStatistics();
                  });
              } else {
                  Swal.fire({
                      icon: 'error',
                      title: 'Error',
                      text: data.message || 'Failed to update order status'
                  });
              }
          })
          .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: 'An error occurred while updating order status'
              });
          })
          .finally(() => {
              // Restore button
              updateBtn.innerHTML = originalText;
              updateBtn.disabled = false;
          });
      }
      
      // Helper function to get status color
      function getStatusColor(status) {
          switch(status.toLowerCase()) {
              case 'pending': return 'warning';
              case 'processing': return 'info';
              case 'completed': return 'success';
              case 'cancelled': return 'danger';
              default: return 'secondary';
          }
      }
      
      // Helper function to get status icon
      function getStatusIcon(status) {
          switch(status.toLowerCase()) {
              case 'pending': return 'clock';
              case 'processing': return 'arrow-clockwise';
              case 'completed': return 'check-circle';
              case 'cancelled': return 'x-circle';
              default: return 'question-circle';
          }
      }
      
      // Prevent browser back navigation
      history.pushState(null, null, location.href);
      window.onpopstate = function () {
        history.go(1);
      };
    </script>

   




    

    
    
  </body>
</html>
