/**
 * Responsive Sidebar functionality for NIMS
 * Handles both desktop collapse and mobile drawer behavior
 */

document.addEventListener('DOMContentLoaded', function() {
  const toggleBtn = document.getElementById('toggleBtn');
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const sidebar = document.getElementById('dashboard_sidebar');
  const overlay = document.querySelector('.sidebar-overlay');
  const closeBtn = document.querySelector('.sidebar-close-btn');
  const dashboardMainContainer = document.getElementById('dashboardMainContainer');
  
  // Check if we're on mobile
  function isMobile() {
    return window.innerWidth <= 768;
  }
  
  // Debug function
  function debugLog(message) {
    console.log('Sidebar Debug:', message);
  }
  
  debugLog('Sidebar script loaded');
  debugLog('Is mobile: ' + isMobile());
  debugLog('Mobile menu button found: ' + !!mobileMenuBtn);
  debugLog('Toggle button found: ' + !!toggleBtn);
  
  // Open sidebar (mobile)
  function openSidebar() {
    if (sidebar && isMobile()) {
      sidebar.classList.add('open');
      if (overlay) overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }
  
  // Close sidebar (mobile)
  function closeSidebar() {
    if (sidebar && isMobile()) {
      sidebar.classList.remove('open');
      if (overlay) overlay.classList.remove('active');
      document.body.style.overflow = '';
    }
  }
  
  // Toggle sidebar (desktop)
  function toggleSidebarDesktop() {
    if (!isMobile() && dashboardMainContainer) {
      dashboardMainContainer.classList.toggle('sidebar-collapsed');
      
      // Optional: adjust sidebar content for compact view
      const collapsed = dashboardMainContainer.classList.contains('sidebar-collapsed');
      const dashboard_logo = document.getElementById('dashboard_logo');
      const userImage = document.getElementById('userImage');
      const userName = document.getElementById('userName');
      const menuIcons = document.getElementsByClassName('menuText');
      const menuList = document.getElementsByClassName('dashboard_menu_list')[0];
      
      if (collapsed) {
        if (dashboard_logo) dashboard_logo.style.fontSize = '24px';
        if (userImage) {
          if (userImage.tagName === 'IMG') {
            userImage.style.width = '50px';
            userImage.style.height = '50px';
          } else if (userImage.classList.contains('generic-avatar')) {
            userImage.style.width = '50px';
            userImage.style.height = '50px';
            userImage.style.fontSize = '20px';
          }
        }
        if (userName) userName.style.fontSize = '12px';
        if (menuIcons) {
          for (let i = 0; i < menuIcons.length; i++) {
            menuIcons[i].style.display = 'none';
          }
        }
        if (menuList) menuList.style.textAlign = 'center';
      } else {
        if (dashboard_logo) dashboard_logo.style.fontSize = '40px';
        if (userImage) {
          if (userImage.tagName === 'IMG') {
            userImage.style.width = '90px';
            userImage.style.height = '90px';
          } else if (userImage.classList.contains('generic-avatar')) {
            userImage.style.width = '90px';
            userImage.style.height = '90px';
            userImage.style.fontSize = '36px';
          }
        }
        if (userName) userName.style.fontSize = '20px';
        if (menuIcons) {
          for (let i = 0; i < menuIcons.length; i++) {
            menuIcons[i].style.display = 'inline-block';
          }
        }
        if (menuList) menuList.style.textAlign = 'left';
      }
    }
  }
  
  // Toggle button event (desktop)
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function(e) {
      e.preventDefault();
      if (isMobile()) {
        openSidebar();
      } else {
        toggleSidebarDesktop();
      }
    });
  }
  
  // Mobile menu button event
  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', function(e) {
      e.preventDefault();
      openSidebar();
    });
  }
  
  // Close button event
  if (closeBtn) {
    closeBtn.addEventListener('click', function(e) {
      e.preventDefault();
      closeSidebar();
    });
  }
  
  // Overlay click event
  if (overlay) {
    overlay.addEventListener('click', function() {
      closeSidebar();
    });
  }
  
  // Close sidebar when clicking menu links (mobile)
  const menuLinks = document.querySelectorAll('.dashboard_sidebar_menu a');
  menuLinks.forEach(link => {
    if (!link.classList.contains('showHideSubMenu')) {
      link.addEventListener('click', function() {
        if (isMobile()) {
          closeSidebar();
        }
      });
    }
  });
  
  // ESC key to close sidebar
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
      closeSidebar();
    }
  });

  // Handle window resize
  window.addEventListener('resize', function() {
    if (!isMobile()) {
      // Reset mobile state when switching to desktop
      closeSidebar();
      document.body.style.overflow = '';
    }
  });
});

// Sub menu functionality with mobile considerations
document.addEventListener('click', function (e){
  let clickedElement = e.target;
  
  if (clickedElement.classList.contains('showHideSubMenu')) {
    e.preventDefault();
    
    let parentLi = clickedElement.closest('li');
    let subMenu = parentLi.querySelector('.subMenus');
    let mainMenuLink = parentLi.querySelector('a');

    // Close all other submenus
    let allSubMenus = document.querySelectorAll('.subMenus');
    
    allSubMenus.forEach((sub) => {
      if (subMenu !== sub) {
        sub.style.display = 'none';
        let otherMainLink = sub.closest('li').querySelector('a');
        if (otherMainLink) {
          otherMainLink.style.borderRadius = '15px';
        }
      }
    });

    if(subMenu != null) { 
      if (subMenu.style.display === 'block') {
        subMenu.style.display = 'none';
        if (mainMenuLink) mainMenuLink.style.borderRadius = '15px';
      } else {
        subMenu.style.display = 'block';
        if (mainMenuLink) mainMenuLink.style.borderRadius = '15px 15px 0 0';
      }
    }
  }
});
