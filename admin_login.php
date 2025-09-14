<?php
session_start();
$alert = '';

if (!isset($_SESSION['captcha_code']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    $_SESSION['captcha_code'] = $code;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $conn = new mysqli("localhost", "root", "", "inventory_negrita");

  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $username = trim($_POST['username']);
  $password = $_POST['password'];
  $captcha_input = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

  if (!isset($_SESSION['captcha_code']) || strtolower($captcha_input) !== strtolower($_SESSION['captcha_code'])) {
    $alert = "captcha_failed";
  } else {
    $stmt = $conn->prepare("SELECT admin_id, admin_password FROM admin_signup WHERE admin_username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
      $stmt->bind_result($id, $hashedPassword);
      $stmt->fetch();

      if (password_verify($password, $hashedPassword)) {
        $_SESSION["admin_id"] = $id;
        $alert = "success";
      } else {
        $alert = "incorrect_password";
      }
    } else {
      $alert = "user_not_found";
    }

    $stmt->close();
  }
  $conn->close();

  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
  $code = '';
  for ($i = 0; $i < 6; $i++) {
      $code .= $chars[rand(0, strlen($chars) - 1)];
  }
  $_SESSION['captcha_code'] = $code;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>IMDISTRACK Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="CSS/login.css?v=<?= time(); ?>" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
  

  <div class="main-container">
    <div class="login-wrapper">
     

      <!-- Login Form -->

      <div class="login-card">
        <div class="form-actions" style="padding-bottom: -50px; padding-top: -50px;">
            <button type="button" class="back-btn" onclick="window.location.href='index.php'">
              <i class="bi bi-arrow-left"></i> 
            </button>
          </div>
        <div class="login-header">
          <h2 class="login-title">Welcome Back, Admin!</h2>
          <p class="login-subtitle">Sign in to your admin dashboard</p>
        </div>

        <form class="login-form" method="POST">
          <div class="form-group">
            <label for="username" class="form-label">
              <i class="bi bi-person"></i> Username
            </label>
            <input type="text" class="form-input" id="username" name="username" placeholder="Enter your username" required />
          </div>

          <div class="form-group">
            <label for="password" class="form-label">
              <i class="bi bi-lock"></i> Password
            </label>
            <div class="password-wrapper">
              <input type="password" class="form-input" id="password" name="password" placeholder="Enter your password" required />
              <span class="password-toggle" onclick="togglePassword()">
                <i class="bi bi-eye" id="toggleIcon"></i>
              </span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">
              <i class="bi bi-shield-check"></i> Security Verification
            </label>
            <div class="captcha-container">
              <div class="captcha-display">
                <span id="captchaText">
                  <?php echo isset($_SESSION['captcha_code']) ? htmlspecialchars($_SESSION['captcha_code']) : ''; ?>
                </span>
                <svg width="120" height="32" class="captcha-overlay">
                  <?php for ($z = 0; $z < 4; $z++):
                    $y = rand(10, 22);
                    $amplitude = rand(5, 12);
                    $segments = rand(8, 14);
                    $segmentWidth = 120 / $segments;
                    $points = [];
                    for ($i = 0; $i < $segments; $i++) {
                      $x = $i * $segmentWidth;
                      $py = $y + (($i % 2 == 0) ? -$amplitude : $amplitude);
                      $points[] = "$x,$py";
                    }
                    $color = sprintf('#%02x%02x%02x', rand(180,220), rand(180,220), rand(180,220));
                  ?>
                    <polyline points="<?= implode(' ', $points) ?>" fill="none" stroke="<?= $color ?>" stroke-width="1" />
                  <?php endfor; ?>
                </svg>
              </div>
              <input type="text" name="captcha" class="form-input" placeholder="Enter CAPTCHA code" required>
            </div>
          </div>

          <button type="submit" class="login-btn">
            <i class="bi bi-box-arrow-in-right"></i> Sign In
          </button>

          

          <div class="signup-section">
            <p>Don't have an admin account?</p>
            <a href="admin_signup.php" class="signup-link">
              <i class="bi bi-person-plus"></i> Create Account
            </a>
          </div>
          
        </form>

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



      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePassword() {
      const passwordField = document.getElementById("password");
      const toggleIcon = document.getElementById("toggleIcon");

      const isPassword = passwordField.type === "password";
      passwordField.type = isPassword ? "text" : "password";
      toggleIcon.classList.toggle("bi-eye");
      toggleIcon.classList.toggle("bi-eye-slash");
    }
  </script>

  <?php if ($alert == "success"): ?>
    <script>
      Swal.fire({
        icon: 'success',
        title: 'Login Successful',
        showConfirmButton: false,
        timer: 1500
      }).then(() => {
        window.location = 'admin_dashboard.php';
      });
    </script>
  <?php elseif ($alert == "incorrect_password"): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Incorrect Password',
        text: 'Please try again.',
        confirmButtonColor: '#8B0000'
      });
    </script>
  <?php elseif ($alert == "user_not_found"): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Username Not Found',
        text: 'Check your input or register.',
        confirmButtonColor: '#8B0000'
      });
    </script>
  <?php elseif ($alert == "captcha_failed"): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'CAPTCHA Incorrect',
        text: 'Please try again.',
        confirmButtonColor: '#8B0000'
      });
    </script>
  <?php endif; ?>
</body>
</html>
