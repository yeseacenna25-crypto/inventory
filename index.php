<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEGRITA Inventory Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="CSS/front.css?v=<?= time(); ?>">
</head>
<body>

    <div class="main-container">
        <div class="content-wrapper">
            
            
            <!-- Welcome Text -->
            <div class="welcome-text">
                <h1 class="welcome-title">Welcome to NEGRITA</h1>
                <p class="welcome-description">
                    Choose your role below to access the system.
                </p>
            </div>
            
            <!-- Role Selection Cards -->
            <div class="roles-container">
                <a href="admin_login.php" class="role-card">
                    <i class="bi bi-shield-check role-icon"></i>
                    <h3 class="role-title">ADMIN</h3>
                    <p class="role-description">
                        Full system access with user management, reports, and system configuration capabilities.
                    </p>
                </a>
                
                <a href="STAFFfolder/staff_login.php" class="role-card">
                    <i class="bi bi-people role-icon"></i>
                    <h3 class="role-title">STAFF</h3>
                    <p class="role-description">
                        Manage daily operations, process orders, and handle inventory transactions efficiently.
                    </p>
                </a>
                
                <a href="DISTRIBUTORfolder/distri_login.php" class="role-card">
                    <i class="bi bi-cart role-icon"></i>
                    <h3 class="role-title">DISTRIBUTOR</h3>
                    <p class="role-description">
                        Track orders and coordinate with the inventory system seamlessly.
                    </p>
                </a>
            </div>
        </div>
    </div>

    <!-- Animated background particles -->
    <div class="particles">
        <div class="particle" style="left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="left: 20%; animation-delay: 1s;"></div>
        <div class="particle" style="left: 30%; animation-delay: 2s;"></div>
        <div class="particle" style="left: 40%; animation-delay: 3s;"></div>
        <div class="particle" style="left: 50%; animation-delay: 4s;"></div>
        <div class="particle" style="left: 60%; animation-delay: 5s;"></div>
        <div class="particle" style="left: 70%; animation-delay: 0.5s;"></div>
        <div class="particle" style="left: 80%; animation-delay: 1.5s;"></div>
        <div class="particle" style="left: 90%; animation-delay: 2.5s;"></div>
        <div class="particle" style="left: 15%; top: 20%; animation-delay: 3.5s;"></div>
        <div class="particle" style="left: 25%; top: 40%; animation-delay: 4.5s;"></div>
        <div class="particle" style="left: 35%; top: 60%; animation-delay: 0.2s;"></div>
        <div class="particle" style="left: 45%; top: 80%; animation-delay: 1.2s;"></div>
        <div class="particle" style="left: 55%; top: 30%; animation-delay: 2.2s;"></div>
        <div class="particle" style="left: 65%; top: 50%; animation-delay: 3.2s;"></div>
        <div class="particle" style="left: 75%; top: 70%; animation-delay: 4.2s;"></div>
        <div class="particle" style="left: 85%; top: 10%; animation-delay: 5.2s;"></div>
        <div class="particle" style="left: 95%; top: 90%; animation-delay: 0.8s;"></div>
    </div>
    
    <!-- Modern Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-title">IM-DISTRACK Inventory Management and Distributor Purchase Tracking System</div>
            <div class="footer-subtitle">• by TFO Team</div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
