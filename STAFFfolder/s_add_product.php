<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Product</title>
  <link rel="stylesheet" type="text/css" href="../CSS/add_product.css?v=<?= time(); ?>" />
  <link rel="stylesheet" type="text/css" href="../CSS/admin_dashboard.css?v=<?= time(); ?>" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
</head>
<body>

<div id="dashboardMainContainer">
     
  <!-- SIDEBAR -->
        <?php include('STAFFpartials/s_sidebar.php') ?>
        <!-- SIDEBAR -->

        <div class="dashboard_content_container" id="dashboard_content_container">

          <!-- TOP NAVBAR -->
          <?php include('STAFFpartials/s_topnav.php') ?>
          <!-- TOP NAVBAR -->

        <div class="modern-container">
          <div class="container-fluid">

          
            
            <!-- Page Header -->
            <h2 class="page-title">
              <i class="bi bi-plus-circle-fill me-3"></i>
              ADD NEW PRODUCT
            </h2>
        
              </div>
              
              <!-- Display errors if any -->
              <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mx-4 mt-4">
                  <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                      <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <!-- Display success message -->
              <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="alert alert-success mx-4 mt-4">
                  Product added successfully!
                </div>
              <?php endif; ?>

              <form action="" method="post" enctype="multipart/form-data" class="appForm">
                <!-- Product Information -->
                <div class="">
                  <div class="section-header" style="margin-bottom: -10px;">
                    <h6>
                      <i class="bi bi-box-seam"></i>
                      PRODUCT INFORMATION
                    </h6>
                   <p class="mb-0 mt-0 opacity-75" style="margin-left: 27px;">  Fill in the information below to add a new product to inventory</p>
                  </div>

                  <div class="form-section">
                    <div class="row g-4 mb-3">
                      <div class="col-md-6">
                        <label for="name" class="modern-label">
                          <i class="bi bi-tag"></i>
                          Product Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control modern-form-control" id="name" name="name" required maxlength="50" placeholder="Enter product name">
                      </div>
                      <div class="col-md-6">
                        <label for="price" class="modern-label">
                          <i class="bi bi-currency-exchange"></i>
                          Product Price (₱) <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control modern-form-control" id="price" name="price" required maxlength="10" placeholder="Enter product price" min="0" max="9999999999">
                      </div>
                    </div>

                    <div class="row g-4 mb-3 justify-content-center">
                      <div class="col-md-6">
                      <label for="stock" class="modern-label"></label>
                        <i class="bi bi-boxes"></i>
                        Total Stock <span class="text-danger">*</span>
                      </label>
                      <input type="number" class="form-control modern-form-control" id="stock" name="stock" required maxlength="10" placeholder="Total products available" min="0" max="9999999999">
                      </div>

                      <div class="col-md-6">
                      <label for="category" class="modern-label">
                        <i class="bi bi-box"></i>
                        Category <span class="text-danger">*</span>
                      </label>
                      <select class="form-select modern-form-control" id="category" name="category" required>
                        <option value="">Select a category</option>
                        <option value="Beauty">Beauty</option>
                        <option value="Capsule">Capsule</option>
                        <option value="Drink">Drink</option>
                        <option value="Food">Food</option>
                        <option value="Rejuv">Rejuv</option>
                        <option value="Scents">Scents</option>
                        <option value="Skincare">Skincare</option>
                        <option value="Soap">Soap</option>
                      </select>
                      </div>
                    </div>

                    <div class="row g-4 mb-3 justify-content-center">
                      <div class="col-md-6">
                      <label for="image" class="modern-label">
                        <i class="bi bi-image"></i>
                        Product Image <span class="text-danger">*</span>
                      </label>
                      <input type="file" class="form-control modern-form-control" id="image" name="image" required accept="image/*">
                      </div>
                    </div>

                    <div class="row g-4">
                      <div class="col-md-12">
                        <label for="description" class="modern-label">
                          <i class="bi bi-card-text"></i>
                          Product Description <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control modern-form-control" id="description" name="description" required maxlength="500" placeholder="Enter product description" rows="4"></textarea>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center mt-4">
                  <button type="submit" class="btn modern-btn" name="add_product">
                    <i class="bi bi-plus-circle"></i>
                    Add Product
                  </button>
                  <button type="reset" class="btn modern-btn-secondary ms-3">
                    <i class="bi bi-arrow-clockwise"></i>
                    Clear Form
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Include responsive sidebar functionality -->
<script src="../sidebar-drawer.js"></script>

<!-- JS FOR ADD -->
<script>
  document.querySelectorAll('input[type="number"]').forEach(inputNumber => {
    inputNumber.oninput = () =>{
        if(inputNumber.value.length > inputNumber.maxLength) inputNumber.value = inputNumber.value.slice(0, inputNumber.maxLength);
    };
  });
</script>

</body>
</html>