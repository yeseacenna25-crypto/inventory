<?php
if (!isset($_SESSION)) {
  session_start();
}
if (!isset($_SESSION['distributor_id'])) {
  header('Location: distri_login.php');
  exit();
}

$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$distributor_id = $_SESSION['distributor_id'];
$stmt = $conn->prepare("SELECT distrib_fname, distrib_mname, distrib_lname, distrib_profile_image FROM distributor_signup WHERE distributor_id = ?");
$stmt->bind_param("i", $distributor_id);
$stmt->execute();
$result = $stmt->get_result();
$distributor = $result->fetch_assoc();
$profileImage = (!empty($distributor['distrib_profile_image'])) ? "uploads/" . $distributor['distrib_profile_image'] : "";
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Distributor Profile</title>
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
      <?php if (isset($_GET['success'])): ?>
        <script>
          window.onload = function() {
            Swal.fire('Success', 'Profile updated successfully!', 'success');
          }
        </script>
      <?php endif; ?>
      <div class="container mt-5">
        <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                  <h4>Edit Distributor Profile</h4>
                </div>
                <div class="card-body">
                  <form action="d_update_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3 text-center">
                      <img src="<?= $profileImage ?>" alt="Profile" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 2px solid #410101;">
                      <div class="mt-2">
                        <label for="profile_image" class="form-label">Change Image</label>
                        <input type="file" class="form-control" name="profile_image" id="profile_image" accept="image/*">
                      </div>
                    </div>
                    <div class="mb-3">
                      <label>First Name</label>
                      <input type="text" name="distrib_fname" class="form-control" value="<?= htmlspecialchars($distributor['distrib_fname']) ?>" required>
                    </div>
                    <div class="mb-3">
                      <label>Middle Name</label>
                      <input type="text" name="distrib_mname" class="form-control" value="<?= htmlspecialchars($distributor['distrib_mname']) ?>">
                    </div>
                    <div class="mb-3">
                      <label>Last Name</label>
                      <input type="text" name="distrib_lname" class="form-control" value="<?= htmlspecialchars($distributor['distrib_lname']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="distri_dashboard.php" class="btn btn-secondary">Cancel</a>
                  </form>
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

                } else {
                  subMenu.style.display = 'block'; 
                  mainMenuIcon.classList.remove('fa-angle-left');
                  mainMenuIcon.classList.remove('fa-angle-down');
                      

                }
              }
            }
            
          

     });


    </script>

</body>
</html>
