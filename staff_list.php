<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to database
$staffConn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($staffConn->connect_error) {
    die("Connection failed: " . $staffConn->connect_error);
}

// Fetch admin name
  $adminStmt = $staffConn->prepare("SELECT admin_fname, admin_mname, admin_lname FROM admin_signup WHERE admin_id = ?");
$adminStmt->bind_param("i", $_SESSION['admin_id']);
$adminStmt->execute();
$result = $adminStmt->get_result();
$admin = $result->fetch_assoc();
$fullName = $admin ? $admin['admin_fname'] : "Admin";
$adminStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>STAFF LIST</title>
  <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css" />
    <link rel="stylesheet" type="text/css" href="CSS/userlist.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"/>
  <script src="https://use.fontawesome.com/0c7a3095b5.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
      <div class="dashboard_content_main">

        <div class="search-container">
          <form action="" method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search staff..." class="search-input" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="search-button">
              <i class="fa fa-search"></i>
            </button>
          </form>
        </div>

        <div class="users"> 
          <table>
            <thead>
              <tr class="text-center">
                <th>#</th>
                <th>Name</th>
                
                <th>Ext.</th>
                <th>Address</th>
                <th>Outlet</th>
                <th>Contact Number</th>
                <th>Email</th>
                <th>Username</th>    
                <th>Action</th>
              </tr>      
            </thead>
            <tbody>
              <?php
              // Handle search functionality
              $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
              
              if (!empty($searchTerm)) {
                  $staffQuery = "SELECT * FROM staff_signup WHERE 
                      staff_fname LIKE ? OR 
                      staff_mname LIKE ? OR 
                      staff_lname LIKE ? OR 
                      staff_email LIKE ? OR 
                      staff_username LIKE ? OR
                      staff_contact_number LIKE ? OR
                      staff_outlet LIKE ? OR
                      staff_role LIKE ?
                      ORDER BY staff_id ASC";
                  $searchStmt = $staffConn->prepare($staffQuery);
                  $searchParam = "%" . $searchTerm . "%";
                  $searchStmt->bind_param("ssssssss", $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
                  $searchStmt->execute();
                  $staffResult = $searchStmt->get_result();
                  $searchStmt->close();
              } else {
                  $staffQuery = "SELECT * FROM staff_signup ORDER BY staff_id ASC";
                  $staffResult = $staffConn->query($staffQuery);
              }

              if ($staffResult && $staffResult->num_rows > 0) {
                  $count = 1;
                  while ($row = $staffResult->fetch_assoc()) {
                      echo "<tr>";
                      echo "<td>" . $count++ . "</td>";
                      echo "<td>" . htmlspecialchars(isset($row['staff_fname']) ? $row['staff_fname'] . ' ' . (isset($row['staff_mname']) ? $row['staff_mname'] . ' ' : '') . (isset($row['staff_lname']) ? $row['staff_lname'] : '') : 'N/A') . "</td>";
                      echo "<td>" . htmlspecialchars(isset($row['staff_extension']) ? $row['staff_extension'] : 'N/A') . "</td>";   
                      echo "<td>" . htmlspecialchars(isset($row['staff_address']) ? $row['staff_address'] : 'N/A') . "</td>";
                      echo "<td>" . htmlspecialchars(isset($row['staff_outlet']) ? $row['staff_outlet'] : 'N/A') . "</td>";
                      echo "<td>" . htmlspecialchars(isset($row['staff_contact_number']) ? $row['staff_contact_number'] : 'N/A') . "</td>";
                      echo "<td>" . htmlspecialchars(isset($row['staff_email']) ? $row['staff_email'] : 'N/A') . "</td>";
                      echo "<td>" . htmlspecialchars(isset($row['staff_username']) ? $row['staff_username'] : 'N/A') . "</td>";
                      echo "<td>
                          <button class='btn btn-sm btn-info' onclick='viewStaff(" . $row['staff_id'] . ")' data-bs-toggle='tooltip' title='View Staff Details'>
                              <i class='fa fa-eye text-white'></i>
                          </button>
                          <button class='btn btn-sm btn-primary' onclick='editStaff(" . $row['staff_id'] . ")' data-bs-toggle='tooltip' title='Edit Staff'>
                              <i class='fa fa-edit'></i> 
                          </button>
                          <button class='btn btn-sm btn-danger' onclick='deleteStaff(" . $row['staff_id'] . ")' data-bs-toggle='tooltip' title='Delete Staff'>
                              <i class='fa fa-trash'></i> 
                          </button>
                      </td>";
                      echo "</tr>";
                  }
              } else {
                  echo "<tr><td colspan='16'>No staff found.</td></tr>";
              }
              
              // Close database connection after all queries are done
              $staffConn->close();
              ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- SIDEBAR TOGGLE SCRIPT -->
<!-- Include responsive sidebar functionality -->
    <script src="sidebar-drawer.js"></script>

<script>
// Staff management functions
function viewStaff(staffId) {
  // Fetch staff details via AJAX
  fetch(`get_staff_details.php?staff_id=${staffId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const staff = data.staff;
  const fullName = `${staff.staff_fname} ${staff.staff_mname ? staff.staff_mname + ' ' : ''}${staff.staff_lname}${staff.staff_extension ? ' ' + staff.staff_extension : ''}`;
        const createdDate = new Date(staff.created_at).toLocaleDateString();
        
        Swal.fire({
          title: 'Staff Details',
          html: `
            <div class="staff-details" style="text-align: left;">
              <div class="row">
                <div class="col-md-6">
                  <p><strong>ID:</strong> ${staff.staff_id}</p>
                  <p><strong>Name:</strong> ${fullName}</p>
                  <p><strong>Email:</strong> ${staff.staff_email}</p>
                  <p><strong>Username:</strong> ${staff.staff_username}</p>
                  <p><strong>Role:</strong> ${staff.staff_role}</p>
                  <p><strong>Gender:</strong> ${staff.staff_gender || 'N/A'}</p>
                </div>
                <div class="col-md-6">
                  <p><strong>Contact:</strong> ${staff.staff_contact_number}</p>
                  <p><strong>Outlet:</strong> ${staff.staff_outlet}</p>
                  <p><strong>Address:</strong> ${staff.staff_address}</p>
                  <p><strong>Birthday:</strong> ${staff.staff_birthday || 'N/A'}</p>
                  <p><strong>Age:</strong> ${staff.staff_age || 'N/A'}</p>
                  <p><strong>Civil Status:</strong> ${staff.staff_civil_status || 'N/A'}</p>
                  <p><strong>Created:</strong> ${createdDate}</p>
                </div>
              </div>
            </div>
          `,
          width: '700px',
          showCloseButton: true,
          showConfirmButton: false,
          customClass: {
            popup: 'swal-wide'
          }
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message || 'Failed to fetch staff details'
        });
      }
    })
    .catch(error => {
      console.error('Error fetching staff details:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to fetch staff details'
      });
    });
}

function editStaff(staffId) {
  window.location.href = 'edit_universal_profile.php?type=staff&id=' + staffId;
}

function deleteStaff(staffId) {
  Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, delete it!'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch('delete_staff.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({staff_id: staffId})
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire('Deleted!', 'Staff has been deleted.', 'success')
          .then(() => location.reload());
        } else {
          Swal.fire('Error!', data.message || 'Failed to delete staff.', 'error');
        }
      })
      .catch(error => {
        Swal.fire('Error!', 'An error occurred while deleting.', 'error');
      });
    }
  });
}
</script>

</body>
</html>
