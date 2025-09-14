<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['staff_id'])) {
  header("Location: admin_login.php");
  exit();
}

$mysqli = new mysqli("localhost", "root", "", "inventory_negrita");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Enable error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Fetch full product details with error handling
try {
    // Use the actual column names from the database including category
  $result = $mysqli->query("SELECT product_id, product_name, category, description, price, quantity, product_image FROM products ORDER BY product_id DESC");
} catch (mysqli_sql_exception $e) {
    // If that fails, show what columns actually exist
    $columns_result = $mysqli->query("DESCRIBE products");
    $available_columns = [];
    while ($col = $columns_result->fetch_assoc()) {
        $available_columns[] = $col['Field'];
    }
    die("Database column error. Available columns: " . implode(', ', $available_columns) . 
        "<br>Error: " . $e->getMessage());
}

$products = []; 
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Map quantity to stock for consistency with the frontend
        $row['stock'] = $row['quantity'];
        
        // Debug: Check what's actually in the product_image field
        if (!empty($row['product_image'])) {
          // Check if it's binary data or filename
          $isFileName = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $row['product_image']);
          $dataLength = strlen($row['product_image']);
          
          // Add debug info (remove this after testing)
          error_log("Product ID: " . $row['product_id'] . 
                   " | Image field length: " . $dataLength . 
                   " | Is filename: " . ($isFileName ? 'Yes' : 'No') . 
                   " | First 20 chars: " . substr($row['product_image'], 0, 20));
        }
        
        $products[] = $row;
    }
}

// Function to get MIME type from image blob

function getImageMime($imageData) {
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mimeType = finfo_buffer($finfo, $imageData);
  finfo_close($finfo);
  return $mimeType;
}

// Function to get product image (handles both BLOB and file path)

// Function to get product image (handles both BLOB and file path)
function getProductImageSrc($product) {
  if (!empty($product['product_image'])) {
    // If it's a filename, use uploads folder
    if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $product['product_image'])) {
      $filePath = 'uploads/' . $product['product_image'];
      if (file_exists($filePath)) {
        return $filePath;
      }
      // Also check if file exists without 'uploads/' prefix (in case it's already included)
      if (file_exists($product['product_image'])) {
        return $product['product_image'];
      }
    }
    // If it's binary data (BLOB), show as base64
    else {
      try {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
          $mimeType = finfo_buffer($finfo, $product['product_image']);
          finfo_close($finfo);
          if ($mimeType && strpos($mimeType, 'image/') === 0) {
            $base64 = base64_encode($product['product_image']);
            return "data:$mimeType;base64,$base64";
          }
        }
      } catch (Exception $e) {
        // If finfo fails, try to detect if it's image data by checking for common image headers
        $imageData = $product['product_image'];
        if (strlen($imageData) > 4) {
          $header = substr($imageData, 0, 4);
          if ($header === "\xFF\xD8\xFF\xE0" || $header === "\xFF\xD8\xFF\xE1") {
            // JPEG
            return "data:image/jpeg;base64," . base64_encode($imageData);
          } elseif (substr($imageData, 0, 8) === "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
            // PNG
            return "data:image/png;base64," . base64_encode($imageData);
          }
        }
      }
    }
  }
  return '';
}

if (isset($_SESSION['product_image'])) {
  $product['product_image'] = $_SESSION['product_image'];
  $imgSrc = getProductImageSrc($product);
  echo '<img src="' . htmlspecialchars($imgSrc) . '" alt="Product Image" style="max-width: 200px; max-height: 200px;">';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Products</title>
  <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css?v=<?= time(); ?>" />
  <link rel="stylesheet" type="text/css" href="CSS/add_product.css?v=<?= time(); ?>" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
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

 
        <div class="products-container">
          <div class="container-fluid">
            
            <!-- Modern Header -->

            <div class="products-header">
                  <h2>
                    <i class="bi bi-box-seam me-3"></i>
                    PRODUCT CATALOG
                  </h2>
                  <p class="mb-0 mt-2 opacity-90">Manage your product catalog with ease</p>
                </div>



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
            
            <div class="row">
              <?php if (empty($products)): ?>
                <div class="col-12">
                  <div class="no-products-message">
                    <i class="bi bi-box"></i>
                    <h4>No Products Available</h4>
                    <p class="mb-0">Start building your inventory by adding your first product.</p>
                  </div>
                </div>

                
              <?php else: ?>
                <?php foreach ($products as $product): ?>
                  <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6 mb-3" data-category="<?= htmlspecialchars($product['category'] ?? '') ?>">
                    <div class="modern-product-card">
                      <?php 
                        // Determine stock status and badge
                        $stockStatus = '';
                        $badgeClass = '';
                        
                        if ($product['stock'] <= 0) {
                          $stockStatus = 'Out of Stock';
                          $badgeClass = 'out-of-stock';
                        } elseif ($product['stock'] <= 10) {
                          $stockStatus = 'Low Stock';
                          $badgeClass = 'low-stock';
                        } else {
                          $stockStatus = 'In Stock';
                          $badgeClass = 'in-stock';
                        }
                      ?>
                      
                      <!-- Product Image -->
                      <div class="product-image-container clickable-image" 
                           data-bs-toggle="modal" 
                           data-bs-target="#imageModal" 
                           data-image-src="<?= htmlspecialchars(getProductImageSrc($product)) ?>" 
                           data-product-name="<?= htmlspecialchars($product['product_name']) ?>">
                        <!-- Stock Badge -->
                        <span class="stock-badge <?= $badgeClass ?>">
                          <?= $stockStatus ?>
                        </span>
                        
                        <?php 
                          $imgSrc = getProductImageSrc($product);
                          if ($imgSrc): ?>
                            <img src="<?= htmlspecialchars($imgSrc) ?>" class="product-img" alt="<?= htmlspecialchars($product['product_name']) ?>">
                          <?php else: ?>
                            <div class="img-placeholder">
                              <i class="bi bi-image"></i>
                              <small>No Image</small>
                            </div>
                          <?php endif; ?>
                        
                        <div class="image-overlay">
                        </div>
                      </div>
                      
                      <!-- Product Body -->
                      <div class="product-body">
                        <h5 class="product-title"><?= htmlspecialchars($product['product_name']) ?></h5>
                        <?php if (!empty($product['category'])): ?>
                        <div class="product-category mb-2">
                          <span class="badge bg-primary"><?= htmlspecialchars($product['category']) ?></span>
                        </div>
                        <?php endif; ?>
                        <p class="product-description">
                          <?= htmlspecialchars($product['description']) ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <span class="product-price">₱<?= number_format($product['price'], 2) ?></span>
                          <span class="product-stock">Stock: <?= $product['stock'] ?></span>
                        </div>
                        <div class="product-actions">
                          <a href="edit_product.php?id=<?= $product['product_id'] ?>" class="modern-edit-btn">
                            <i class="bi bi-pencil-square"></i> Edit
                          </a>
                          <button class="modern-delete-btn delete-product-btn" data-id="<?= $product['product_id'] ?>">
                            <i class="bi bi-trash3"></i> Delete
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Product Image</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="modalImage" src="" alt="Product Image" class="img-fluid rounded" style="max-height: 70vh;">
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- SweetAlert2 for delete confirmation -->
<script>
document.querySelectorAll('.delete-product-btn').forEach(function(btn) {
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    const productId = this.getAttribute('data-id');
    Swal.fire({
      title: 'Delete Product?',
      text: 'Are you sure you want to delete this product? This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = 'delete_product.php?id=' + productId;
      }
    });
  });
});

// Image modal functionality
document.querySelectorAll('.clickable-image').forEach(function(imageContainer) {
  imageContainer.addEventListener('click', function() {
    const imageSrc = this.getAttribute('data-image-src');
    const productName = this.getAttribute('data-product-name');
    
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('imageModalLabel').textContent = productName;
  });
});

// Category filtering functionality
document.querySelectorAll('.category-item').forEach(function(categoryItem) {
  categoryItem.addEventListener('click', function() {
    // Remove active class from all category items
    document.querySelectorAll('.category-item').forEach(item => {
      item.classList.remove('active');
    });
    
    // Add active class to clicked category
    this.classList.add('active');
    
    const selectedCategory = this.textContent.trim();
    const productCards = document.querySelectorAll('[data-category]');
    
    // Show all products if "All" is selected (you can add an "All" category)
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
</script>

<!-- Include responsive sidebar functionality -->
<script src="sidebar-drawer.js"></script>

</body>
</html>
