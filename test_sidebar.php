<?php
session_start();
$_SESSION['admin_id'] = 1; // Set for testing
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Test Sidebar</title>
  <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css?v=<?= time(); ?>" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
  <style>
    /* Ensure sidebar positioning and functionality */
    #dashboardMainContainer {
      display: flex;
      min-height: 100vh;
    }
    
    .dashboard_sidebar {
      position: fixed;
      left: 0;
      top: 0;
      height: 100vh;
      z-index: 1000;
      overflow-y: auto;
    }
    
    .dashboard_content_container {
      margin-left: 20%;
      width: 80%;
      transition: all 0.3s ease;
    }
    
    .test-content {
      padding: 20px;
      background: white;
      margin: 20px;
      border-radius: 10px;
      min-height: 500px;
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
      <div class="test-content">
        <h2>🔧 Sidebar Test Page</h2>
        <p>This page is used to test sidebar functionality.</p>
        
        <div class="alert alert-info">
          <h4>Test Instructions:</h4>
          <ol>
            <li>Click the hamburger menu button (☰) in the top navigation</li>
            <li>The sidebar should collapse to show only icons</li>
            <li>Click it again to expand the sidebar</li>
            <li>Try clicking on menu items with submenus (like USERS, PRODUCTS, ORDERS)</li>
            <li>Submenus should expand/collapse properly</li>
          </ol>
        </div>
        
        <div class="alert alert-success">
          <h4>✅ Expected Behavior:</h4>
          <ul>
            <li>Sidebar width should change from 20% to 8% when collapsed</li>
            <li>Content area should adjust accordingly</li>
            <li>Menu text should hide when collapsed</li>
            <li>Logo size should adjust</li>
            <li>User image and name should resize</li>
          </ul>
        </div>
        
        <div id="debug-info" class="mt-4">
          <h4>Debug Information:</h4>
          <p>Sidebar Width: <span id="sidebar-width">-</span></p>
          <p>Content Width: <span id="content-width">-</span></p>
          <p>Sidebar State: <span id="sidebar-state">Open</span></p>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var sideBarIsOpen = true;
  var toggleBtn = document.getElementById('toggleBtn');
  var dashboard_sidebar = document.getElementById('dashboard_sidebar');
  var dashboard_content_container = document.getElementById('dashboard_content_container');
  var dashboard_logo = document.getElementById('dashboard_logo');
  var userImage = document.getElementById('userImage');
  var userName = document.getElementById('userName');

  // Debug function to update display
  function updateDebugInfo() {
    if (dashboard_sidebar && dashboard_content_container) {
      document.getElementById('sidebar-width').textContent = dashboard_sidebar.style.width || '20%';
      document.getElementById('content-width').textContent = dashboard_content_container.style.width || '80%';
      document.getElementById('sidebar-state').textContent = sideBarIsOpen ? 'Open' : 'Collapsed';
    }
  }

  // Initial debug info
  updateDebugInfo();

  if (toggleBtn && dashboard_sidebar && dashboard_content_container) {
      console.log('✅ Sidebar elements found - setting up toggle functionality');
      
      toggleBtn.addEventListener("click", (event) => {
        event.preventDefault();
        console.log('🔄 Toggle button clicked, current state:', sideBarIsOpen ? 'Open' : 'Collapsed');

        if (sideBarIsOpen) {
          dashboard_sidebar.style.width = '8%';
          dashboard_content_container.style.width = '92%';
          dashboard_content_container.style.marginLeft = '8%';
          if (dashboard_logo) dashboard_logo.style.fontSize = '30px';
          if (userImage) userImage.style.width = '50px';
          if (userName) userName.style.fontSize = '12px';

          let menuIcons = document.getElementsByClassName('menuText');
          for (let i = 0; i < menuIcons.length; i++) {
            menuIcons[i].style.display = 'none';
          }
          let menuList = document.getElementsByClassName('dashboard_menu_list')[0];
          if (menuList) menuList.style.textAlign = 'center';
          sideBarIsOpen = false;
          console.log('📦 Sidebar collapsed');
        } else {
          dashboard_sidebar.style.width = '20%';
          dashboard_content_container.style.width = '80%';
          dashboard_content_container.style.marginLeft = '20%';
          if (dashboard_logo) dashboard_logo.style.fontSize = '40px';
          if (userImage) userImage.style.width = '90px';
          if (userName) userName.style.fontSize = '20px';

          let menuIcons = document.getElementsByClassName('menuText');
          for (let i = 0; i < menuIcons.length; i++) {
            menuIcons[i].style.display = 'inline-block';
          }
          let menuList = document.getElementsByClassName('dashboard_menu_list')[0];
          if (menuList) menuList.style.textAlign = 'left';
          sideBarIsOpen = true;
          console.log('📖 Sidebar expanded');
        }
        
        updateDebugInfo();
      });
  } else {
    console.log('❌ Sidebar elements not found:');
    console.log('Toggle button:', toggleBtn);
    console.log('Sidebar:', dashboard_sidebar);
    console.log('Content container:', dashboard_content_container);
  }

  // Sub menu functionality
  document.addEventListener('click', function (e){
    let clickedElement = e.target;

    if (clickedElement.classList.contains('showHideSubMenu')) {
      let subMenu = clickedElement.closest('li').querySelector('.subMenus');
      let mainMenuIcon = clickedElement.closest('li').querySelector('.mainMenuIconArrow');

      let subMenus = document.querySelectorAll('.subMenus');
      subMenus.forEach((sub) => {
        if (subMenu !== sub) sub.style.display = 'none';
      });

      if(subMenu != null) {
        if (subMenu.style.display === 'block') {
          subMenu.style.display = 'none';
          if (mainMenuIcon) {
            mainMenuIcon.classList.remove('fa-angle-down');
            mainMenuIcon.classList.add('fa-angle-left');
          }
          console.log('📁 Submenu closed');
        } else {
          subMenu.style.display = 'block';
          if (mainMenuIcon) {
            mainMenuIcon.classList.remove('fa-angle-left');
            mainMenuIcon.classList.add('fa-angle-down');
          }
          console.log('📂 Submenu opened');
        }
      }
    }
  });
});
</script>

</body>
</html>
