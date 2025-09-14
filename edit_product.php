<?php
// edit_product.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "inventory_negrita");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if (!isset($_GET['id'])) {
    die('Product ID not specified.');
}

$product_id = intval($_GET['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['product_name'];
    $category = $_POST['category'];
    $desc = $_POST['description'];
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    // Optional: handle image update
    $stmt = $mysqli->prepare("UPDATE products SET product_name=?, category=?, description=?, price=?, quantity=? WHERE product_id=?");
    $stmt->bind_param('sssdii', $name, $category, $desc, $price, $quantity, $product_id);
    if ($stmt->execute()) {
        header("Location: trial_view.php?msg=updated");
        exit();
    } else {
        $error = 'Failed to update product.';
    }
}

$stmt = $mysqli->prepare("SELECT product_name, category, description, price, quantity FROM products WHERE product_id=?");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($name, $category, $desc, $price, $quantity);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DASHBOARD</title>
    <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css?v=<?= time(); ?>" />
        <link rel="stylesheet" type="text/css" href="CSS/view_products.css?v=<?= time(); ?>" />

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

             <div class="edit-product-container">

            <div class="card shadow-sm edit-product-card">

                            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                <h4>Edit Product Details</h4>
                                <div>
                                    <a href="trial_view.php" class="btn btn-secondary btn-sm">
                                        <i class="fa fa-arrow-left"></i> Back to Products
                                    </a>
                                </div>
                            </div>

                            <div class="card-body">
                             <?php if (isset($error)): ?>
                        <div class="error-message"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="form-group">
                            <label for="product_name">Product Name:</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Select a category</option>
                                <option value="Beauty" <?= ($category === 'Beauty') ? 'selected' : '' ?>>Beauty</option>
                                <option value="Capsule" <?= ($category === 'Capsule') ? 'selected' : '' ?>>Capsule</option>
                                <option value="Drink" <?= ($category === 'Drink') ? 'selected' : '' ?>>Drink</option>
                                <option value="Food" <?= ($category === 'Food') ? 'selected' : '' ?>>Food</option>
                                <option value="Rejuv" <?= ($category === 'Rejuv') ? 'selected' : '' ?>>Rejuv</option>
                                <option value="Scents" <?= ($category === 'Scents') ? 'selected' : '' ?>>Scents</option>
                                <option value="Skincare" <?= ($category === 'Skincare') ? 'selected' : '' ?>>Skincare</option>
                                <option value="Soap" <?= ($category === 'Soap') ? 'selected' : '' ?>>Soap</option>
                            </select>
                        </div>
                        
                        
                        
                        <div class="form-group">
                            <label for="price">Price:</label>
                            <input type="number" class="form-control" id="price" step="0.01" name="price" value="<?= $price ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity">Quantity:</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="<?= $quantity ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($desc) ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success">Update Product</button>
                    </form>
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
    </script>

   



      



    

    
    
  </body>
</html>
