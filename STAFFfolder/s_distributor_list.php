<?php
session_start();

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch distributor list
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT distributor_id, distrib_fname, distrib_mname, distrib_lname, distrib_extension, distrib_address, distrib_outlet, distrib_contact_number, distrib_email, distrib_username FROM distributor_signup WHERE 1=1";
if (!empty($search)) {
    $sql .= " AND (distrib_fname LIKE ? OR distrib_mname LIKE ? OR distrib_lname LIKE ? OR distrib_username LIKE ? OR distrib_email LIKE ? OR distrib_outlet LIKE ?)";
}
$sql .= " ORDER BY distributor_id DESC";

if (!empty($search)) {
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$search%";
    $stmt->bind_param("ssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$distributors = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $distributors[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DISTRIBUTOR LIST</title>
    <link rel="stylesheet" type="text/css" href="../CSS/admin_dashboard.css" />
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
  <?php include('STAFFpartials/s_sidebar.php') ?>
  <!-- SIDEBAR -->

  <div class="dashboard_content_container" id="dashboard_content_container">

    <!-- TOP NAVBAR -->
    <?php include('STAFFpartials/s_topnav.php') ?>
    <!-- TOP NAVBAR -->

          <div class="dashboard_content">
          <div class="dashboard_content_main">


          <div class="search-container">
          <form action="" method="GET" class="search-form">
          <input type="text" name="search" placeholder="Search distributors..." class="search-input" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
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
              <?php if (empty($distributors)): ?>
                <tr>
                  <td colspan="15" class="text-center">No distributors found</td>
                </tr>
              <?php else: ?>
                <?php foreach ($distributors as $index => $distributor): ?>
                <tr>
                  <td><?php echo $index + 1; ?></td>
                   <td><?php echo htmlspecialchars(($distributor['distrib_fname'] ?? '') . ' ' . ($distributor['distrib_mname'] ?? '') . ' ' . ($distributor['distrib_lname'] ?? '')); ?></td>
                  <td><?php echo htmlspecialchars($distributor['distrib_extension'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($distributor['distrib_address'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($distributor['distrib_outlet']); ?></td>
                  <td><?php echo htmlspecialchars($distributor['distrib_contact_number']); ?></td>
                  <td><?php echo htmlspecialchars($distributor['distrib_email']); ?></td>
                  <td><?php echo htmlspecialchars($distributor['distrib_username']); ?></td>
                  <td>
                     <?php if (!isset($_SESSION['staff_id']) || $distributor['distributor_id'] != $_SESSION['staff_id']): ?>
                    <button class="btn btn-sm btn-info" onclick="viewDistributor(<?php echo $distributor['distributor_id']; ?>)" data-bs-toggle="tooltip" title="View Distributor Details">
                      <i class="fa fa-eye text-white"></i>
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="editDistributor(<?php echo $distributor['distributor_id']; ?>)" data-bs-toggle="tooltip" title="Edit Distributor">
                      <i class="fa fa-edit"></i>
                    </button>
                    <?php else: ?>
                    <button class="btn btn-sm btn-info" onclick="viewDistributor(<?php echo $distributor['distributor_id']; ?>)" data-bs-toggle="tooltip" title="View Distributor Details">
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
    <script src="../sidebar-drawer.js"></script>
    <script>
      // Prevent browser back navigation
      history.pushState(null, null, location.href);
      window.onpopstate = function () {
        history.go(1);
      };
    </script>
    <script>
      // Distributor management functions
      function viewDistributor(distributorId) {
  // Fetch distributor details
  fetch('s_get_distributor_details.php?distributor_id=' + distributorId)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const distributor = data.distributor;
              const fullName = `${distributor.distrib_fname} ${distributor.distrib_mname ? distributor.distrib_mname + ' ' : ''}${distributor.distrib_lname}${distributor.distrib_extension ? ' ' + distributor.distrib_extension : ''}`;
              const createdDate = new Date(distributor.created_at).toLocaleDateString();

              Swal.fire({
                title: 'Distributor Details',
                html: `
                  <div class="distributor-details" style="text-align: left;">
                    <div class="row">
                      <div class="col-md-6">
                        <p><strong>ID:</strong> ${distributor.distributor_id}</p>
                        <p><strong>Name:</strong> ${fullName}</p>
                        <p><strong>Email:</strong> ${distributor.distrib_email}</p>
                        <p><strong>Username:</strong> ${distributor.distrib_username}</p>
                        <p><strong>Role:</strong> ${distributor.distrib_role}</p>
                        <p><strong>Gender:</strong> ${distributor.distrib_gender || 'N/A'}</p>
                      </div>
                      <div class="col-md-6">
                        <p><strong>Contact:</strong> ${distributor.distrib_contact_number}</p>
                        <p><strong>Outlet:</strong> ${distributor.distrib_outlet}</p>
                        <p><strong>Address:</strong> ${distributor.distrib_address}</p>
                        <p><strong>Birthday:</strong> ${distributor.distrib_birthday || 'N/A'}</p>
                        <p><strong>Age:</strong> ${distributor.distrib_age || 'N/A'}</p>
                        <p><strong>Civil Status:</strong> ${distributor.distrib_civil_status || 'N/A'}</p>
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
                text: data.message || 'Failed to fetch distributor details'
              });
            }
          })
          .catch(error => {
            console.error('Error fetching distributor details:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Failed to fetch distributor details'
            });
          });
      }

      function editDistributor(distributorId) {
        window.location.href = 's_edit_universal_profile.php?type=distributor&id=' + distributorId;
      }

      function deleteDistributor(distributorId) {
        Swal.fire({
          title: 'Are you sure?',
          text: "You won't be able to revert this! This will permanently delete the distributor account.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            fetch('delete_distributor.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({distributor_id: distributorId})
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                Swal.fire('Deleted!', 'Distributor has been deleted.', 'success')
                .then(() => location.reload());
              } else {
                Swal.fire('Error!', data.message || 'Failed to delete distributor.', 'error');
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