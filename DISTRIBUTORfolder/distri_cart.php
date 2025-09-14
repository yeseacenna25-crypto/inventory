<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Check if distributor is logged in
if (!isset($_SESSION['distributor_id'])) {
    header('Location: distri_login.php');
    exit();
}

// Connect to database and fetch distributor information
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch distributor details
$stmt = $conn->prepare("SELECT distrib_fname, distrib_mname, distrib_lname, distrib_contact_number, distrib_address FROM distributor_signup WHERE distributor_id = ?");
$stmt->bind_param("i", $_SESSION['distributor_id']);
$stmt->execute();
$result = $stmt->get_result();
$distributor = $result->fetch_assoc();

$distributor_name = $distributor ? trim($distributor['distrib_fname'] . ' ' . $distributor['distrib_mname'] . ' ' . $distributor['distrib_lname']) : '';
$distributor_contact = $distributor['distrib_contact_number'] ?? '';
$distributor_address = $distributor['distrib_address'] ?? '';

$stmt->close();
$conn->close();

// Function to get product image (handles both BLOB and file path)
function getProductImageSrc($product) {
  if (!empty($product['product_image'])) {
    $imageData = $product['product_image'];
    
    // If it's a filename, use uploads folder
    if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $imageData)) {
      // Try multiple path variations
      $possiblePaths = [
        '../uploads/' . $imageData,
        'uploads/' . $imageData,
        $imageData,
        '../' . $imageData
      ];
      
      foreach ($possiblePaths as $filePath) {
        if (file_exists($filePath)) {
          return $filePath;
        }
      }
    }
    // If it's binary data (BLOB), show as base64
    else {
      try {
        // Check if it's actually image data by looking at headers first
        if (strlen($imageData) > 8) {
          $header = substr($imageData, 0, 8);
          
          // JPEG detection
          if (substr($imageData, 0, 2) === "\xFF\xD8") {
            return "data:image/jpeg;base64," . base64_encode($imageData);
          }
          // PNG detection
          elseif ($header === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
            return "data:image/png;base64," . base64_encode($imageData);
          }
          // GIF detection
          elseif (substr($imageData, 0, 6) === "GIF87a" || substr($imageData, 0, 6) === "GIF89a") {
            return "data:image/gif;base64," . base64_encode($imageData);
          }
        }
        
        // Try using finfo as backup
        if (function_exists('finfo_open')) {
          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          if ($finfo) {
            $mimeType = finfo_buffer($finfo, $imageData);
            finfo_close($finfo);
            if ($mimeType && strpos($mimeType, 'image/') === 0) {
              $base64 = base64_encode($imageData);
              return "data:$mimeType;base64,$base64";
            }
          }
        }
      } catch (Exception $e) {
        error_log("Error processing binary data: " . $e->getMessage());
      }
    }
  }
  return '';
}

// Database connection
$conn = mysqli_connect('localhost', 'root', '', 'inventory_negrita');
$productImagesJson = '{}';

if ($conn) {
  $result = mysqli_query($conn, "SELECT product_id, product_image FROM products");
  $productImages = [];
  
  if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
      $imgSrc = getProductImageSrc($row);
      if (!empty($imgSrc)) {
        $productImages[$row['product_id']] = $imgSrc;
      }
    }
  }
  
  $productImagesJson = json_encode($productImages);
  mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CART</title>
    <link rel="stylesheet" type="text/css" href="distributor.css?v=<?= time(); ?>" />
    <link rel="stylesheet" type="text/css" href="distri_cart.css?v=<?= time(); ?>" />
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
                    <div class="container-fluid">
                        
                        <!-- Cart Header -->
                        <div class="cart-header">
                          <h2>
                    <i class="bi bi-cart me-3"></i>
                    SHOPPING CART
                  </h2>
                  <p class="mb-0 mt-2 opacity-90">Review your items before checkout</p>
                </div>

                        <!-- Cart Content -->
                        <div class="row">
                            <!-- Cart Items -->
                            <div class="col-lg-8 col-md-7">
                                <div class="cart-items-section">
                                    <div id="cartItemsContainer">
                                        <!-- Cart items will be loaded here by JavaScript -->
                                    </div>
                                    
                                    <!-- Empty Cart State -->
                                    <div class="empty-cart" id="emptyCartState" style="display: none;">
                                        <div class="empty-cart-icon">
                                            <i class="bi bi-cart-x"></i>
                                        </div>
                                        <h4>Your cart is empty</h4>
                                        <p>Looks like you haven't added any items to your cart yet.</p>
                                        <a href="distri_products.php" class="btn btn-primary">
                                            <i class="bi bi-shop"></i> Continue Shopping
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Cart Summary -->
                            <div class="col-lg-4 col-md-5">
                                <div class="cart-summary">
                                    <h3>SUMMARY</h3>
                                    
                                    <div class="summary-row">
                                        <span>Subtotal</span>
                                        <span class="summary-icon">
                                        </span>
                                        <span id="subtotalAmount">₱0.00</span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Handling Fee (3%)</span>
                                        <span class="summary-icon">
                                        </span>
                                        <span id="handlingFeeAmount"></span>
                                    </div>
                                    
                                   
                                    
                                    <hr>
                                    
                                    <div class="summary-row total-row">
                                        <strong>Total</strong>
                                        <strong id="totalAmount">₱0.00</strong>
                                    </div>
                                    
                                    <button class="checkout-btn" id="checkoutBtn" disabled>
                                        Go to Checkout
                                    </button>
                                    
                                    <div class="continue-shopping">
                                        <a href="distri_products.php">
                                            <i class="bi bi-arrow-left"></i> Continue Shopping
                                        </a>
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
    
    <script>
        // Cart management - Make it distributor specific
        const distributorId = <?= $_SESSION['distributor_id'] ?>;
        const cartKey = `distributorCart_${distributorId}`;
        let cart = JSON.parse(localStorage.getItem(cartKey)) || [];
        
        console.log('Distributor ID:', distributorId);
        console.log('Cart Key:', cartKey);
        console.log('Loaded cart for distributor:', cart);
        
        // Product images from PHP
        let productImages = <?php echo $productImagesJson; ?>;
        
        console.log('Product images loaded:', productImages);

        // Helper function to save cart with distributor-specific key
        function saveCart() {
            localStorage.setItem(cartKey, JSON.stringify(cart));
            console.log('Cart saved for distributor:', distributorId, cart);
        }

        // Helper function to clear cart completely (no confirmation)
        function clearCartDirectly() {
            cart = [];
            localStorage.removeItem(cartKey);
            console.log('Cart cleared for distributor:', distributorId);
            
            // Force update the cart display immediately
            const cartContainer = document.getElementById('cartItemsContainer');
            const emptyState = document.getElementById('emptyCartState');
            const checkoutBtn = document.getElementById('checkoutBtn');
            
            if (cartContainer) cartContainer.style.display = 'none';
            if (emptyState) emptyState.style.display = 'block';
            if (checkoutBtn) checkoutBtn.disabled = true;
            
            // Update summary
            updateSummary();
        }

        // Clean up old cart data from localStorage (remove old generic keys)
        function cleanupOldCartData() {
            const keysToRemove = ['cart', 'distributorCart'];
            keysToRemove.forEach(key => {
                if (localStorage.getItem(key)) {
                    localStorage.removeItem(key);
                    console.log('Removed old cart key:', key);
                }
            });
        }

        // Initialize - clean up old data on page load
        cleanupOldCartData();

        // Enhanced SweetAlert function
        function showAlert(title, text, icon) {
            if (window.Swal) {
                Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    confirmButtonColor: '#410101',
                    background: 'linear-gradient(135deg, #faf4f4 0%, #f7e4e4 100%)',
                    backdrop: 'rgba(0,0,0,0.4)',
                    timer: icon === 'success' ? 2000 : null,
                    timerProgressBar: true
                });
            } else {
                alert(title + '\n' + text);
            }
        }

        // Get product image source
        function getProductImageSrc(productId) {
            console.log('Getting image for product ID:', productId);
            console.log('Available product images:', productImages);
            
            if (productImages[productId]) {
                console.log('Found image for product', productId, ':', productImages[productId]);
                return productImages[productId];
            } else {
                console.log('No image found for product ID:', productId);
            }
            return null;
        }

        // Update cart display
        function updateCartDisplay() {
            const cartContainer = document.getElementById('cartItemsContainer');
            const emptyState = document.getElementById('emptyCartState');
            const checkoutBtn = document.getElementById('checkoutBtn');
            
            if (cart.length === 0) {
                cartContainer.style.display = 'none';
                emptyState.style.display = 'block';
                checkoutBtn.disabled = true;
                updateSummary();
                return;
            }
            
            cartContainer.style.display = 'block';
            emptyState.style.display = 'none';
            checkoutBtn.disabled = false;
            
            let cartHTML = '';
            
            cart.forEach((item, index) => {
                const stockWarning = item.quantity >= item.maxStock - 2;
                const imageSrc = getProductImageSrc(item.id);
                
                console.log(`Product ${item.id}: Image src = ${imageSrc}`);
                
                cartHTML += `
                    <div class="cart-item" data-index="${index}">
                        <div class="item-image">
                            ${imageSrc ? 
                                `<img src="${imageSrc}" alt="${item.name}" class="product-cart-img" onerror="this.parentNode.innerHTML='<div class=\\"placeholder-image\\"><i class=\\"bi bi-image\\"></i></div>` :
                                `<div class="placeholder-image"><i class="bi bi-image"></i></div>`
                            }
                                 <div class="item-content">
                            <div class="item-info">
                                <h5 class="item-name">${item.name}</h5>
                                <p class="item-details">Available: ${item.maxStock}</p>
                                
                                ${stockWarning ? `
                                <div class="stock-warning show">
                                    <i class="bi bi-exclamation-circle"></i>
                                    Just a few left. Order soon.
                                </div>
                                ` : ''}
                            </div>
                            
                            <div class="item-actions">
                                <button class="action-btn remove-btn" onclick="removeItem(${index})" title="Move to Trash">
                                    <i class="bi bi-trash"></i>
                                </button>
                                
                                <div class="quantity-controls">
                                    <button class="qty-btn" onclick="updateQuantity(${index}, -1)">−</button>
                                    <span class="qty-display">${item.quantity}</span>
                                    <button class="qty-btn" onclick="updateQuantity(${index}, 1)" 
                                            ${item.quantity >= item.maxStock ? 'disabled' : ''}>+</button>
                                </div>
                                    </div>
                        
                        <div class="item-pricing">
                            ${item.originalPrice && item.originalPrice > item.price ? 
                                `<div class="price-original">₱${item.originalPrice.toFixed(2)}</div>` : ''
                            }
                            <div class="price-current">₱${item.price.toFixed(2)}</div>
                        </div>
                    </div>
           
                                                  
                            </div>

                            
                        </div>
                             `;
            });
                        

                    
            
            cartContainer.innerHTML = cartHTML;
            updateSummary();
        }

        // Update summary
        function updateSummary() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const handlingFee = subtotal * 0.03; // 3% handling fee
            const total = subtotal + handlingFee;
            
            document.getElementById('subtotalAmount').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('handlingFeeAmount').textContent = `₱${handlingFee.toFixed(2)}`;
            document.getElementById('totalAmount').textContent = `₱${total.toFixed(2)}`;
        }

        // Update quantity
        function updateQuantity(index, change) {
            const item = cart[index];
            const newQuantity = item.quantity + change;
            
            if (newQuantity <= 0) {
                removeItem(index);
                return;
            }
            
            if (newQuantity > item.maxStock) {
                showAlert('Stock Limit', `Cannot add more ${item.name}. Available stock: ${item.maxStock}`, 'warning');
                return;
            }
            
            cart[index].quantity = newQuantity;
            saveCart();
            updateCartDisplay();
            
            // Add visual feedback
            const cartItem = document.querySelector(`[data-index="${index}"]`);
            if (cartItem) {
                cartItem.classList.add('item-updated');
                setTimeout(() => {
                    cartItem.classList.remove('item-updated');
                }, 300);
            }
        }

        // Remove item
        function removeItem(index) {
            const itemName = cart[index].name;
            
            Swal.fire({
                title: 'Remove Item?',
                text: `Are you sure you want to remove ${itemName} from your cart?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, remove it',
                cancelButtonText: 'Keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    cart.splice(index, 1);
                    saveCart();
                    updateCartDisplay();
                    showAlert('Item Removed', `${itemName} has been removed from your cart.`, 'success');
                }
            });
        }

        // Clear cart
        function clearCart() {
            Swal.fire({
                title: 'Clear Cart?',
                text: 'Are you sure you want to remove all items from your cart?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, clear cart',
                cancelButtonText: 'Keep items'
            }).then((result) => {
                if (result.isConfirmed) {
                    clearCartDirectly();
                    updateCartDisplay();
                    showAlert('Cart Cleared', 'All items have been removed from your cart.', 'success');
                }
            });
        }

        // Checkout
        // Comprehensive checkout function
        async function proceedToCheckout() {
            if (cart.length === 0) {
                showAlert('Empty Cart', 'Your cart is empty. Add some items before checkout.', 'warning');
                return;
            }
            
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const handlingFee = total * 0.03; // 3% handling fee
            const finalTotal = total + handlingFee;
            
            // Step 1: Show order summary and get customer information
            const { value: customerInfo } = await Swal.fire({
                title: 'ORDER INFORMATION',
                html: `
                    <div class="checkout-form">
                      <div class="order-summary mb-3">
                        <h6>Order Summary</h6>
                        <div class="summary-items">
                          ${cart.map(item => `
                            <div class="summary-item d-flex justify-content-between">
                              <span>${item.name} (x${item.quantity})</span>
                              <span>₱${(item.price * item.quantity).toFixed(2)}</span>
                            </div>
                          `).join('')}
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                          <span>Subtotal:</span>
                          <span>₱${total.toFixed(2)}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                          <span>Handling Fee (3%):</span>
                          <span>₱${handlingFee.toFixed(2)}</span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold">
                          <span>Total:</span>
                          <span>₱${finalTotal.toFixed(2)}</span>
                        </div>
                      </div>
                      <hr>
                      <div class="customer-form">
                        <h6>Customer Information</h6>
                        <div class="customer-info-display">
                          <div class="info-item">
                            <strong>Customer Name:</strong>
                            <span><?= htmlspecialchars($distributor_name) ?></span>
                          </div>
                          <div class="info-item">
                            <strong>Contact Number:</strong>
                            <span><?= htmlspecialchars($distributor_contact) ?></span>
                          </div>
                          <div class="info-item">
                            <strong>Address:</strong>
                            <span><?= htmlspecialchars($distributor_address) ?></span>
                          </div>
                          <div class="mb-2">
                                <textarea id="order_notes" class="swal2-input" placeholder="Add a note for the order." style="margin: 5px 0; width: 100%; height: 80px; resize: vertical;"></textarea>
                                
                            </div>




                        </div>
                      </div>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonColor: '#410101',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Place Order',
                cancelButtonText: 'Cancel',
                width: '600px',
                preConfirm: () => {
                    // Use distributor information directly from PHP
                    const name = '<?= addslashes($distributor_name) ?>';
                    const contact = '<?= addslashes($distributor_contact) ?>';
                    const address = '<?= addslashes($distributor_address) ?>';
                    const notes = document.getElementById('order_notes') ? document.getElementById('order_notes').value : '';
                    if (!name || !contact || !address) {
                        Swal.showValidationMessage('Distributor information is incomplete. Please update your profile.');
                        return false;
                    }
                    return {
                        name: name,
                        contact: contact,
                        address: address,
                        notes: notes
                    };
                }
            });
            
            if (!customerInfo) return;
            
            // Step 2: Process the order
            try {
                // Show loading
                Swal.fire({
                    title: 'Processing Order...',
                    text: 'Please wait while we process your order.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Prepare order data
                const orderNotes = customerInfo.notes || '';
                const orderData = {
                    customer_info: {
                        name: customerInfo.name,
                        contact: customerInfo.contact,
                        address: customerInfo.address
                    },
                    items: cart.map(item => ({
                        id: item.id,
                        name: item.name,
                        quantity: item.quantity,
                        price: item.price
                    })),
                    order_notes: orderNotes
                };
                
                // Send order to server
                const response = await fetch('process_checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Clear cart on success - distributor specific
                    clearCartDirectly();
                    updateCartDisplay();
                    
                    console.log('Order successful - cart cleared for distributor:', distributorId);
                    
                    // Show success message with order details
                    Swal.fire({
                        icon: 'success',
                        title: 'Order Placed Successfully!',
                        html: `
                            <div class="order-success">
                                <p><strong>Order ID:</strong> #${result.order_id}</p>
                                <p><strong>Customer:</strong> ${result.order_details.customer_name}</p>
                                <p><strong>Contact:</strong> ${result.order_details.customer_contact}</p>
                                <p><strong>Items:</strong> ${result.order_details.total_items}</p>
                                <p><strong>Total:</strong> ₱${result.order_details.final_total.toFixed(2)}</p>
                                <p><strong>Status:</strong> <span class="badge bg-warning">${result.order_details.status}</span></p>
                                <hr>
                                <p class="text-muted">Order confirmation has been logged. The admin will process your order shortly.</p>
                            </div>
                        `,
                        confirmButtonColor: '#410101',
                        confirmButtonText: 'Continue Shopping'
                    }).then(() => {
                        // Redirect to products page for continued shopping
                        window.location.href = 'distri_products.php';
                    });
                } else {
                    throw new Error(result.message || 'Failed to process order');
                }
            } catch (error) {
                console.error('Checkout error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Order Failed',
                    text: error.message || 'There was an error processing your order. Please try again.',
                    confirmButtonColor: '#410101'
                });
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            updateCartDisplay();
            
            // Attach checkout button event
            const checkoutBtn = document.getElementById('checkoutBtn');
            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', proceedToCheckout);
            }
        });
    </script>

    <!-- Include responsive sidebar functionality -->
    <script src="../sidebar-drawer.js"></script>



    <script>
        // Debug: Log distributor information to console
        console.log('Distributor Info Loaded:');
        console.log('Name: <?= addslashes($distributor_name) ?>');
        console.log('Contact: <?= addslashes($distributor_contact) ?>');
        console.log('Address: <?= addslashes($distributor_address) ?>');
    </script>

    

    
    
  </body>
</html>