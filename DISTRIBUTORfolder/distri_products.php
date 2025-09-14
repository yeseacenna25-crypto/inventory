<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PRODUCTS</title>
    <link rel="stylesheet" type="text/css" href="distributor.css?v=<?= time(); ?>" />
    <link rel="stylesheet" type="text/css" href="distri_cart.css?v=<?= time(); ?>" />
    <link rel="stylesheet" type="text/css" href="distri_product.css?v=<?= time(); ?>" />

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

            <!-- Modern Products Container -->
            <div class="products-container">
              <div class="container-fluid">
                
                <!-- Modern Header -->

                
                <div class="products-header">
                  <h2>
                    <i class="bi bi-box-seam me-3"></i>
                    PRODUCT CATALOG
                  </h2>
                  <p class="mb-0 mt-2 opacity-90">Browse and order from our premium collection</p>
                </div>

                <!-- Category Bar -->
                 <div class="category-bar">
              <div class="category-item active">All</div>
              <div class="category-item">Beauty</div>
              <div class="category-item">Capsule</div>
              <div class="category-item">Drink</div>
              <div class="category-item">Food</div>
              <div class="category-item">Rejuv</div>
              <div class="category-item">Scents</div>
              <div class="category-item">Skincare</div>
              <div class="category-item">Soap</div>
              </div>
              
            </div>

<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Function to get product image (handles both BLOB and file path)
function getProductImageSrc($product) {
  if (!empty($product['product_image'])) {
    $imageData = $product['product_image'];
    
    // Debug: Log what we're working with
    error_log("Processing image for product ID: " . ($product['product_id'] ?? 'unknown'));
    error_log("Image data length: " . strlen($imageData));
    error_log("First 50 chars: " . substr($imageData, 0, 50));
    
    // If it's a filename, use uploads folder
    if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $imageData)) {
      error_log("Detected as filename: " . $imageData);
      
      // Try multiple path variations
      $possiblePaths = [
        '../uploads/' . $imageData,
        'uploads/' . $imageData,
        $imageData,
        '../' . $imageData
      ];
      
      foreach ($possiblePaths as $filePath) {
        if (file_exists($filePath)) {
          error_log("Found file at: " . $filePath);
          return $filePath;
        }
      }
      error_log("File not found in any location for: " . $imageData);
    }
    // If it's binary data (BLOB), show as base64
    else {
      error_log("Detected as binary data");
      
      try {
        // Check if it's actually image data by looking at headers first
        if (strlen($imageData) > 8) {
          $header = substr($imageData, 0, 8);
          
          // JPEG detection
          if (substr($imageData, 0, 2) === "\xFF\xD8") {
            error_log("Detected JPEG binary data");
            return "data:image/jpeg;base64," . base64_encode($imageData);
          }
          // PNG detection
          elseif ($header === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
            error_log("Detected PNG binary data");
            return "data:image/png;base64," . base64_encode($imageData);
          }
          // GIF detection
          elseif (substr($imageData, 0, 6) === "GIF87a" || substr($imageData, 0, 6) === "GIF89a") {
            error_log("Detected GIF binary data");
            return "data:image/gif;base64," . base64_encode($imageData);
          }
        }
        
        // Try using finfo as backup
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
          $mimeType = finfo_buffer($finfo, $imageData);
          finfo_close($finfo);
          if ($mimeType && strpos($mimeType, 'image/') === 0) {
            error_log("finfo detected MIME type: " . $mimeType);
            $base64 = base64_encode($imageData);
            return "data:$mimeType;base64,$base64";
          }
        }
      } catch (Exception $e) {
        error_log("Error processing binary data: " . $e->getMessage());
      }
    }
  }
  error_log("No valid image found");
  return '';
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($_POST['action'] === 'add_to_cart' && isset($_POST['id'])) {
    $id = $_POST['id'];
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    if (!in_array($id, $_SESSION['cart'])) {
      $_SESSION['cart'][] = $id;
    }
    exit;
  }
}

// Database connection
$conn = mysqli_connect('localhost', 'root', '', 'inventory_negrita');

// Debug: Check if uploads directory exists
$uploadsDir = '../uploads/';
if (!is_dir($uploadsDir)) {
  error_log("Uploads directory does not exist: " . realpath($uploadsDir));
} else {
  error_log("Uploads directory exists at: " . realpath($uploadsDir));
  $files = scandir($uploadsDir);
  error_log("Files in uploads directory: " . implode(', ', array_slice($files, 2))); // Skip . and ..
}
?>

                <!-- Products Grid -->
                <div class="row">
                  <?php
                  if (!$conn) {
                    echo '<div class="col-12">';
                    echo '<div class="alert alert-danger">Database connection error</div>';
                    echo '</div>';
                  } else {
                    $result = mysqli_query($conn, "SELECT product_id, product_name, category, description, price, quantity, product_image FROM products ORDER BY product_id DESC");
                    
                    if ($result && mysqli_num_rows($result) > 0) {
                      while ($row = mysqli_fetch_assoc($result)) {
                        $stock = (int)$row['quantity'];
                        
                        // Determine stock status and badge
                        $stockStatus = '';
                        $badgeClass = '';
                        
                        if ($stock <= 0) {
                          $stockStatus = 'Out of Stock';
                          $badgeClass = 'out';
                        } elseif ($stock <= 10) {
                          $stockStatus = 'Low Stock';
                          $badgeClass = 'low';
                        } else {
                          $stockStatus = 'In Stock';
                          $badgeClass = 'in';
                        }
                        
                        // Get image source using the robust function
                        $imgSrc = getProductImageSrc($row);
                        
                        // Debug output (remove this after testing)
                        if (empty($imgSrc) && !empty($row['product_image'])) {
                          echo "<!-- DEBUG: Product ID " . $row['product_id'] . 
                               " - Image field length: " . strlen($row['product_image']) . 
                               " - First 30 chars: " . htmlspecialchars(substr($row['product_image'], 0, 30)) . " -->";
                        }
                  ?>

                  <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 mb-3" data-category="<?= htmlspecialchars($row['category'] ?? '') ?>">
                    <div class="modern-product-card">
                      <!-- Stock Badge -->
                      <span class="badge-stock <?= $badgeClass ?>">
                        <?= $stockStatus ?>
                      </span>
                      
                      <!-- Product Image -->
                      <div class="product-image-container clickable-image" 
                           data-bs-toggle="modal" 
                           data-bs-target="#imageModal" 
                           data-image-src="<?= htmlspecialchars($imgSrc) ?>" 
                           data-product-name="<?= htmlspecialchars($row['product_name']) ?>">
                        <?php if ($imgSrc): ?>
                          <img src="<?= htmlspecialchars($imgSrc) ?>" class="product-img" alt="<?= htmlspecialchars($row['product_name']) ?>">
                        <?php else: ?>
                          <div class="img-placeholder">
                            <i class="bi bi-image"></i>
                            <small>No Image</small>
                            <?php if (!empty($row['product_image'])): ?>
                              <div style="font-size: 10px; color: #999; margin-top: 5px;">
                                Data: <?= strlen($row['product_image']) ?> bytes
                              </div>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>
                        <div class="image-overlay">
                        </div>
                      </div>

                     

                      <!-- Product Body -->
                      <div class="product-body">
                        <h5 class="product-title"><?= htmlspecialchars($row['product_name']) ?></h5>
                        <?php if (!empty($row['category'])): ?>
                        <div class="product-category mb-2">
                          <span class="badge bg-primary"><?= htmlspecialchars($row['category']) ?></span>
                        </div>
                        <?php endif; ?>
                        <p class="product-description">
                          <?= htmlspecialchars($row['description']) ?>
                        </p>
                        <div class="price-stock-container">
                          <span class="product-price">₱<?= number_format($row['price'], 2) ?></span>
                          <span class="product-stock">Stock: <?= $stock ?></span>
                        </div>
                        <div class="product-actions">
                          <button class="add-to-cart-btn" 
                                  data-id="<?= $row['product_id'] ?>" 
                                  data-name="<?= htmlspecialchars($row['product_name']) ?>" 
                                  data-price="<?= $row['price'] ?>" 
                                  data-stock="<?= $stock ?>"
                                  <?= $stock <= 0 ? 'disabled' : '' ?>>
                            <i class="bi bi-cart-plus"></i> 
                            <?= $stock <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <?php 
                      } // End while loop
                    } else {
                  ?>
                    <div class="col-12">
                      <div class="no-products-message">
                        <i class="bi bi-box"></i>
                        <h4>No Products Available</h4>
                        <p class="mb-0">Our product catalog is currently being updated. Please check back soon.</p>
                      </div>
                    </div>
                  <?php 
                    } // End if result check
                  } // End if connection check
                  ?>
                </div>
              </div>
            </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Floating Cart Widget -->
  <div class="floating-cart" id="floatingCart" style="display: flex !important;">
    <div class="cart-icon">
      <i class="bi bi-cart3"></i>
      <span class="cart-count">0</span>
    </div>
    <div class="cart-text">
      <small>View Cart</small>
    </div>
  </div>

  <!-- Image Modal for Product Images -->
  <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="imageModalLabel">Product Image</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <img id="modalImage" src="" alt="Product Image" class="img-fluid rounded" style="max-height: 70vh;">
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
  
  console.log('Products page - Distributor ID:', distributorId);
  console.log('Products page - Cart Key:', cartKey);
  
  // Helper function to save cart with distributor-specific key
  function saveCart() {
    localStorage.setItem(cartKey, JSON.stringify(cart));
    console.log('Cart saved from products page for distributor:', distributorId, cart);
  }
  
  // Clean up old cart data from localStorage (remove old generic keys)
  function cleanupOldCartData() {
    const keysToRemove = ['cart', 'distributorCart'];
    keysToRemove.forEach(key => {
      if (localStorage.getItem(key)) {
        localStorage.removeItem(key);
        console.log('Removed old cart key from products page:', key);
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
        showClass: {
          popup: 'animate__animated animate__fadeInUp animate__faster'
        },
        hideClass: {
          popup: 'animate__animated animate__fadeOutDown animate__faster'
        },
        timer: icon === 'success' ? 2000 : null,
        timerProgressBar: true
      });
    } else {
      alert(title + '\n' + text);
    }
  }

  // Update cart count display (if you have a cart counter element)
  function updateCartCount() {
    const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
      cartCountElement.textContent = cartCount;
      // Add animation
      cartCountElement.classList.add('updated');
      setTimeout(() => {
        cartCountElement.classList.remove('updated');
      }, 400);
    }
    
    // Always show floating cart, but change appearance based on contents
    const floatingCart = document.getElementById('floatingCart');
    if (floatingCart) {
      floatingCart.style.display = 'flex';
      if (cartCount === 0) {
        floatingCart.style.opacity = '0.7';
      } else {
        floatingCart.style.opacity = '1';
      }
    }
    
    // Store cart in localStorage for persistence
    saveCart();
  }

  // Show cart summary - now redirects to cart page
  function showCartSummary() {
    // Always redirect to cart page for better UX
    window.location.href = 'distri_cart.php';
  }

  // Clear cart function
  function clearCart() {
    cart = [];
    updateCartCount();
    showAlert('Cart Cleared', 'All items have been removed from your cart.', 'success');
  }

  // Add item to cart function
  function addToCart(productId, productName, productPrice, availableStock) {
    // Check if item already exists in cart
    const existingItemIndex = cart.findIndex(item => item.id === productId);
    
    if (existingItemIndex > -1) {
      // Check if we can add more of this item
      if (cart[existingItemIndex].quantity < availableStock) {
        cart[existingItemIndex].quantity += 1;
        showAlert('Updated Cart!', `${productName} quantity updated in your cart.`, 'success');
      } else {
        showAlert('Stock Limit', `Cannot add more ${productName}. Available stock: ${availableStock}`, 'warning');
        return false;
      }
    } else {
      // Add new item to cart
      cart.push({
        id: productId,
        name: productName,
        price: parseFloat(productPrice),
        quantity: 1,
        maxStock: availableStock
      });
      showAlert('Added to Cart!', `${productName} has been added to your cart.`, 'success');
    }
    
    updateCartCount();
    return true;
  }

  // Enhanced product interaction handlers
  document.addEventListener('DOMContentLoaded', function() {
    
    // Debug: Check if floating cart exists
    const floatingCart = document.getElementById('floatingCart');
    console.log('Floating cart element:', floatingCart);
    
    // Attach click event to floating cart
    if (floatingCart) {
      floatingCart.addEventListener('click', function() {
        showCartSummary();
      });
      console.log('Cart click event attached');
    } else {
      console.error('Floating cart not found!');
    }
    
    // Initialize cart count on page load
    updateCartCount();
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
      // Ctrl/Cmd + Shift + C to show cart
      if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'C') {
        e.preventDefault();
        showCartSummary();
      }
      // Escape to close modals
      if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
          const modalInstance = bootstrap.Modal.getInstance(openModal);
          if (modalInstance) modalInstance.hide();
        }
      }
    });
    
    // Add to Cart functionality
    document.querySelectorAll('.add-to-cart-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        // Skip if button is disabled
        if (btn.disabled) return;
        
        const id = btn.getAttribute('data-id');
        const name = btn.getAttribute('data-name');
        const price = btn.getAttribute('data-price');
        const stock = parseInt(btn.getAttribute('data-stock'));
        
        // Check stock availability
        if (stock <= 0) {
          showAlert('Out of Stock', name + ' is currently out of stock.', 'error');
          return;
        }
        
        // Show loading state
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="loading-spinner"></span> Adding...';
        btn.disabled = true;
        
        // Simulate API call delay for better UX
        setTimeout(() => {
          const success = addToCart(id, name, price, stock);
          
          // Restore button state
          btn.innerHTML = originalText;
          btn.disabled = stock <= 0;
          
          // Add visual feedback
          if (success) {
            btn.classList.add('btn-success-flash');
            setTimeout(() => {
              btn.classList.remove('btn-success-flash');
            }, 600);
          }
        }, 300);
      });
    });
    
    // Image click handlers for modal display
    document.querySelectorAll('.clickable-image').forEach(function(imageContainer) {
      imageContainer.addEventListener('click', function() {
        const imageSrc = this.getAttribute('data-image-src');
        const productName = this.getAttribute('data-product-name');
        
        // Only show modal if there's an actual image
        if (imageSrc && imageSrc.trim() !== '') {
          const modalImage = document.getElementById('modalImage');
          const modalTitle = document.getElementById('imageModalLabel');
          
          modalImage.src = imageSrc;
          modalTitle.textContent = productName;
        }
      });
      
      // Add click cursor style
      imageContainer.style.cursor = 'pointer';
    });
    
    // Category filtering with scroll enhancement
    document.querySelectorAll('.category-item').forEach(function(item) {
      item.addEventListener('click', function() {
        // Remove active class from all items
        document.querySelectorAll('.category-item').forEach(i => i.classList.remove('active'));
        // Add active class to clicked item
        this.classList.add('active');
        
        // Scroll category into view (center it in the category bar)
        const categoryBar = document.querySelector('.category-bar');
        if (categoryBar) {
          const containerRect = categoryBar.getBoundingClientRect();
          const itemRect = this.getBoundingClientRect();
          const scrollLeft = categoryBar.scrollLeft;
          
          // Calculate the center position
          const itemCenter = itemRect.left + itemRect.width / 2;
          const containerCenter = containerRect.left + containerRect.width / 2;
          const offset = itemCenter - containerCenter;
          
          // Smooth scroll to center the item
          categoryBar.scrollTo({
            left: scrollLeft + offset,
            behavior: 'smooth'
          });
        }
        
        const selectedCategory = this.textContent.trim();
        const productCards = document.querySelectorAll('[data-category]');
        
        // Show all products if "All" is selected
        if (selectedCategory === 'All') {
          productCards.forEach(card => {
            card.style.display = 'block';
          });
        } else {
          // Filter products by selected category
          productCards.forEach(card => {
            const productCategory = card.getAttribute('data-category');
            if (productCategory === selectedCategory) {
              card.style.display = 'block';
            } else {
              card.style.display = 'none';
            }
          });
        }
      });
    });
  });
  </script>
  
  <!-- Include responsive sidebar functionality -->
  <script src="../sidebar-drawer.js"></script>
  
  </body>
</html>