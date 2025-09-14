<?php
if (!isset($_SESSION)) {
  session_start();
}
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

            <div class="container mt-4">
    <h4 class="text-danger mb-4"><i class="bi bi-card-list"></i> List of Purchase Orders</h4>

   <!-- Filter Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Filter by Status:</label>
                    <select class="form-select" id="statusFilter" onchange="loadOrders()">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="dateFrom" class="form-label">From Date:</label>
                    <input type="date" class="form-control" id="dateFrom" onchange="loadOrders()">
                </div>
                <div class="col-md-3">
                    <label for="dateTo" class="form-label">To Date:</label>
                    <input type="date" class="form-control" id="dateTo" onchange="loadOrders()">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button class="btn btn-primary" onclick="loadOrders()">
                            <i class="fa fa-refresh"></i> Refresh
                        </button>
                        <button class="btn btn-success" onclick="window.location.href='distri_order.php'">
                            <i class="fa fa-plus"></i> New Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
      <!-- Shopee-style Order List -->
      <div class="card">
        <div class="card-body">
          <!-- Status Tabs -->
          <ul class="nav nav-tabs mb-3" id="orderStatusTabs">
            <li class="nav-item"><a class="nav-link active" href="#" data-status="" onclick="setStatusFilter('')">All <span id="countAll" style="background:none;color:gray !important;padding:2px 8px;border-radius:8px;">0</span></a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-status="pending" onclick="setStatusFilter('pending')">Pending <span id="countPending" style="background:none;color:gray !important;padding:2px 8px;border-radius:8px;">0</span></a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-status="processing" onclick="setStatusFilter('processing')">To Process <span id="countProcessing" style="background:none;color:gray !important;padding:2px 8px;border-radius:8px;">0</span></a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-status="completed" onclick="setStatusFilter('completed')">Processed <span id="countCompleted" style="background:none;color:gray !important;padding:2px 8px;border-radius:8px;">0</span></a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-status="cancelled" onclick="setStatusFilter('cancelled')">Cancelled <span id="countCancelled" style="background:none;color:gray !important;padding:2px 8px;border-radius:8px;">0</span></a></li>
          </ul>
          <!-- Order List -->
          <div id="ordersList">
            <div class="text-center text-muted">No orders found.</div>
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
    <script src="../sidebar-drawer.js"></script>

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
      const dateFrom = document.getElementById('dateFrom').value;
      const dateTo = document.getElementById('dateTo').value;
      let url = `../fetch_distributor_orders.php?`;
      if (dateFrom) url += `date_from=${encodeURIComponent(dateFrom)}&`;
      if (dateTo) url += `date_to=${encodeURIComponent(dateTo)}&`;
      try {
        const response = await fetch(url);
        const data = await response.json();
        allOrders = data.orders || [];
        updateOrderCounts(allOrders);
        const ordersList = document.getElementById('ordersList');
        ordersList.innerHTML = '';
        let filteredOrders = allOrders;
        if (currentStatus) {
          filteredOrders = filteredOrders.filter(o => o.status === currentStatus);
        }
        if (data.success && filteredOrders.length > 0) {
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
            if (order.status === 'pending') {
              cancelBtnHtml = `<button class="btn btn-danger btn-sm py-1 px-3 cancel-order-btn" style="font-size:0.85rem;border-radius:8px;vertical-align:middle;" data-order-id="${order.order_id}">Cancel</button>`;
            }
            card.innerHTML = `
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-md-4 text-start">
                    <div><strong>Order ID:</strong> ${order.order_id}</div>
                    <div><strong>Name:</strong> ${order.customer_name}</div>
                    <div><strong>Contact:</strong> ${order.customer_contact}</div>
                    <div><strong>Address:</strong> ${order.customer_address}</div>
                  </div>
                  <div class="col-md-4">
                    ${itemsHtml}
                  </div>
                  <div class="col-md-2">
                    <strong>Total Price:</strong> ₱${order.total_amount}
                  </div>
                  <div class="col-md-2 d-flex align-items-center gap-2">
                    <span class="badge ${getStatusBadgeClass(order.status)}">${capitalize(order.status)}</span>
                    ${cancelBtnHtml}
                  </div>
                </div>
              </div>
            `;
            ordersList.appendChild(card);
          });
        } else {
          ordersList.innerHTML = '<div class="text-center text-muted">No orders found.</div>';
        }
      } catch (err) {
        document.getElementById('ordersList').innerHTML = '<div class="text-center text-danger">Failed to load orders.</div>';
      }
    }
    function updateOrderCounts(orders) {
      let all = orders.length;
      let pending = orders.filter(o => o.status === 'pending').length;
      let processing = orders.filter(o => o.status === 'processing').length;
      let completed = orders.filter(o => o.status === 'completed').length;
      let cancelled = orders.filter(o => o.status === 'cancelled').length;
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
          text: 'Are you sure you want to cancel this order?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#aaa',
          confirmButtonText: 'Yes, cancel it',
          cancelButtonText: 'No'
        }).then((result) => {
          if (result.isConfirmed) {
            fetch('DISTRIBUTORfolder/cancel_order.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ order_id: orderId })
            })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                Swal.fire('Cancelled!', 'Your order has been cancelled.', 'success');
                setTimeout(loadOrders, 1200);
              } else {
                Swal.fire('Error', data.message || 'Unable to cancel order. Please check if the order is still pending and belongs to you.', 'error');
              }
            });
          }
        });
      }
    });
    </script>
    </script>

   



      



    

    
    
  </body>
</html>