<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $conn = mysqli_connect('localhost', 'root', '', 'inventory_negrita');
  if (!$conn) {
    die('Database connection error');
  }
  $distrib_fname = mysqli_real_escape_string($conn, $_POST['distrib_fname'] ?? '');
  $distrib_mname = mysqli_real_escape_string($conn, $_POST['distrib_mname'] ?? '');
  $distrib_lname = mysqli_real_escape_string($conn, $_POST['distrib_lname'] ?? '');
  $distrib_extension = mysqli_real_escape_string($conn, $_POST['distrib_extension'] ?? '');
  $distrib_gender = mysqli_real_escape_string($conn, $_POST['distrib_gender'] ?? '');
  $distrib_birthday = mysqli_real_escape_string($conn, $_POST['distrib_birthday'] ?? '');
  $distrib_age = mysqli_real_escape_string($conn, $_POST['distrib_age'] ?? '');
  $distrib_civil_status = mysqli_real_escape_string($conn, $_POST['distrib_civil_status'] ?? '');
  $distrib_address = mysqli_real_escape_string($conn, $_POST['distrib_address'] ?? '');
  $distrib_contact_number = mysqli_real_escape_string($conn, $_POST['distrib_contact_number'] ?? '');
  $distrib_email = mysqli_real_escape_string($conn, $_POST['distrib_email'] ?? '');
  $distrib_outlet = mysqli_real_escape_string($conn, $_POST['outlet'] ?? '');
  $distrib_username = mysqli_real_escape_string($conn, $_POST['distrib_username'] ?? '');
  $distrib_password = mysqli_real_escape_string($conn, $_POST['distrib_password'] ?? '');
  $distrib_confirm_password = mysqli_real_escape_string($conn, $_POST['distrib_confirm_password'] ?? '');
  if ($distrib_password !== $distrib_confirm_password) {
    echo "<script>Swal.fire('Error', 'Passwords do not match!', 'error');</script>";
  } else {
    $sql = "INSERT INTO distributor_signup (
      distrib_fname, distrib_mname, distrib_lname, distrib_extension, distrib_gender, distrib_birthday, distrib_age, distrib_civil_status, distrib_address, distrib_contact_number, distrib_email, distrib_outlet, distrib_username, distrib_password
    ) VALUES (
      '$distrib_fname', '$distrib_mname', '$distrib_lname', '$distrib_extension', '$distrib_gender', '$distrib_birthday', '$distrib_age', '$distrib_civil_status', '$distrib_address', '$distrib_contact_number', '$distrib_email', '$distrib_outlet', '$distrib_username', '$distrib_password'
    )";
    if (mysqli_query($conn, $sql)) {
      echo "<script>Swal.fire('Success', 'Distributor registered successfully!', 'success').then(()=>{window.location='distri_login.php'});</script>";
    } else {
      echo "<script>Swal.fire('Error', 'Registration failed!<br>" . mysqli_error($conn) . "', 'error');</script>";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>DISTRIBUTOR REGISTRATION</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="../CSS/admin_signup.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <div class="container">
    <form class="form-group1" method="POST">
      <div class="mb-3 bg p-5 rounded">
        <a href="distri_login.php" class="btn btn-secondary mb-3 ms-0 back-btn">&larr;</a>
        <h3 class="text-center fw-bold pb-3">DISTRIBUTOR REGISTRATION</h3>

        <!-- Personal Information -->
        <div class="card mb-4 border">
          <div class="card-header bg-light">
            <h6 class="fw-bold mb-0">PERSONAL INFORMATION</h6>
          </div>
          <div class="card-body">
            <div class="row g-3 mb-3">
              <div class="col-md-3">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" name="distrib_fname" required>
              </div>
              <div class="col-md-3">
                <label class="form-label">Middle Name</label>
                <input type="text" class="form-control" name="distrib_mname">
              </div>
              <div class="col-md-3">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" name="distrib_lname" required>
              </div>
              <div class="col-md-3">
                <label class="form-label">Extension</label>
                <select class="form-select" name="distrib_extension">
                  <option value="">--</option>
                  <option value="Jr.">Jr.</option>
                  <option value="Sr.">Sr.</option>
                  <option value="II">II</option>
                  <option value="III">III</option>
                  <option value="IV">IV</option>
                </select>
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-3">
                <label class="form-label">Gender</label>
                <select class="form-select" name="distrib_gender">
                  <option value="">--</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Birthdate</label>
                <input type="date" class="form-control" id="birthday" name="distrib_birthday" required>
              </div>
              <div class="col-md-3">
                <label class="form-label">Age</label>
                <input type="text" class="form-control text-center" name="distrib_age" maxlength="3" oninput="validateAge(this)">
              </div>
              <div class="col-md-3">
                <label class="form-label">Civil Status</label>
                <select class="form-select" name="distrib_civil_status">
                  <option value=""></option>
                  <option value="Single">Single</option>
                  <option value="Married">Married</option>
                  <option value="Widowed">Widowed</option>
                  <option value="Separated">Separated</option>
                  <option value="Annulled">Annulled</option>
                </select>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Home Address</label>
                <input type="text" class="form-control" name="distrib_address" required>
              </div>
              <div class="col-md-3">
                <label class="form-label">Contact Number</label>
                <input type="text" class="form-control text-center" name="distrib_contact_number" maxlength="11" oninput="validateContactNumber(this)">
              </div>
              <div class="col-md-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="distrib_email" required>
              </div>
              <div class="col-md-3">
                <label class="form-label">Outlet</label>
                <input type="text" class="form-control" name="outlet" id="outlet" placeholder="Search for outlet (city, municipality, etc.)" autocomplete="off" required>
                <div id="outlet-suggestions" class="list-group position-absolute w-100" style="z-index: 10;"></div>
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
                <input type="text" class="form-control" name="distrib_username" required>
              </div>
              <div class="col">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="distrib_password" required>
              </div>
              <div class="col">
                <label class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="distrib_confirm_password" required>
              </div>
            </div>
            <button type="submit" class="form-control btn btn-dark mt-3 fw-semibold">SIGN UP</button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Places API autocomplete for outlet field
    const outletInput = document.getElementById('outlet');
    const suggestionsBox = document.getElementById('outlet-suggestions');
    outletInput.addEventListener('input', function() {
      const query = outletInput.value.trim();
      if (query.length < 2) {
        suggestionsBox.innerHTML = '';
        suggestionsBox.style.display = 'none';
        return;
      }
  fetch('../philippine_places_api.php?search=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
          suggestionsBox.innerHTML = '';
          if (Array.isArray(data) && data.length > 0) {
            data.forEach(place => {
              const item = document.createElement('button');
              item.type = 'button';
              item.className = 'list-group-item list-group-item-action';
              item.textContent = place;
              item.onclick = function() {
                outletInput.value = place;
                suggestionsBox.innerHTML = '';
                suggestionsBox.style.display = 'none';
              };
              suggestionsBox.appendChild(item);
            });
            suggestionsBox.style.display = 'block';
          } else {
            suggestionsBox.style.display = 'none';
          }
        });
    });
    document.addEventListener('click', function(e) {
      if (!outletInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
        suggestionsBox.innerHTML = '';
        suggestionsBox.style.display = 'none';
      }
    });
  </script>
</body>
</html>
