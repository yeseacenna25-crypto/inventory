<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch admin name
$stmt = $conn->prepare("SELECT admin_fname, admin_mname, admin_lname FROM admin_signup WHERE admin_id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$current_admin = $result->fetch_assoc();

$fullName = $current_admin ? $current_admin['admin_fname']: "Admin";
$stmt->close();

// Fetch all admin data for admin list
$search = isset($_GET['search']) ? $_GET['search'] : '';

$sql = "SELECT admin_id, admin_fname, admin_mname, admin_lname, admin_extension, admin_gender, admin_birthday, admin_age, admin_civil_status, admin_address, admin_outlet, admin_contact_number, admin_email, admin_username, admin_role, created_at FROM admin_signup WHERE 1=1";

if (!empty($search)) {
  $sql .= " AND (admin_fname LIKE ? OR admin_mname LIKE ? OR admin_lname LIKE ? OR admin_username LIKE ? OR admin_email LIKE ? OR admin_outlet LIKE ?)";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($search)) {
    $searchTerm = "%$search%";
    $stmt->bind_param("ssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

$stmt->execute();
$result = $stmt->get_result();
$admins = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ADMIN LIST</title>
    <link rel="stylesheet" type="text/css" href="CSS/admin_dashboard.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
    />
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
          <input type="text" name="search" placeholder="Search admins..." class="search-input" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
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
              <th>Phone Number</th>
              <th>Email</th>
              <th>Username</th>
              <th>Action</th>
            </tr>      
          </thead>
          <tbody>
              <?php if (empty($admins)): ?>
                <tr>
                  <td colspan="15" class="text-center">No admins found</td>
                </tr>
              <?php else: ?>
                <?php foreach ($admins as $index => $admin): ?>
                <tr>
                  <td><?php echo $index + 1; ?></td>
                   <td><?php echo htmlspecialchars(($admin['admin_fname'] ?? '') . ' ' . ($admin['admin_mname'] ?? '') . ' ' . ($admin['admin_lname'] ?? '')); ?></td>

                  <td><?php echo htmlspecialchars($admin['admin_extension'] ?? ''); ?></td>

                  <td><?php echo htmlspecialchars($admin['admin_address'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($admin['admin_outlet']); ?></td>
                  <td><?php echo htmlspecialchars($admin['admin_contact_number']); ?></td>
                  <td><?php echo htmlspecialchars($admin['admin_email']); ?></td>
                  <td><?php echo htmlspecialchars($admin['admin_username']); ?></td>
                  <td>
                     <?php if ($admin['admin_id'] != $_SESSION['admin_id']): ?>
                    <button class="btn btn-sm btn-info" onclick="viewAdmin(<?php echo $admin['admin_id']; ?>)" data-bs-toggle="tooltip" title="View Admin Details">
                      <i class="fa fa-eye text-white"></i>
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="editAdmin(<?php echo $admin['admin_id']; ?>)" data-bs-toggle="tooltip" title="Edit Admin">
                      <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteAdmin(<?php echo $admin['admin_id']; ?>)" data-bs-toggle="tooltip" title="Delete Admin">
                      <i class="fa fa-trash"></i>
                    </button>
                    <?php else: ?>
                    <button class="btn btn-sm btn-info" onclick="viewAdmin(<?php echo $admin['admin_id']; ?>)" data-bs-toggle="tooltip" title="View Admin Details">
                      <i class="fa fa-eye text-white"></i>
                    </button>
                    <span class="badge bg-success">Current User</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
          </tbody>
          
        </table>
      </div>
      </div>                       
</div>
</div>
</div>
</div>
</div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Include responsive sidebar functionality -->
    <script src="sidebar-drawer.js"></script>
<?php if (isset($_GET['new_admin_id'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var row = document.getElementById('newAdminRow');
  if (row) {
    row.scrollIntoView({behavior: 'smooth', block: 'center'});
    Swal.fire({
      icon: 'success',
      title: 'New Admin Added',
      text: 'The new admin has been added and highlighted.',
      confirmButtonColor: '#168b20ff'
    });
  }
});
</script>
<?php endif; ?>
    <script>
      // Prevent browser back navigation
      history.pushState(null, null, location.href);
      window.onpopstate = function () {
        history.go(1);
      };
    </script>
    <script>
      // Admin management functions
      function viewAdmin(adminId) {
        // Fetch admin details
        fetch('get_admin_details.php?admin_id=' + adminId)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const admin = data.admin;
              const fullName = `${admin.admin_fname} ${admin.admin_mname ? admin.admin_mname + ' ' : ''}${admin.admin_lname}${admin.admin_extension ? ' ' + admin.admin_extension : ''}`;
              const createdDate = new Date(admin.created_at).toLocaleDateString();
              
              Swal.fire({
                title: 'Admin Details',
                html: `
                  <div class="admin-details" style="text-align: left;">
                    <div class="row">
                      <div class="col-md-6">
                        <p><strong>ID:</strong> ${admin.admin_id}</p>
                        <p><strong>Name:</strong> ${fullName}</p>
                        <p><strong>Email:</strong> ${admin.admin_email}</p>
                        <p><strong>Username:</strong> ${admin.admin_username}</p>
                        <p><strong>Role:</strong> ${admin.admin_role}</p>
                        <p><strong>Gender:</strong> ${admin.admin_gender || 'N/A'}</p>
                      </div>
                      <div class="col-md-6">
                        <p><strong>Contact:</strong> ${admin.admin_contact_number}</p>
                        <p><strong>Outlet:</strong> ${admin.admin_outlet}</p>
                        <p><strong>Address:</strong> ${admin.admin_address}</p>
                        <p><strong>Birthday:</strong> ${admin.admin_birthday || 'N/A'}</p>
                        <p><strong>Age:</strong> ${admin.admin_age || 'N/A'}</p>
                        <p><strong>Civil Status:</strong> ${admin.admin_civil_status || 'N/A'}</p>
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
                text: data.message || 'Failed to fetch admin details'
              });
            }
          })
          .catch(error => {
            console.error('Error fetching admin details:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Failed to fetch admin details'
            });
          });
      }

      function editAdmin(adminId) {
        window.location.href = 'edit_universal_profile.php?type=admin&id=' + adminId;
      }

      function deleteAdmin(adminId) {
        Swal.fire({
          title: 'Are you sure?',
          text: "You won't be able to revert this! This will permanently delete the admin account.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            fetch('delete_admin.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({admin_id: adminId})
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire('Deleted!', 'Admin has been deleted.', 'success')
                .then(() => location.reload());
              } else {
                Swal.fire('Error!', data.message || 'Failed to delete admin.', 'error');
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