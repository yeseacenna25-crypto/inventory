<?php
$showSuccess = false;
$showError = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  session_start();
  if (strlen($_POST["staff_password"]) < 8) {
    $showError = "Password must be at least 8 characters!";
  } elseif ($_POST["staff_password"] !== $_POST["staff_confirm_password"]) {
    $showError = "Passwords do not match!";
  } else {
        $conn = new mysqli("localhost", "root", "", "inventory_negrita");

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

  // Check for duplicate username
  $check = $conn->prepare("SELECT staff_id FROM staff_signup WHERE staff_username = ?");
  $check->bind_param("s", $_POST["staff_username"]);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $showError = "Username already exists!";
        } else {
      $stmt = $conn->prepare("INSERT INTO staff_signup 
        (staff_fname, staff_mname, staff_lname, staff_extension, staff_gender, staff_birthday, staff_age, staff_civil_status, staff_address, staff_contact_number, staff_email, staff_outlet, staff_username, staff_password) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

      $hashed_password = password_hash($_POST["staff_password"], PASSWORD_DEFAULT);
      $stmt->bind_param("ssssssssssssss",
        $_POST["staff_fname"],
        $_POST["staff_mname"],
        $_POST["staff_lname"],
        $_POST["staff_extension"],
        $_POST["staff_gender"],
        $_POST["staff_birthday"],
        $_POST["staff_age"],
        $_POST["staff_civil_status"],
        $_POST["staff_address"],
        $_POST["staff_contact_number"],
        $_POST["staff_email"],
        $_POST["staff_outlet"],
        $_POST["staff_username"],
        $hashed_password
      );

      if ($stmt->execute()) {
  $showSuccess = true;
      } else {
        $showError = "Error: " . $stmt->error;
      }

      $stmt->close();
        }

        $check->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>STAFF REGISTRATION</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="../CSS/admin_signup.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <div class="container">
    <form class="form-group1" method="POST">
      <div class="mb-3 bg p-5 rounded">
        <a href="staff_login.php" class="btn btn-secondary mb-3 ms-0 back-btn">&larr;</a>
        <h3 class="text-center fw-bold pb-3">STAFF REGISTRATION</h3>

        <!-- Personal Information -->
        <div class="card mb-4 border">
          <div class="card-header bg-light">
            <h6 class="fw-bold mb-0">PERSONAL INFORMATION</h6>
          </div>
          <div class="card-body">
            <div class="row g-3 mb-3">
              <div class="col-md-3">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" name="staff_fname" required value="<?php echo isset($_POST['staff_fname']) ? htmlspecialchars($_POST['staff_fname']) : '' ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Middle Name</label>
                <input type="text" class="form-control" name="staff_mname" value="<?php echo isset($_POST['staff_mname']) ? htmlspecialchars($_POST['staff_mname']) : '' ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" name="staff_lname" required value="<?php echo isset($_POST['staff_lname']) ? htmlspecialchars($_POST['staff_lname']) : '' ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Extension</label>
                <select class="form-select" name="staff_extension">
                  <option value="">--</option>
                  <option value="Jr." <?php if(isset($_POST['staff_extension']) && $_POST['staff_extension']=='Jr.') echo 'selected'; ?>>Jr.</option>
                  <option value="Sr." <?php if(isset($_POST['staff_extension']) && $_POST['staff_extension']=='Sr.') echo 'selected'; ?>>Sr.</option>
                  <option value="II" <?php if(isset($_POST['staff_extension']) && $_POST['staff_extension']=='II') echo 'selected'; ?>>II</option>
                  <option value="III" <?php if(isset($_POST['staff_extension']) && $_POST['staff_extension']=='III') echo 'selected'; ?>>III</option>
                  <option value="IV" <?php if(isset($_POST['staff_extension']) && $_POST['staff_extension']=='IV') echo 'selected'; ?>>IV</option>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-3">
                <label class="form-label">Gender</label>
                <select class="form-select" name="staff_gender">
                  <option value="">--</option>
                  <option value="Male" <?php if(isset($_POST['staff_gender']) && $_POST['staff_gender']=='Male') echo 'selected'; ?>>Male</option>
                  <option value="Female" <?php if(isset($_POST['staff_gender']) && $_POST['staff_gender']=='Female') echo 'selected'; ?>>Female</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Birthdate</label>
                <input type="date" class="form-control" id="birthday" name="staff_birthday" required value="<?php echo isset($_POST['staff_birthday']) ? htmlspecialchars($_POST['staff_birthday']) : '' ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Age</label>
                <input type="text" class="form-control text-center" name="staff_age" maxlength="3" oninput="validateAge(this)" value="<?php echo isset($_POST['staff_age']) ? htmlspecialchars($_POST['staff_age']) : '' ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Civil Status</label>
                <select class="form-select" name="staff_civil_status">
                  <option value=""></option>
                  <option value="Single" <?php if(isset($_POST['staff_civil_status']) && $_POST['staff_civil_status']=='Single') echo 'selected'; ?>>Single</option>
                  <option value="Married" <?php if(isset($_POST['staff_civil_status']) && $_POST['staff_civil_status']=='Married') echo 'selected'; ?>>Married</option>
                  <option value="Widowed" <?php if(isset($_POST['staff_civil_status']) && $_POST['staff_civil_status']=='Widowed') echo 'selected'; ?>>Widowed</option>
                  <option value="Separated" <?php if(isset($_POST['staff_civil_status']) && $_POST['staff_civil_status']=='Separated') echo 'selected'; ?>>Separated</option>
                  <option value="Annulled" <?php if(isset($_POST['staff_civil_status']) && $_POST['staff_civil_status']=='Annulled') echo 'selected'; ?>>Annulled</option>
                </select>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Home Address</label>
                <input type="text" class="form-control" name="staff_address" required value="<?php echo isset($_POST['staff_address']) ? htmlspecialchars($_POST['staff_address']) : '' ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Contact Number</label>
                <div class="input-group">
                  <select class="form-select" name="country_code" style="max-width: 100px;">
                    <?php
                    $country_codes = [
                      ['country' => 'Philippines', 'code' => '+63'],
                      ['country' => 'United States', 'code' => '+1'],
                      ['country' => 'United Kingdom', 'code' => '+44'],
                      ['country' => 'India', 'code' => '+91'],
                      ['country' => 'Canada', 'code' => '+1'],
                      ['country' => 'Australia', 'code' => '+61'],
                    ];
                    foreach ($country_codes as $code) {
                      $selected = (isset($_POST['country_code']) && $_POST['country_code'] == $code['code']) ? 'selected' : ($code['code'] == '+63' ? 'selected' : '');
                      echo "<option value='" . htmlspecialchars($code['code']) . "' $selected>" . htmlspecialchars($code['code']) . "</option>";
                    }
                    ?>
                  </select>
                  <input type="text" class="form-control text-center" name="staff_contact_number" maxlength="11" oninput="validateContactNumber(this)" value="<?php echo isset($_POST['staff_contact_number']) ? htmlspecialchars($_POST['staff_contact_number']) : '' ?>">
                </div>
              </div>
              <div class="col-md-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="staff_email" required value="<?php echo isset($_POST['staff_email']) ? htmlspecialchars($_POST['staff_email']) : '' ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Outlet</label>
                <select class="form-select" name="staff_outlet" id="staff_outlet">
                  <option value="">--</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Account Information -->
        <div class="card border">
          <div class="card-header bg-light">
            <h6 class="fw-bold mb-0">ACCOUNT INFORMATION</h6>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="staff_username" required value="<?php echo isset($_POST['staff_username']) ? htmlspecialchars($_POST['staff_username']) : '' ?>">
              </div>
              <div class="col">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="staff_password" required>
              </div>
              <div class="col">
                <label class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="staff_confirm_password" required>
              </div>
            </div>
            <div class="mb-3">
               <!-- CAPTCHA removed -->
            </div>
            <button type="submit" class="form-control btn btn-dark mt-3 fw-semibold">SIGN UP</button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const today = new Date().toISOString().split("T")[0];
      document.getElementById("birthday").setAttribute("max", today);

      // Improved fetch for Philippine places with error handling and fallback
      function populateOutletDropdown(data) {
        const outletSelect = document.getElementById('staff_outlet');
        outletSelect.innerHTML = '<option value="">--</option>';
        if (!Array.isArray(data) || data.length === 0) {
          const errorOption = document.createElement('option');
          errorOption.value = '';
          errorOption.textContent = 'No places found';
          outletSelect.appendChild(errorOption);
          return;
        }
        data.forEach(region => {
          if (!region.provinces) return;
          region.provinces.forEach(province => {
            if (!province.cities) return;
            province.cities.forEach(city => {
              const option = document.createElement('option');
              option.value = city;
              option.textContent = `${region.region} - ${province.province} - ${city}`;
              if ("<?php echo isset($_POST['staff_outlet']) ? $_POST['staff_outlet'] : '' ?>" === city) {
                option.selected = true;
              }
              outletSelect.appendChild(option);
            });
          });
        });
      }

      function showFetchError() {
        const outletSelect = document.getElementById('staff_outlet');
        outletSelect.innerHTML = '<option value="">--</option>';
        const errorOption = document.createElement('option');
        errorOption.value = '';
        errorOption.textContent = 'Error loading places';
        outletSelect.appendChild(errorOption);
        const errorDiv = document.createElement('div');
        errorDiv.style.color = 'red';
        errorDiv.textContent = 'Failed to load Philippine places. See console for details.';
        outletSelect.parentNode.appendChild(errorDiv);
      }

      function tryFetch(urls, idx = 0) {
        if (idx >= urls.length) {
          showFetchError();
          return;
        }
        fetch(urls[idx])
          .then(response => {
            if (!response.ok) {
              throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
          })
          .then(data => {
            populateOutletDropdown(data);
          })
          .catch(error => {
            console.error('Error fetching Philippine places from', urls[idx], error);
            tryFetch(urls, idx + 1);
          });
      }

      // Try both relative and absolute paths
      tryFetch(['../philippine_places_api.php', '../../philippine_places_api.php', '/philippine_places_api.php']);
    });

    function validateContactNumber(input) {
      input.value = input.value.replace(/\D/g, '');
      if (input.value.length > 11) {
        input.value = input.value.slice(0, 11);
        Swal.fire('Contact Number must be 11 digits only.');
      }
    }

    function validateAge(input) {
      input.value = input.value.replace(/\D/g, '');
      if (input.value.length > 3) {
        input.value = input.value.slice(0, 3);
        Swal.fire('Age must be a number up to 3 digits only.');
      }
    }
  </script>

  <?php if ($showSuccess): ?>
  <script>
    Swal.fire({
      icon: 'success',
      title: 'Registration Successful',
      text: 'Redirecting to login...',
      confirmButtonColor: '#8B0000'
    }).then(() => {
      window.location = 'staff_login.php';
    });
  </script>
  <?php endif; ?>

  <?php if ($showError): ?>
  <script>
    Swal.fire({
      icon: 'error',
      title: 'Oops!',
      text: '<?= $showError ?>',
      confirmButtonColor: '#8B0000'
    });
  </script>
  <?php endif; ?>
</body>
</html>
