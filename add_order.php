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
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ADD ORDER</title>
    <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css?v=<?= time(); ?>" />
     <link rel="stylesheet" type="text/css" href="CSS/view_product.css?v=<?= time(); ?>" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link
      rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
        <script>
            history.pushState(null, null, location.href);
            window.onpopstate = function () {
                history.go(1);
            };
        </script>
    <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
    
    <style>
        /* Modern Button Hover Effects */
        .btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
        }
        
        .btn:active {
            transform: translateY(0) !important;
        }
        
        #createOrderBtn:hover {
            transform: scale(1.05) !important;
            box-shadow: 0 10px 30px rgba(65, 1, 1, 0.3) !important;
        }
        
        #createOrderBtn:active {
            transform: scale(0.98) !important;
        }
        
        .action-buttons {
            gap: 15px;
        }
        
        .action-buttons .btn {
            min-width: 140px;
        }
        
        /* Button loading state */
        .btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn.loading::after {
            content: "";
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-left: 10px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            

            <div class="container">

              <!-- Action Buttons -->
              <div class="action-buttons">
                  <button class="btn btn-primary px-4 py-2 shadow-sm" id="selectCustomerBtn" data-bs-toggle="modal" data-bs-target="#customerModal" style="background: linear-gradient(135deg, #410101, #640202); border: none; border-radius: 10px; font-weight: 500; transition: all 0.3s ease;">
                      <i class="bi bi-person-plus me-2"></i>Select Customer
                  </button>
                  <button class="btn btn-success px-4 py-2 shadow-sm" id="addProductBtn" data-bs-toggle="modal" data-bs-target="#productModal" style="background: linear-gradient(135deg, #410101, #640202); border: none; border-radius: 10px; font-weight: 500; transition: all 0.3s ease;">
                      <i class="bi bi-plus-circle me-2"></i>Add Product
                  </button>
                  <button class="btn btn-warning px-4 py-2 shadow-sm" onclick="clearOrder()" style="background: linear-gradient(135deg, #410101, #640202); border: none; border-radius: 10px; font-weight: 500; transition: all 0.3s ease; color: white;">
                      <i class="bi bi-arrow-clockwise me-2"></i>Clear
                  </button>
                  <button class="btn btn-info px-4 py-2 shadow-sm" onclick="window.location.href='view_order.php'" style="background: linear-gradient(135deg, #410101, #640202); border: none; border-radius: 10px; font-weight: 500; color: white; transition: all 0.3s ease;">
                      <i class="bi bi-list-ul me-2"></i>Orders List
                  </button>
              </div>

              <!-- Customer Info -->
              <div class="mb-3">
                  <strong>Customer:</strong> <span id="selected-customer">--- Please select a customer ---</span>
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
                      <td><button class="btn btn-danger btn-sm px-3 shadow-sm" onclick="removeRow(this)" style="background: linear-gradient(135deg, #dc3545, #c82333); border: none; border-radius: 8px; font-weight: 500; transition: all 0.3s ease;">
                          <i class="bi bi-trash me-1"></i>Remove
                      </button></td>
                  </tr>
                  </tbody>
              </table>

              <!-- Total Order Value and Create Order -->
              <div class="row">
                  <div class="col-md-6">
                      <strong>Total Order Value:</strong> <span class="order-value" id="total-value"> <span>₱</span>0.00 </span>        
                  </div>
                  <div class="col-md-6 create-order-container">
                    <button type="button" class="btn btn-lg px-5 py-3 shadow" onclick="createOrder()" id="createOrderBtn" style="background: linear-gradient(135deg, #410101, #640202); border: none; border-radius: 12px; color: white; font-weight: 600; font-size: 1.1rem; transition: all 0.3s ease; transform: scale(1);">
                        <i class="bi bi-check-circle me-2"></i>Create Order
                    </button>
                  </div>
              </div>

          </div>


          <!-- Product Selection Modal -->
          <?php include('partials/order_modal.php'); ?>
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
                                      <label for="distributorSelect" class="form-label">Select Distributor</label>
                                      <select class="form-select" id="distributorSelect">
                                          <option value="">-- Select Distributor --</option>
                                      </select>
                                  </div>
                                  <div class="col-md-6">
                                      <label for="customerContact" class="form-label">Contact Number</label>
                                      <input type="text" class="form-control" id="customerContact">
                                  </div>
                              </div>
                              <div class="row mt-3">

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
                          <button type="button" class="btn btn-secondary px-4 py-2 shadow-sm" data-bs-dismiss="modal" style="background: linear-gradient(135deg, #6c757d, #5a6268); border: none; border-radius: 8px; font-weight: 500; transition: all 0.3s ease;">
                              <i class="bi bi-x-circle me-2"></i>Cancel
                          </button>
                          <button type="button" class="btn btn-primary px-4 py-2 shadow-sm" onclick="selectCustomer()" style="background: linear-gradient(135deg, #007bff, #0056b3); border: none; border-radius: 8px; font-weight: 500; transition: all 0.3s ease;">
                              <i class="bi bi-check-circle me-2"></i>Select Customer
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
    
    <script>
      // DOM Content Loaded event to ensure everything is initialized
      document.addEventListener('DOMContentLoaded', function() {
          console.log('DOM loaded, initializing add order page...');
          
          // Check if modal exists
          const productModal = document.getElementById('productModal');
          if (productModal) {
              console.log('Product modal found');
          } else {
              console.error('Product modal not found!');
          }
          
          // Check if search input exists
          const productSearch = document.getElementById('productSearch');
          if (productSearch) {
              console.log('Product search input found');
          } else {
              console.error('Product search input not found!');
          }
      });
      
      // Product Modal Functionality
      let allProducts = [];
      let selectedCustomer = null;
      
      // Load products when modal is shown
      document.getElementById('productModal').addEventListener('shown.bs.modal', function () {
          console.log('Product modal opened, loading products...');
          loadProducts();
      });
      
      // Search functionality
      document.getElementById('productSearch').addEventListener('input', function() {
          try {
              const searchTerm = this.value.toLowerCase();
              console.log('Searching for:', searchTerm);
              filterProducts(searchTerm);
          } catch (error) {
              console.error('Error in search:', error);
          }
      });
      
      function loadProducts() {
          console.log('loadProducts() function called');
          const tbody = document.getElementById('productsTableBody');
          tbody.innerHTML = `
              <tr>
                  <td colspan="8" class="text-center">
                      <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden">Loading...</span>
                      </div>
                      <div class="mt-2">Loading products...</div>
                  </td>
              </tr>
          `;
          
          console.log('Fetching from fetch_products.php...');
          fetch('fetch_products.php')
              .then(response => {
                  console.log('Response status:', response.status);
                  console.log('Response headers:', response.headers);
                  return response.text(); // Get as text first to see raw response
              })
              .then(text => {
                  console.log('Raw response:', text);
                  try {
                      const data = JSON.parse(text);
                      console.log('Parsed data:', data);
                      if (data.success) {
                          allProducts = data.products;
                          console.log('Products loaded:', allProducts.length);
                          console.log('First product:', allProducts[0]);
                          displayProducts(allProducts);
                      } else {
                          console.error('API Error:', data.message || data.error || 'Unknown error');
                          tbody.innerHTML = `
                              <tr>
                                  <td colspan="8" class="text-center text-danger">
                                      <i class="fa fa-exclamation-triangle"></i> Error loading products: ${data.message || data.error || 'Unknown error'}
                                  </td>
                              </tr>
                          `;
                      }
                  } catch (parseError) {
                      console.error('JSON Parse Error:', parseError);
                      console.error('Raw text was:', text);
                      tbody.innerHTML = `
                          <tr>
                              <td colspan="8" class="text-center text-danger">
                                  <i class="fa fa-exclamation-triangle"></i> Invalid response format
                              </td>
                          </tr>
                      `;
                  }
              })
              .catch(error => {
                  console.error('Fetch Error:', error);
                  tbody.innerHTML = `
                      <tr>
                          <td colspan="8" class="text-center text-danger">
                              <i class="fa fa-exclamation-triangle"></i> Failed to load products: ${error.message}
                          </td>
                      </tr>
                  `;
              });
      }
      
      function displayProducts(products) {
          const tbody = document.getElementById('productsTableBody');
          
          if (products.length === 0) {
              tbody.innerHTML = `
                  <tr>
                      <td colspan="8" class="text-center text-muted">
                          <i class="fa fa-inbox"></i> No products found
                      </td>
                  </tr>
              `;
              return;
          }
          
          tbody.innerHTML = products.map(product => {
              // Escape product name for onclick handler
              const escapedName = product.name.replace(/'/g, "\\'").replace(/"/g, '\\"');
              
              // Debug log for each product
              console.log('Creating row for product:', {
                  id: product.id,
                  name: product.name,
                  image: product.image,
                  price_raw: product.price_raw,
                  price_raw_type: typeof product.price_raw
              });
              
              return `
              <tr>
                  <td>${product.id}</td>
                  <td>
                      ${product.image && product.image !== '' && product.image !== 'undefined' 
                          ? `<img src="${product.image}" alt="${product.name}" 
                               style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd;" 
                               onerror="console.log('Image failed to load: ${product.image}'); this.style.display='none'; this.nextElementSibling.style.display='flex';">
                             <div style="display: none; width: 50px; height: 50px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; align-items: center; justify-content: center; color: #6c757d;">
                                 <i class="fa fa-image" style="font-size: 20px;"></i>
                             </div>`
                          : `<div style="width: 50px; height: 50px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                                 <i class="fa fa-image" style="font-size: 20px;"></i>
                             </div>`
                      }
                  </td>
                  <td>${product.name}</td>
                  <td><span class="badge bg-secondary">${product.category || 'N/A'}</span></td>
                  <td>${product.quantity}</td>
                  <td>₱${product.price}</td>
                  <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                      title="${product.description || ''}">${product.description || 'No description'}</td>
                  <td>
                      <button class="btn btn-primary btn-sm select-product-btn" 
                              data-product-id="${product.id}"
                              data-product-name="${product.name}"
                              data-product-price="${product.price_raw || 0}"
                              title="Add to order">
                          <i class="fa fa-plus"></i> Select
                      </button>
                  </td>
              </tr>
              `;
          }).join('');
          
          // Add event listeners to select buttons
          tbody.querySelectorAll('.select-product-btn').forEach(button => {
              button.addEventListener('click', function() {
                  console.log('Select button clicked, reading attributes...');
                  
                  const productId = this.getAttribute('data-product-id');
                  const productName = this.getAttribute('data-product-name');
                  const productPriceRaw = this.getAttribute('data-product-price');
                  
                  console.log('Raw attributes:', {
                      productId: productId,
                      productName: productName,
                      productPriceRaw: productPriceRaw
                  });
                  
                  const productPrice = parseFloat(productPriceRaw);
                  
                  console.log('Parsed price:', productPrice, 'isNaN:', isNaN(productPrice));
                  console.log('Select button clicked:', productId, productName, productPrice);
                  
                  selectProduct(productId, productName, productPrice);
              });
          });
      }
      
      function filterProducts(searchTerm) {
          const filteredProducts = allProducts.filter(product => 
              product.name.toLowerCase().includes(searchTerm) ||
              product.category.toLowerCase().includes(searchTerm) ||
              product.description.toLowerCase().includes(searchTerm)
          );
          displayProducts(filteredProducts);
      }
      
      function selectProduct(productId, productName, productPrice) {
          console.log('selectProduct called:', {productId, productName, productPrice});
          
          // More robust validation
          const validProductId = productId && (typeof productId === 'string' || typeof productId === 'number') && productId.toString().trim() !== '';
          const validProductName = productName && typeof productName === 'string' && productName.trim() !== '';
          const numericPrice = parseFloat(productPrice);
          const validProductPrice = !isNaN(numericPrice) && numericPrice >= 0;
          
          console.log('Validation details:', {
              productId: productId,
              validProductId: validProductId,
              productName: productName,
              validProductName: validProductName,
              productPrice: productPrice,
              numericPrice: numericPrice,
              validProductPrice: validProductPrice
          });
          
          if (!validProductId || !validProductName || !validProductPrice) {
              console.error('Invalid product data:', {
                  productId: productId, 
                  productName: productName, 
                  productPrice: productPrice,
                  issues: {
                      invalidId: !validProductId,
                      invalidName: !validProductName,
                      invalidPrice: !validProductPrice
                  }
              });
              
              let errorMsg = 'Invalid product data: ';
              if (!validProductId) errorMsg += 'Invalid ID. ';
              if (!validProductName) errorMsg += 'Invalid name. ';
              if (!validProductPrice) errorMsg += 'Invalid price. ';
              
              Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: errorMsg
              });
              return;
          }
          
          // Use the validated numeric price
          productPrice = numericPrice;
          
          try {
              // Close modal
              const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
              if (modal) {
                  console.log('Closing modal...');
                  modal.hide();
              } else {
                  console.warn('Modal instance not found');
              }
              
              // Add product to order table
              addProductToOrder(productId, productName, productPrice);
          } catch (error) {
              console.error('Error in selectProduct:', error);
              Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: 'Failed to select product: ' + error.message
              });
          }
      }
      
      function addProductToOrder(productId, productName, productPrice) {
          console.log('addProductToOrder called:', productId, productName, productPrice);
          
          const tableBody = document.getElementById('order-table-body');
          if (!tableBody) {
              console.error('order-table-body not found');
              return;
          }
          
          // Check if product already exists in the order
          const existingRow = tableBody.querySelector(`tr[data-id="${productId}"]`);
          if (existingRow) {
              console.log('Product already exists, updating quantity');
              // If exists, increase quantity
              const quantityInput = existingRow.querySelector('input[type="number"]');
              if (quantityInput) {
                  quantityInput.value = parseInt(quantityInput.value) + 1;
                  updateRowTotal(existingRow);
              }
              return;
          }
          
          console.log('Adding new product to order');
          
          // Create new row
          const newRow = document.createElement('tr');
          newRow.setAttribute('data-id', productId);
          newRow.innerHTML = `
              <td>${productId}</td>
              <td>${productName}</td>
              <td>
                  <input type="number" value="1" min="1" class="form-control quantity-input" 
                         style="width: 80px;" onchange="updateRowTotal(this.closest('tr'))">
              </td>
              <td class="price">₱${productPrice.toFixed(2)}</td>
              <td class="line-total">₱${productPrice.toFixed(2)}</td>
              <td>
                  <button class="btn btn-sm btn-danger" onclick="removeRow(this)">
                      <i class="fa fa-trash"></i> Remove
                  </button>
              </td>
          `;
          
          // Remove the placeholder row if it exists
          const placeholderRow = tableBody.querySelector('tr[data-id="#"]');
          if (placeholderRow) {
              console.log('Removing placeholder row');
              placeholderRow.remove();
          }
          
          tableBody.appendChild(newRow);
          updateTotalValue();
          
          console.log('Product added successfully');
          
          // Show SweetAlert success message
          Swal.fire({
              icon: 'success',
              title: 'Product Added!',
              text: `${productName} has been added to your order.`,
              confirmButtonColor: '#640202',
              timer: 1500,
              showConfirmButton: false
          });
      }
      
      function updateRowTotal(row) {
          const quantity = parseInt(row.querySelector('.quantity-input').value) || 1;
          const priceText = row.querySelector('.price').textContent.replace('₱', '');
          const price = parseFloat(priceText);
          const total = quantity * price;
          
          row.querySelector('.line-total').textContent = `₱${total.toFixed(2)}`;
          updateTotalValue();
      }
      
      function removeRow(button) {
          const row = button.closest('tr');
          row.remove();
          updateTotalValue();
          
          // If no products left, add placeholder row
          const tableBody = document.getElementById('order-table-body');
          if (tableBody.children.length === 0) {
              tableBody.innerHTML = `
                  <tr data-id="#">
                      <td>--</td>
                      <td>--</td>
                      <td>
                          <input type="number" name="price" required maxlength="10" min="0" max="9999999999" class="input">
                      </td>
                      <td class="price">--</td>
                      <td class="line-total">--</td>
                      <td><button class="btn btn-sm btn-danger" onclick="removeRow(this)">Remove</button></td>
                  </tr>
              `;
          }
      }
      
      function updateTotalValue() {
          const tableBody = document.getElementById('order-table-body');
          const rows = tableBody.querySelectorAll('tr[data-id]:not([data-id="#"])');
          let total = 0;
          
          rows.forEach(row => {
              const lineTotalText = row.querySelector('.line-total').textContent.replace('₱', '');
              total += parseFloat(lineTotalText) || 0;
          });
          
          document.getElementById('total-value').innerHTML = `<span>₱</span> ${total.toFixed(2)}`;
      }

      // Load distributors into dropdown when modal is shown
      document.getElementById('customerModal').addEventListener('show.bs.modal', function() {
          const select = document.getElementById('distributorSelect');
          select.innerHTML = '<option value="">-- Select Distributor --</option>';
          fetch('fetch_distributors.php')
              .then(response => response.json())
              .then(data => {
                  if (data.success && Array.isArray(data.data)) {
                      data.data.forEach(dist => {
                          const option = document.createElement('option');
                          option.value = dist.distributor_id;
                          option.textContent = dist.distributor_name;
                          option.setAttribute('data-contact', dist.distrib_contact_number);
                          option.setAttribute('data-address', dist.distrib_address);
                          select.appendChild(option);
                      });
                  }
              });
      });

      // Autofill customer fields when distributor selected
      document.getElementById('distributorSelect').addEventListener('change', function() {
          const selected = this.options[this.selectedIndex];
          // Auto-fill modal fields
          const distributorName = selected.value ? selected.textContent : '';
          document.getElementById('customerContact').value = selected.getAttribute('data-contact') || '';
          document.getElementById('customerAddress').value = selected.getAttribute('data-address') || '';

          // Also auto-fill customer name field (add if missing)
          let customerNameInput = document.getElementById('customerName');
          if (!customerNameInput) {
              customerNameInput = document.createElement('input');
              customerNameInput.type = 'hidden';
              customerNameInput.id = 'customerName';
              document.getElementById('customerForm').appendChild(customerNameInput);
          }
          customerNameInput.value = distributorName;
      });

      // Customer selection functionality
      function selectCustomer() {
          const customerName = document.getElementById('customerName').value.trim();
          const customerContact = document.getElementById('customerContact').value.trim();
          const customerAddress = document.getElementById('customerAddress').value.trim();

          if (!customerName) {
              Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: 'Customer name is required!'
              });
              return;
          }

          selectedCustomer = {
              name: customerName,
              contact: customerContact,
              address: customerAddress
          };

          // Update customer display with all info
          document.getElementById('selected-customer').innerHTML = `
              <strong>Name:</strong> ${customerName}<br>
              <strong>Contact:</strong> ${customerContact}<br>
              <strong>Address:</strong> ${customerAddress}
          `;

          // Close modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
          modal.hide();

          // Clear form
          document.getElementById('customerForm').reset();
          document.getElementById('distributorSelect').selectedIndex = 0;

          Swal.fire({
              icon: 'success',
              title: 'Success',
              text: 'Customer selected successfully!',
              timer: 1500,
              showConfirmButton: false
          });
      }
      
      // Clear order functionality
      function clearOrder() {
          Swal.fire({
              title: 'Are you sure?',
              text: "This will clear all products and customer selection!",
              icon: 'warning',
              showCancelButton: true,
              confirmButtonColor: '#640202',
              cancelButtonColor: '#6c757d',
              confirmButtonText: 'Yes, clear it!'
          }).then((result) => {
              if (result.isConfirmed) {
                  // Clear customer selection
                  selectedCustomer = null;
                  document.getElementById('selected-customer').textContent = '--- Please select a customer ---';
                  
                  // Clear order table
                  const tableBody = document.getElementById('order-table-body');
                  tableBody.innerHTML = `
                      <tr data-id="#">
                          <td>--</td>
                          <td>--</td>
                          <td>
                              <input type="number" name="price" required maxlength="10" min="0" max="9999999999" class="input">
                          </td>
                          <td class="price">--</td>
                          <td class="line-total">--</td>
                          <td><button class="btn btn-sm btn-danger" onclick="removeRow(this)">Remove</button></td>
                      </tr>
                  `;
                  
                  // Reset total
                  document.getElementById('total-value').innerHTML = `<span>₱</span>0.00`;
                  
                  Swal.fire({
                      icon: 'success',
                      title: 'Cleared!',
                      text: 'Order has been cleared.',
                      timer: 1500,
                      showConfirmButton: false
                  });
              }
          });
      }
      
      // Create order functionality
      function createOrder() {
          // Validate customer selection
          if (!selectedCustomer) {
              Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: 'Please select a customer first!'
              });
              return;
          }
          
          // Get order items
          const tableBody = document.getElementById('order-table-body');
          const rows = tableBody.querySelectorAll('tr[data-id]:not([data-id="#"])');
          
          if (rows.length === 0) {
              Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: 'Please add at least one product to the order!'
              });
              return;
          }
          
          const items = [];
          rows.forEach(row => {
              const productId = row.getAttribute('data-id');
              const productName = row.cells[1].textContent;
              const quantity = parseInt(row.querySelector('.quantity-input').value);
              const priceText = row.querySelector('.price').textContent.replace('₱', '');
              const unitPrice = parseFloat(priceText);
              
              items.push({
                  product_id: productId,
                  product_name: productName,
                  quantity: quantity,
                  unit_price: unitPrice
              });
          });
          
          // Show loading
          const createBtn = document.getElementById('createOrderBtn');
          const originalText = createBtn.innerHTML;
          createBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Creating...';
          createBtn.disabled = true;
          
          // Prepare order data
          // Get distributor_id from distributorSelect
          const distributorSelect = document.getElementById('distributorSelect');
          const distributor_id = distributorSelect.value || null;
          const orderData = {
              customer_name: selectedCustomer.name,
              customer_contact: selectedCustomer.contact,
              customer_address: selectedCustomer.address,
              distributor_id: distributor_id,
              items: items
          };
          
          // Send to backend
          fetch('process_order.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
              },
              body: JSON.stringify(orderData)
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  Swal.fire({
                      icon: 'success',
                      title: 'Success!',
                      text: `Order #${data.order_id} created successfully!`,
                      confirmButtonColor: '#640202'
                  }).then(() => {
                      // Clear the order after successful creation
                      clearOrder();
                  });
              } else {
                  Swal.fire({
                      icon: 'error',
                      title: 'Error',
                      text: data.message || 'Failed to create order'
                  });
                  console.error('Order creation error:', data.message);
              }
          })
          .catch(error => {
              console.error('Error:', error);
              Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: error.message || 'An error occurred while creating the order'
              });
          })
          .finally(() => {
              // Restore button
              createBtn.innerHTML = originalText;
              createBtn.disabled = false;
          });
      }

    </script>
    
    <!-- Include responsive sidebar functionality -->
    <script src="sidebar-drawer.js"></script>

    
    
    
  </body>
</html>
