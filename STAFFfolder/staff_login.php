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

  $username = trim($_POST['staff_username']);
  $password = $_POST['staff_password'];
  $captcha_input = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

  if (!isset($_SESSION['captcha_code']) || strtolower($captcha_input) !== strtolower($_SESSION['captcha_code'])) {
    $alert = "captcha_failed";
  } else {
    $stmt = $conn->prepare("SELECT staff_id, staff_password FROM staff_signup WHERE staff_username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
      $stmt->bind_result($id, $hashedPassword);
      $stmt->fetch();

      if (password_verify($password, $hashedPassword)) {
        $_SESSION["staff_id"] = $id;
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

    <form class="form-group" method="POST">
      <div class="mb-3 bg p-5 rounded">
        <h2 class="text-center fw-semibold">WELCOME, STAFF!</h2>

  <label for="staff_username" class="form-label mt-4 fw-semibold">Username</label>
  <input type="text" class="form-control text-center" id="staff_username" name="staff_username" placeholder="username" required />

  <label for="staff_password" class="form-label mt-3 fw-semibold">Password</label>
        <div class="input-group">
          <input type="password" class="form-control text-center" id="staff_password" name="staff_password" placeholder="password" required />
          <span class="input-group-text toggle-password" onclick="togglePassword()">
            <i class="bi bi-eye" id="toggleIcon"></i>
          </span>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">CAPTCHA</label>
          <div class="d-flex flex-column align-items-center mb-2" style="position: relative;">
            <div style="position: relative; display: inline-block;">
              <span id="captchaText" style="font-size: 1.3em; letter-spacing: 2px; background: #eee; padding: 6px 16px; border-radius: 6px; font-family: monospace; position: relative; z-index: 1;">
                <?php echo isset($_SESSION['captcha_code']) ? htmlspecialchars($_SESSION['captcha_code']) : ''; ?>
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
                  // Use lighter color for thin lines
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

        <div class="d-flex justify-content-center mt-3">
          <button type="button" class="btn btn-outline-dark" onclick="window.location.href='../index.php'">
            Return to Main Page
          </button>
        </div>

        <p class="text-center mt-3" style="font-size: 15px;">Don't have an account?</p>
        <p class="text-center" style="font-size: 15px;">
          Click here to <a href="staff_signup.php" class="signup-link">sign up</a>
        </p>
      </div>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePassword() {
  const passwordField = document.getElementById("staff_password");
      const toggleIcon = document.getElementById("toggleIcon");

      const isPassword = passwordField.type === "staff_password";
      passwordField.type = isPassword ? "text" : "staff_password";
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
    window.location = 'staff_dashboard.php';
  });
</script>
<?php endif; ?>

<?php if ($alert == "user_not_found"): ?>
<script>
  Swal.fire({
    icon: 'error',
    title: 'User Not Found',
    text: 'Please check your username and try again.',
    confirmButtonText: 'OK'
  });
</script>
<?php endif; ?>

<?php if ($alert == "incorrect_password"): ?>
<script>
  Swal.fire({
    icon: 'error',
    title: 'Incorrect Password',
    text: 'Please enter the correct password.',
    confirmButtonText: 'OK'
  });
</script>
<?php endif; ?>

<?php if ($alert == "captcha_failed"): ?>
<script>
  Swal.fire({
    icon: 'error',
    title: 'CAPTCHA Failed',
    text: 'Please check the CAPTCHA and try again.',
    confirmButtonText: 'OK'
  });
</script>
<?php endif; ?>
</body>
</html>
