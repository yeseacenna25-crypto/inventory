<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// DB Connection
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get admin name
$stmt = $conn->prepare("SELECT admin_fname, admin_mname, admin_lname FROM admin_signup WHERE admin_id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$fullName = "Admin";
if (isset($_SESSION['role'])) {
  if ($_SESSION['role'] === 'Admin' && isset($admin['first_name'])) {
    $fullName = $admin['first_name'];
  } elseif ($_SESSION['role'] === 'Staff' && isset($admin['staff_fname'])) {
    $fullName = $admin['staff_fname'];
  } elseif ($_SESSION['role'] === 'Distributor' && isset($admin['distrib_fname'])) {
    $fullName = $admin['distrib_fname'];
  }
}
$stmt->close();

// Alert message flag
$alert = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $first_name = trim($_POST['first_name']);
  $middle_name = trim($_POST['middle_name']);
  $last_name = trim($_POST['last_name']);
  $extension = $_POST['extension'];
  $gender = $_POST['gender'];
  $birthday = $_POST['birthday'];
  $age = (int)$_POST['age'];
  $civil_status = $_POST['civil_status'];
  $address = trim($_POST['address']);
  $contact_number = trim($_POST['contact_number']);
  $email = trim($_POST['email']);
  $username = trim($_POST['username']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];
  $role = $_POST['role'];
  $outlet = $_POST['outlet'];

  if ($password !== $confirm_password) {
    $alert = "Passwords do not match";
  } else {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Role-based table logic
    switch ($role) {
      case 'Admin':
        $table = 'admin_signup';
        $sql = "INSERT INTO $table (admin_fname, admin_mname, admin_lname, admin_extension, admin_gender, admin_birthday, admin_age, admin_civil_status, admin_address, admin_contact_number, admin_email, admin_username, admin_password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$first_name, $middle_name, $last_name, $extension, $gender, $birthday, $age, $civil_status, $address, $contact_number, $email, $username, $hashedPassword];
        $bind_types = "ssssssissssss";
        break;
      case 'Staff':
        $table = 'staff_signup';
        $sql = "INSERT INTO $table (staff_fname, staff_mname, staff_lname, staff_extension, staff_gender, staff_birthday, staff_age, staff_civil_status, staff_address, staff_contact_number, staff_email, staff_username, staff_password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$first_name, $middle_name, $last_name, $extension, $gender, $birthday, $age, $civil_status, $address, $contact_number, $email, $username, $hashedPassword];
        $bind_types = "ssssssissssss";
        break;
      case 'Distributor':
        $table = 'distributor_signup';
        $sql = "INSERT INTO $table (distrib_fname, distrib_mname, distrib_lname, distrib_extension, distrib_gender, distrib_birthday, distrib_age, distrib_civil_status, distrib_address, distrib_contact_number, distrib_email, distrib_username, distrib_password, distrib_outlet) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$first_name, $middle_name, $last_name, $extension, $gender, $birthday, $age, $civil_status, $address, $contact_number, $email, $username, $hashedPassword, $outlet];
        $bind_types = "sssssissssssss";
        break;
      default:
        $alert = "Invalid role";
        exit();
    }

    $stmt = $conn->prepare($sql);
    if ($stmt) {
      $stmt->bind_param($bind_types, ...$params);

            if ($stmt->execute()) {
                $alert = "User added successfully";
            } else {
                $alert = "Error: " . htmlspecialchars($stmt->error, ENT_QUOTES);
            }
            $stmt->close();
        } else {
            $alert = "Prepare failed: " . htmlspecialchars($conn->error, ENT_QUOTES);
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ADD USER</title>
  <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css?v=<?= time(); ?>">
    <link rel="stylesheet" type="text/css" href="CSS/add_product.css?v=<?= time(); ?>" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
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
         

        <div class="modern-container" style="margin-bottom: -50px;">
          <div class="container-fluid">
            
            <!-- Page Header -->
            <h4 class="page-title">
              <i class="bi bi-person-plus-fill me-3"></i>
              ADD NEW USER
            </h4>
            
           
              
              <form action="" method="POST" class="appForm">

                <!-- Personal Information -->
                <div class="">
                    <div class="section-header" style="margin-bottom: -10px;">
                    <h6>
                      <i class="bi bi-person-plus-fill"></i>
                      PERSONAL INFORMATION
                    </h6>
                    <p class="mb-0 mt-0 opacity-75" style="margin-left: 28px;">Fill in the information below to create a new user account</p>
                  </div>


                    
                 
                  <div class="form-section">
                    <div class="row g-4 mb-3">
                      <div class="col-md-3">
                        <label for="first_name" class="modern-label">
                          <i class="bi bi-person"></i>
                          First Name
                        </label>
                        <input type="text" class="form-control modern-form-control" id="first_name" name="first_name" required>
                      </div>
                      <div class="col-md-3">
                        <label for="middle_name" class="modern-label">
                          <i class="bi bi-person"></i>
                          Middle Name
                        </label>
                        <input type="text" class="form-control modern-form-control" id="middle_name" name="middle_name">
                      </div>
                      <div class="col-md-3">
                        <label for="last_name" class="modern-label">
                          <i class="bi bi-person"></i>
                          Last Name
                        </label>
                        <input type="text" class="form-control modern-form-control" id="last_name" name="last_name" required>
                      </div>
                      <div class="col-md-3">
                        <label for="extension" class="modern-label">
                          <i class="bi bi-tags"></i>
                          Extension
                        </label>
                        <select class="form-select modern-form-control" id="extension" name="extension">
                          <option value="">--</option>
                          <option value="Jr.">Jr.</option>
                          <option value="Sr.">Sr.</option>
                          <option value="II">II</option>
                          <option value="III">III</option>
                          <option value="IV">IV</option>
                        </select>
                      </div>
                    </div>

                    <div class="row g-4 mb-3">
                      <div class="col-md-3">
                        <label for="gender" class="modern-label">
                          <i class="bi bi-gender-ambiguous"></i>
                          Gender
                        </label>
                        <select class="form-select modern-form-control" id="gender" name="gender">
                          <option value="">--</option>
                          <option value="Male">Male</option>
                          <option value="Female">Female</option>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label for="birthday" class="modern-label">
                          <i class="bi bi-calendar-heart"></i>
                          Birthdate
                        </label>
                        <input type="date" class="form-control modern-form-control" id="birthday" name="birthday" required>
                      </div>
                      <div class="col-md-3">
                        <label for="age" class="modern-label">
                          <i class="bi bi-hourglass-split"></i>
                          Age
                        </label>
                        <input type="text" class="form-control modern-form-control" id="age" name="age" required>
                      </div>
                      <div class="col-md-3">
                        <label for="civil_status" class="modern-label">
                          <i class="bi bi-heart"></i>
                          Civil Status
                        </label>
                        <select class="form-select modern-form-control" id="civil_status" name="civil_status">
                          <option value="Single">Single</option>
                          <option value="Married">Married</option>
                          <option value="Widowed">Widowed</option>
                          <option value="Separated">Separated</option>
                          <option value="Annulled">Annulled</option>
                        </select>
                      </div>
                    </div>

                    <div class="row g-4">
                      <div class="col-md-3">
                        <label for="address" class="modern-label">
                          <i class="bi bi-house"></i>
                          Home Address
                        </label>
                        <input type="text" class="form-control modern-form-control" id="address" name="address" required>
                      </div>
                      <div class="col-md-3">
                        <label for="contact_number" class="modern-label">
                          <i class="bi bi-telephone"></i>
                          Contact Number
                        </label>
                        <input type="text" class="form-control modern-form-control" id="contact_number" name="contact_number" pattern="[0-9]{11}" placeholder="11 digit mobile number">
                      </div>
                      <div class="col-md-3">
                        <label for="email" class="modern-label">
                          <i class="bi bi-envelope"></i>
                          Email Address
                        </label>
                        <input type="email" class="form-control modern-form-control" id="email" name="email" required>
                      </div>
                      <div class="col-md-3">
                        <label for="role" class="modern-label">
                          <i class="bi bi-person-badge"></i>
                          Role
                        </label>
                        <select class="form-select modern-form-control" id="role" name="role" required>
                          <option value="">--</option>
                          <option value="Admin">Admin</option>
                          <option value="Staff">Staff</option>
                          <option value="Distributor">Distributor</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Account Information -->
                <div class="">
                  <div class="section-header">
                    <h6>
                      <i class="bi bi-key"></i>
                      ACCOUNT INFORMATION
                    </h6>
                  </div>
                  <div class="form-section">
                    <div class="row g-4">
                      <div class="col-md-4">
                        <label for="username" class="modern-label">
                          <i class="bi bi-person-circle"></i>
                          Username
                        </label>
                        <input type="text" class="form-control modern-form-control" id="username" name="username" required>
                      </div>
                      <div class="col-md-4">
                        <label for="password" class="modern-label">
                          <i class="bi bi-shield-lock"></i>
                          Password
                        </label>
                        <input type="password" class="form-control modern-form-control" id="password" name="password" required>
                      </div>
                      <div class="col-md-4">
                        <label for="confirm_password" class="modern-label">
                          <i class="bi bi-shield-check"></i>
                          Confirm Password
                        </label>
                        <input type="password" class="form-control modern-form-control" id="confirm_password" name="confirm_password" required>
                      </div>
                    </div>
                  </div>
                </div>


                <!-- Admin Information (conditionally shown) -->
                <div class="" id="admin-section" style="display: none;">
                  <div class="section-header">
                    <h6>
                      <i class="bi bi-person-badge"></i>
                      ADMIN INFORMATION
                    </h6>
                  </div>
                  <div class="form-section">
                    <div class="row g-4">
                      <div class="col-md-6">
                        <label for="admin_branch" class="modern-label">
                          <i class="bi bi-geo-alt"></i>
                          Outlet/Branch
                        </label>
                        <select class="form-select modern-form-control" id="admin_branch" name="admin_branch">
                          <option value="">--</option>
                        </select>
                        <div id="admin-places-list" class="list-group" style="position: absolute; z-index: 1000; display: none;"></div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Staff Information (conditionally shown) -->
                <div class="" id="staff-section" style="display: none;">
                  <div class="section-header">
                    <h6>
                      <i class="bi bi-person"></i>
                      STAFF INFORMATION
                    </h6>
                  </div>
                  <div class="form-section">
                    <div class="row g-4">
                      <div class="col-md-6">
                        <label for="staff_branch" class="modern-label">
                          <i class="bi bi-geo-alt"></i>
                          Outlet/Branch
                        </label>
                        <select class="form-select modern-form-control" id="staff_branch" name="staff_branch">
                          <option value="">--</option>
                        </select>
                        <div id="staff-places-list" class="list-group" style="position: absolute; z-index: 1000; display: none;"></div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Distributor Information (conditionally shown) -->
                <div class="" id="distributor-section" style="display: none;">
                  <div class="section-header">
                    <h6>
                      <i class="bi bi-shop"></i>
                      DISTRIBUTOR INFORMATION
                    </h6>
                  </div>
                  <div class="form-section">
                    <div class="row g-4">
                     
                      <div class="col-md-6">
                        <label for="outlet" class="modern-label">
                          <i class="bi bi-geo-alt"></i>
                          Outlet/Branch
                        </label>
                        <select class="form-select modern-form-control" id="outlet" name="outlet">
                          <option value="">--</option>
                          <option value="Luzon">Luzon</option>
                          <option value="Visayas">Visayas</option>
                          <option value="Mindanao">Mindanao</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center mt-4">
                  <button type="submit" class="btn modern-btn" name="add_user">
                    <i class="bi bi-person-plus"></i>
                    Add New User
                  </button>
                  <button type="reset" class="btn modern-btn-secondary ms-3">
                    <i class="bi bi-arrow-clockwise"></i>
                    Clear Form
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
        <script>
      // Helper to build full place name
      function buildPlaceNames(data) {
        let names = [];
        data.forEach(region => {
          region.provinces.forEach(province => {
            province.cities.forEach(city => {
              names.push(`${region.region} - ${province.province} - ${city}`);
            });
          });
        });
        return names;
      }

      // Populate select options for branch fields
      function populateBranchSelect(selectId) {
        fetch('philippine_places_api.php')
          .then(response => response.json())
          .then(data => {
            const select = document.getElementById(selectId);
            select.innerHTML = '<option value="">--</option>';
            buildPlaceNames(data).forEach(place => {
              const option = document.createElement('option');
              option.value = place;
              option.textContent = place;
              select.appendChild(option);
            });
          });
      }

      document.addEventListener('DOMContentLoaded', function() {
        populateBranchSelect('admin_branch');
        populateBranchSelect('staff_branch');
        populateBranchSelect('outlet');
      });
      // Helper function to flatten API response for autocomplete
      function getPlaceSuggestions(data) {
        let suggestions = [];
        data.forEach(region => {
          if (region.region) suggestions.push(region.region);
          region.provinces.forEach(province => {
            if (province.province) suggestions.push(province.province);
            province.cities.forEach(city => {
              suggestions.push(city);
            });
          });
        });
        return suggestions.filter((v, i, a) => a.indexOf(v) === i); // unique
      }

      // Autocomplete for Admin Branch
      const adminBranchInput = document.getElementById('admin_branch');
      const adminPlacesList = document.getElementById('admin-places-list');
      adminBranchInput.addEventListener('input', function() {
        const query = adminBranchInput.value.trim();
        if (query.length === 0) {
          adminPlacesList.style.display = 'none';
          return;
        }
        fetch('philippine_places_api.php?search=' + encodeURIComponent(query))
          .then(response => response.json())
          .then(data => {
            adminPlacesList.innerHTML = '';
            const suggestions = getPlaceSuggestions(data);
            if (suggestions.length > 0) {
              suggestions.forEach(place => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.textContent = place;
                item.onclick = function() {
                  adminBranchInput.value = place;
                  adminPlacesList.style.display = 'none';
                };
                adminPlacesList.appendChild(item);
              });
              adminPlacesList.style.display = 'block';
            } else {
              adminPlacesList.style.display = 'none';
            }
          });
      });
      document.addEventListener('click', function(e) {
        if (!adminBranchInput.contains(e.target) && !adminPlacesList.contains(e.target)) {
          adminPlacesList.style.display = 'none';
        }
      });

      // Autocomplete for Staff Branch
      const staffBranchInput = document.getElementById('staff_branch');
      const staffPlacesList = document.getElementById('staff-places-list');
      staffBranchInput.addEventListener('input', function() {
        const query = staffBranchInput.value.trim();
        if (query.length === 0) {
          staffPlacesList.style.display = 'none';
          return;
        }
        fetch('philippine_places_api.php?search=' + encodeURIComponent(query))
          .then(response => response.json())
          .then(data => {
            staffPlacesList.innerHTML = '';
            const suggestions = getPlaceSuggestions(data);
            if (suggestions.length > 0) {
              suggestions.forEach(place => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.textContent = place;
                item.onclick = function() {
                  staffPlacesList.style.display = 'none';
                };
                staffPlacesList.appendChild(item);
              });
              staffPlacesList.style.display = 'block';
            } else {
              staffPlacesList.style.display = 'none';
            }
          });
      });
      document.addEventListener('click', function(e) {
        if (!staffBranchInput.contains(e.target) && !staffPlacesList.contains(e.target)) {
          staffPlacesList.style.display = 'none';
        }
      });
      // Philippine Places API search for Outlet field
      const outletInput = document.getElementById('outlet');
      const placesList = document.getElementById('places-list');
      let places = [];

      outletInput.addEventListener('input', function() {
        const query = outletInput.value.trim();
        if (query.length === 0) {
          placesList.style.display = 'none';
          return;
        }
        fetch('philippine_places_api.php?search=' + encodeURIComponent(query))
          .then(response => response.json())
          .then(data => {
            places = data;
            placesList.innerHTML = '';
            if (places.length > 0) {
              places.forEach(place => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action';
                item.textContent = place;
                item.onclick = function() {
                  outletInput.value = place;
                  placesList.style.display = 'none';
                };
                placesList.appendChild(item);
              });
              placesList.style.display = 'block';
            } else {
              placesList.style.display = 'none';
            }
          });
      });

      // Hide dropdown when clicking outside
      document.addEventListener('click', function(e) {
        if (!outletInput.contains(e.target) && !placesList.contains(e.target)) {
          placesList.style.display = 'none';
        }
      });

     

    
      // Birthday to Age calculation
      document.getElementById('birthday').addEventListener('change', function() {
        const birthDate = new Date(this.value);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
          age--;
        }
        
        document.getElementById('age').value = age;
      });

      // Show/hide distributor section based on role
      document.getElementById('role').addEventListener('change', function() {
        const adminSection = document.getElementById('admin-section');
        const staffSection = document.getElementById('staff-section');
        const distributorSection = document.getElementById('distributor-section');
        adminSection.style.display = 'none';
        staffSection.style.display = 'none';
        distributorSection.style.display = 'none';
        if (this.value === 'Admin') {
          adminSection.style.display = 'block';
        } else if (this.value === 'Staff') {
          staffSection.style.display = 'block';
        } else if (this.value === 'Distributor') {
          distributorSection.style.display = 'block';
        }
      });

      // Password confirmation validation
      document.getElementById('m_password').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirmPassword = this.value;
        
        if (password !== confirmPassword) {
          this.setCustomValidity('Passwords do not match');
        } else {
          this.setCustomValidity('');
        }
      });
    </script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn    </script>
pm/sweetalert2@11"></script>

<!-- Alert handling -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  <?php if (!empty($alert)): ?>
    Swal.fire({
      icon: 'success',
      title: 'Notice',
      text: "<?php echo $alert; ?>",
      confirmButtonColor: '#168b20ff'
    });
  <?php endif; ?>
});
</script>

    
<!-- Include responsive sidebar functionality -->
    <script src="sidebar-drawer.js"></script>

      
    </script>

</body>
</html>
