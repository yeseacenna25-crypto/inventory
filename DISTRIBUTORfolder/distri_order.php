<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ORDERS</title>
    <link rel="stylesheet" type="text/css" href="../CSS/admin_dashboard.css?v=<?= time(); ?>" />
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
            

            <div class="container">

              <!-- Action Buttons -->
              <div class="action-buttons">

                  <button class="btn btn-dark" id="addProductBtn" data-bs-toggle="modal" data-bs-target="#productModal">Add Product</button>
                  <button class="btn btn-dark" onclick="clearOrder()">Clear</button>
                  <button class="btn btn-dark" onclick="window.location.href='distri_view_order.php'">Orders List</button>
              </div>

             

              <!-- Order Table -->
              <table class="table table-bordered align-middle text-center">
                  <thead class="table-light">
                  <tr>
                     <th>ID</th>
                      <th>Product</th>
                      <th>Quantity</th>
                      <th>Price</th>
                      <th>Total</th>
                      <th>Action</th>
                  </tr>
                  </thead>
                  <tbody id="order-table-body">
                  
                  
                  <tr data-id="#">
                      <td>--</td>
                      <td>--</td>
                      <td>
                            <input type="number" name="price" required maxlength="10"  min="0" max="9999999999" class="input">
                      </td>
                      <td class="price">--</td>
                      <td class="line-total">--</td>
                      <td><button class="btn btn-sm btn-danger" onclick="removeRow(this)">Remove</button></td>
                  </tr>
                  </tbody>
              </table>

               <!-- Total Order Value and Create Order -->
              <div class="row">
                  <div class="col-md-6">
                      <strong>Total Order Value:</strong> <span class="order-value" id="total-value"> <span>₱</span>0.00 </span>        
                  </div>
                  <div class="col-md-6 create-order-container">
                    <button type="button" class="btn" style="background-color: #640202; color: white;" onclick="createOrder()" id="createOrderBtn">
                        Create Order
                    </button>
                  </div>
              </div>

          </div>


          <!-- Product Selection Modal -->
          <?php include('../partials/order_modal.php'); ?>
          <!-- Product Selection Modal -->

          <!-- Customer Selection Modal -->
          <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                      <div class="modal-header" style="background-color: #410101; color: white;">
                          <h5 class="modal-title" id="customerModalLabel">
                              <i class="fa fa-user"></i> Select Customer
                          </h5>
                          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                          <form id="customerForm">
                              <div class="row">
                                  <div class="col-md-6">
                                      <label for="customerName" class="form-label">Customer Name *</label>
                                      <input type="text" class="form-control" id="customerName" required>
                                  </div>
                                  <div class="col-md-6">
                                      <label for="customerContact" class="form-label">Contact Number</label>
                                      <input type="text" class="form-control" id="customerContact">
                                  </div>
                              </div>
                              <div class="row mt-3">
                                  <div class="col-12">
                                      <label for="customerAddress" class="form-label">Address</label>
                                      <textarea class="form-control" id="customerAddress" rows="3"></textarea>
                                  </div>
                              </div>
                          </form>
                      </div>
                      <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                              <i class="fa fa-times"></i> Cancel
                          </button>
                          <button type="button" class="btn btn-primary" onclick="selectCustomer()">
                              <i class="fa fa-check"></i> Select Customer
                          </button>
                      </div>
                  </div>
              </div>
          </div>
          <!-- Customer Selection Modal -->

                  
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