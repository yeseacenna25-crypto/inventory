<script>
  history.pushState(null, null, location.href);
  window.onpopstate = function () {
    history.go(1);
  };
</script>
<?php
// ===== Enable MySQLi error reporting =====
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ====== PHP BACKEND LOGIC ======
$mysqli = new mysqli('localhost', 'root', '', 'inventory_negrita');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $image = $_FILES['product_image'] ?? null;

    if (!$name) $errors[] = "Product name is required.";
    if (!$description) $errors[] = "Description is required.";
    if (!$price || $price <= 0) $errors[] = "Valid price is required.";

    if (!$image || $image['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Image upload failed.";
    } else {
        $validExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($image['product_image'], PATHINFO_EXTENSION));
        if (!in_array($ext, $validExtensions)) {
            $errors[] = "Only JPG, JPEG, PNG, or GIF files allowed.";
        }
        if ($image['size'] > 2 * 1024 * 1024) {
            $errors[] = "Image size must be under 2MB.";
        }
    }

    if (!$errors) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = time() . "_" . uniqid() . "." . $ext;
        $destination = $uploadDir . $filename;

    if (move_uploaded_file($image['tmp_name'], $destination)) {
      $stmt = $mysqli->prepare("INSERT INTO add_products (product_name, description, price, product_image) VALUES (?, ?, ?, ?)");
      $stmt->bind_param('ssds', $product_name, $description, $product_price, $filename);
      $stmt->execute();
      header("Location: add_product.php?success=1");
      exit;
    } else {
      $errors[] = "Failed to move uploaded file.";
    }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Product</title>
  <link rel="stylesheet" type="text/css" href="CSS/add_product.css?v=1.0" />
  <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css?v=<?= time(); ?>" />
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

    <div class="dashboard_content">
      <div class="dashboard_content_main">
      
        
          <form method="POST" enctype="multipart/form-data">
            <h2>Add New Product</h2>

            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
              <div id="successMessage" class="alert alert-success w-100">Product added successfully!</div>
            <?php elseif ($errors): ?>
              <div class="alert alert-danger">
                <ul>
                  <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <div class="form-group">
              <label>Product Name</label>
              <input type="text" name="name" required>
            </div>

            <div class="form-group">
              <label>Description</label>
              <input type="text" name="description" required>
            </div>

            <div class="form-group">
              <label>Price (₱)</label>
              <input type="number" name="price" step="0.01" required>
            </div>

             <div class="form-group">
              <label>Category</label>
              <select name="category_id" required class="form-select">
              <option value>---</option>
              <option value="">Self Care</option>
              <option value="">Cosmetics</option>
              <option value="">Drinks/Supplements</option>
              <option value="">Perfumes</option>


              </select>
              </div>


            <div class="form-group">
              <label>Product Image</label>
              <input type="file" name="image" id="imageInput" accept="image/*" required>
              <img id="imgPreview" alt="Image Preview">
            </div>

            <button type="submit">Save Product</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
</div>


<script>
  const imgInput = document.getElementById('imageInput');
  const imgPreview = document.getElementById('imgPreview');

  imgInput.addEventListener('change', () => {
    const [file] = imgInput.files;
    if (file) {
      const reader = new FileReader();
      reader.onload = e => {
        imgPreview.src = e.target.result;
        imgPreview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    } else {
      imgPreview.style.display = 'none';
    }
  });

  const successMsg = document.getElementById('successMessage');
  if (successMsg) {
    setTimeout(() => {
      successMsg.style.display = 'none';
    }, 3000); // Hide after 3s
  }


</script>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
        <script>
      var sideBarIsOpen = true;
      toggleBtn.addEventListener("click", (event) => {
        event.preventDefault();

        if (sideBarIsOpen) {
          dashboard_sidebar.style.width = '8%';
          dashboard_content_container.style.width = '92%';
          dashboard_logo.style.fontSize = '30px';
          userImage.style.width = '70px';
          userName.style.fontSize = '15px';

          let menuIcons = document.getElementsByClassName('menuText');
          for (let i = 0; i < menuIcons.length; i++) {
            menuIcons[i].style.display = 'none';
          }
          document.getElementsByClassName('dashboard_menu_list')[0].style.textAlign = 'center';
          sideBarIsOpen = false;
        } else {
          dashboard_sidebar.style.width = '20%';
          dashboard_content_container.style.width = '80%';
          dashboard_logo.style.fontSize = '50px';
          userImage.style.width = '70px';
          userName.style.fontSize = '15px';

          let menuIcons = document.getElementsByClassName('menuText');
          for (let i = 0; i < menuIcons.length; i++) {
            menuIcons[i].style.display = 'inline-block';
          }
          document.getElementsByClassName('dashboard_menu_list')[0].style.textAlign = 'left';
          sideBarIsOpen = true;
        }
      });

      //sub menu


      document.addEventListener('click', function (e){
        let clickedElement = e.target;
        
        if (clickedElement.classList.contains('showHideSubMenu')) {
          let subMenu = clickedElement.closest('li').querySelector('.subMenus');
          let mainMenuIcon = clickedElement.closest('li').querySelector('.mainMenuIconArrow');



                let subMenus = document.querySelectorAll('.subMenus');
                subMenus.forEach((sub) => {
                  if (subMenu !== sub)  sub.style.display = 'none';
                
                });


      

          if(subMenu != null) { 
                if (subMenu.style.display === 'block') {
                  subMenu.style.display = 'none';
                  mainMenuIcon.classList.remove('fa-angle-down');
                  mainMenuIcon.classList.remove('fa-angle-left');

                } else 
                subMenu.style.display = 'block';
                  mainMenuIcon.classList.remove('fa-angle-left');
                  mainMenuIcon.classList.remove('fa-angle-down');
                      

                }
              
            }

      });

      
    </script>

    
  </body>
</html>
