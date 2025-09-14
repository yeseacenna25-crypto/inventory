<?php
session_start();
$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $conn = mysqli_connect('localhost', 'root', '', 'inventory_negrita');
  if (!$conn) {
    die('Database connection error');
  }
  $username = mysqli_real_escape_string($conn, $_POST['distrib_username'] ?? '');
  $password = mysqli_real_escape_string($conn, $_POST['distrib_password'] ?? '');
  $captcha = $_POST['captcha'] ?? '';
  $captcha_code = $_SESSION['captcha_code'] ?? '';
  // Validate CAPTCHA
  if (strtolower($captcha) !== strtolower($captcha_code)) {
    $alert = 'Invalid CAPTCHA!';
  } else {
    // Check distributor credentials
    $sql = "SELECT * FROM distributor_signup WHERE distrib_username='$username' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
      // Check if password is hashed or plaintext
      if (password_verify($password, $row['distrib_password'])) {
        // Hashed password verification
        $_SESSION['distributor_id'] = $row['distributor_id'];
        $_SESSION['distrib_username'] = $row['distrib_username'];
        $alert = 'success';
      } elseif ($password === $row['distrib_password']) {
        // Fallback for plaintext passwords (legacy support)
        $_SESSION['distributor_id'] = $row['distributor_id'];
        $_SESSION['distrib_username'] = $row['distrib_username'];
        $alert = 'success';
      } else {
        $alert = 'Incorrect password!';
      }
    } else {
      $alert = 'Distributor not found!';
    }
  }
  // Regenerate CAPTCHA after each attempt
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
  <title>IMDISTRACK LOG IN</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"/>
  <link rel="stylesheet" type="text/css" href="../CSS/login.css?v=1.0" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
  <div class="container">
    <div class="loginHeader text-center">
      <img src="../ASSETS/imlogo.png" alt="Logo" class="loginLogo" />
    </div>

    <?php if ($alert && $alert !== 'success'): ?>
      <script>Swal.fire('Login Failed', '<?= htmlspecialchars($alert) ?>', 'error');</script>
    <?php endif; ?>
    <form class="form-group" method="POST">
      <div class="mb-3 bg p-5 rounded">
        <h2 class="text-center fw-semibold">WELCOME, DISTRIBUTOR!</h2>

        <label for="username" class="form-label mt-4 fw-semibold">Username</label>
        <input type="text" class="form-control text-center" id="distrib_username" name="distrib_username" placeholder="username" required />

        <label for="password" class="form-label mt-3 fw-semibold">Password</label>
        <div class="input-group">
          <input type="password" class="form-control text-center" id="distrib_password" name="distrib_password" placeholder="password" required />
          <span class="input-group-text toggle-password" onclick="togglePassword()">
            <i class="bi bi-eye" id="toggleIcon"></i>
          </span>
        </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">CAPTCHA</label>
            <div class="d-flex flex-column align-items-center mb-2" style="position: relative;">
              <div style="position: relative; display: inline-block;">
                <span id="captchaText" style="font-size: 1.3em; letter-spacing: 2px; background: #eee; padding: 6px 16px; border-radius: 6px; font-family: monospace; position: relative; z-index: 1;">
                  <?php
                    echo isset($_SESSION['captcha_code']) ? htmlspecialchars($_SESSION['captcha_code']) : '';
                  ?>
                </span>
                <svg width="120" height="32" style="position: absolute; left: 0; top: 0; z-index: 2; pointer-events: none;">
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
            </div>
            <input type="text" name="captcha" class="form-control" placeholder="Enter CAPTCHA" required>
          </div>

        <button type="submit" class="form-control btn-color mt-3">Login</button>

        <p class="text-center mt-3" style="font-size: 15px;">Don't have an account?</p>
        <p class="text-center" style="font-size: 15px;">
          Click here to <a href="distri_signup.php" class="signup-link">sign up</a>
        </p>
      </div>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Password toggle functionality
    function togglePassword() {
      const passwordInput = document.getElementById('distrib_password');
      const toggleIcon = document.getElementById('toggleIcon');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash';
      } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye';
      }
    }

    // Redirect to dashboard if login is successful
    <?php if (isset($alert) && $alert == "success"): ?>
      Swal.fire({
        icon: 'success',
        title: 'Login Successful!',
        text: 'Welcome back, <?= htmlspecialchars($_SESSION['distrib_username'] ?? 'Distributor') ?>!',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
      }).then(() => {
        window.location = "distri_dashboard.php";
      });
    <?php endif; ?>
  </script>
</body>
</html>
