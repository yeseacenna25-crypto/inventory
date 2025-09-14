<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get user type and ID from URL parameters
$user_type = isset($_GET['type']) ? $_GET['type'] : 'staff';
$user_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['staff_id'];

// Validate user type
$allowed_types = ['admin', 'staff', 'distributor'];
if (!in_array($user_type, $allowed_types)) {
    header("Location: admin_dashboard.php");
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Determine table and ID column based on user type
$table_config = [
    'admin' => ['table' => 'admin_signup', 'id_col' => 'admin_id'],
    'staff' => ['table' => 'staff_signup', 'id_col' => 'staff_id'],
    'distributor' => ['table' => 'distributor_signup', 'id_col' => 'distributor_id']
];

$config = $table_config[$user_type];
$table = $config['table'];
$id_column = $config['id_col'];

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM $table WHERE $id_column = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Debug: Log the data being retrieved from database
error_log("User Type: " . $user_type);
error_log("User ID: " . $user_id);
error_log("Table: " . $table);
error_log("Full User Data: " . print_r($user_data, true));

// Extract field values for display and debugging
if ($user_type === 'admin') {
    $gender = $user_data['admin_gender'] ?? '';
    $birthday = $user_data['admin_birthday'] ?? '';
    $age = $user_data['admin_age'] ?? '';
    $civil_status = $user_data['admin_civil_status'] ?? '';
    $address = $user_data['admin_address'] ?? '';
    
    error_log("Civil Status: " . ($user_data['admin_civil_status'] ?? 'not set'));
    error_log("Gender: " . ($user_data['admin_gender'] ?? 'not set'));
    error_log("Birthday: " . ($user_data['admin_birthday'] ?? 'not set'));
    error_log("Age: " . ($user_data['admin_age'] ?? 'not set'));
    error_log("Address: " . ($user_data['admin_address'] ?? 'not set'));
} elseif ($user_type === 'staff') {
    $gender = $user_data['staff_gender'] ?? '';
    $birthday = $user_data['staff_birthday'] ?? '';
    $age = $user_data['staff_age'] ?? '';
    $civil_status = $user_data['staff_civil_status'] ?? '';
    $address = $user_data['staff_address'] ?? '';
    
    error_log("Civil Status: " . ($user_data['staff_civil_status'] ?? 'not set'));
    error_log("Gender: " . ($user_data['staff_gender'] ?? 'not set'));
    error_log("Birthday: " . ($user_data['staff_birthday'] ?? 'not set'));
    error_log("Age: " . ($user_data['staff_age'] ?? 'not set'));
    error_log("Address: " . ($user_data['staff_address'] ?? 'not set'));
} else {
    $gender = $user_data['distrib_gender'] ?? '';
    $birthday = $user_data['distrib_birthday'] ?? '';
    $age = $user_data['distrib_age'] ?? '';
    $civil_status = $user_data['distrib_civil_status'] ?? '';
    $address = $user_data['distrib_address'] ?? '';
    
    error_log("Civil Status: " . ($user_data['distrib_civil_status'] ?? 'not set'));
    error_log("Gender: " . ($user_data['distrib_gender'] ?? 'not set'));
    error_log("Birthday: " . ($user_data['distrib_birthday'] ?? 'not set'));
    error_log("Age: " . ($user_data['distrib_age'] ?? 'not set'));
    error_log("Address: " . ($user_data['distrib_address'] ?? 'not set'));
}

// Add explicit PHP debugging
error_log("PHP Variables:");
error_log("Gender: " . ($gender ?? 'not set'));
error_log("Birthday: " . ($birthday ?? 'not set'));
error_log("Age: " . ($age ?? 'not set'));
error_log("Civil Status: " . ($civil_status ?? 'not set'));
error_log("Address: " . ($address ?? 'not set'));

if (!$user_data) {
    $_SESSION['error'] = "User not found.";
    header("Location: staff_dashboard.php");
    exit();
}

// Check if current staff can edit this profile
$can_edit = false;
if ($user_type === 'staff' && $user_id == $_SESSION['staff_id']) {
    $can_edit = true; // Staff can edit their own profile
} elseif ($user_type !== 'staff') {
    $can_edit = true; // Admin can edit staff and distributor profiles
}

if (!$can_edit) {
    $_SESSION['error'] = "You don't have permission to edit this profile.";
    header("Location: admin_dashboard.php");
    exit();
}

$conn->close();

// Check if profile_image field exists in user data, handle gracefully
$profileImage = 'ASSETS/icon.jpg'; // Default image
// Support different field names for profile image
if ($user_type === 'admin' && !empty($user_data['profile_image'])) {
    $image_path = 'uploads/' . $user_data['profile_image'];
    if (file_exists($image_path)) {
        $profileImage = $image_path;
    }
} elseif ($user_type === 'staff' && !empty($user_data['profile_image'])) {
    $image_path = 'uploads/' . $user_data['profile_image'];
    if (file_exists($image_path)) {
        $profileImage = $image_path;
    }
} elseif ($user_type === 'distributor' && !empty($user_data['profile_image'])) {
    $image_path = 'uploads/' . $user_data['profile_image'];
    if (file_exists($image_path)) {
        $profileImage = $image_path;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit <?= ucfirst($user_type) ?> Profile</title>
    <link rel="stylesheet" type="text/css" href="../CSS/admin_dashboard.css?v=<?= time(); ?>" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
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
                    <div class="container mt-4">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= $_SESSION['success'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $_SESSION['error'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <div class="card shadow-sm">
                            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                <h4>Edit <?= ucfirst($user_type) ?> Profile</h4>
                                <div>
                                    <a href="s_<?= $user_type ?>_list.php" class="btn btn-secondary btn-sm">
                                        <i class="fa fa-arrow-left"></i> Back to List
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <form action="s_update_universal_profile.php" method="POST" enctype="multipart/form-data" id="profileForm">
                                    <input type="hidden" name="user_type" value="<?= $user_type ?>">
                                    <input type="hidden" name="user_id" value="<?= $user_id ?>">

                                    <div class="row">
                                        <!-- Profile Image Section -->
                                        <div class="col-md-4 text-center mb-4">
                                            <img src="<?= $profileImage ?>" alt="Profile" 
                                                 style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #410101;" 
                                                 id="profilePreview">
                                            <div class="mt-3">
                                                <label for="profile_image" class="form-label">Change Profile Image</label>
                                                <input type="file" class="form-control" name="profile_image" id="profile_image" 
                                                       accept="image/*" onchange="previewImage(this)">
                                                <small class="form-text text-muted">Max size: 5MB. Supported: JPG, PNG, GIF</small>
                                            </div>
                                        </div>

                                        <!-- Form Fields Section -->
                                        <div class="col-md-8">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">First Name *</label>
                                                    <?php if ($user_type === 'admin'): ?>
                                                        <input type="text" name="admin_fname" class="form-control" value="<?= htmlspecialchars($user_data['admin_fname'] ?? '') ?>">
                                                    <?php elseif ($user_type === 'staff'): ?>
                                                        <input type="text" name="staff_fname" class="form-control" value="<?= htmlspecialchars($user_data['staff_fname'] ?? '') ?>">
                                                    <?php else: ?>
                                                        <input type="text" name="distrib_fname" class="form-control" value="<?= htmlspecialchars($user_data['distrib_fname'] ?? '') ?>">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Middle Name</label>
                                                    <input type="text" name="<?= $user_type === 'admin' ? 'admin_mname' : ($user_type === 'staff' ? 'staff_mname' : 'distrib_mname') ?>" class="form-control" 
                                                           value="<?= htmlspecialchars($user_type === 'admin' ? ($user_data['admin_mname'] ?? '') : ($user_type === 'staff' ? ($user_data['staff_mname'] ?? '') : ($user_data['distrib_mname'] ?? ''))) ?>">
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Last Name *</label>
                                                    <?php if ($user_type === 'admin'): ?>
                                                        <input type="text" name="admin_lname" class="form-control" value="<?= htmlspecialchars($user_data['admin_lname'] ?? '') ?>">
                                                    <?php elseif ($user_type === 'staff'): ?>
                                                        <input type="text" name="staff_lname" class="form-control" value="<?= htmlspecialchars($user_data['staff_lname'] ?? '') ?>">
                                                    <?php else: ?>
                                                        <input type="text" name="distrib_lname" class="form-control" value="<?= htmlspecialchars($user_data['distrib_lname'] ?? '') ?>">
                                                    <?php endif; ?>
                                                </div>
                                                 <div class="col-md-6 mb-3">
                                                        <label class="form-label">Extension</label>
                                                        <select name="<?= $user_type === 'admin' ? 'admin_extension' : ($user_type === 'staff' ? 'staff_extension' : 'distrib_extension') ?>" class="form-control">
                                                            <option value="" <?= ($user_type === 'admin' ? ($user_data['admin_extension'] ?? '') : ($user_type === 'staff' ? ($user_data['staff_extension'] ?? '') : ($user_data['distrib_extension'] ?? ''))) == '' ? 'selected' : '' ?>>--</option>
                                                            <option value="Jr." <?= ($user_type === 'admin' ? ($user_data['admin_extension'] ?? '') : ($user_type === 'staff' ? ($user_data['staff_extension'] ?? '') : ($user_data['distrib_extension'] ?? ''))) == 'Jr.' ? 'selected' : '' ?>>Jr.</option>
                                                            <option value="Sr." <?= ($user_type === 'admin' ? ($user_data['admin_extension'] ?? '') : ($user_type === 'staff' ? ($user_data['staff_extension'] ?? '') : ($user_data['distrib_extension'] ?? ''))) == 'Sr.' ? 'selected' : '' ?>>Sr.</option>
                                                            <option value="II" <?= ($user_type === 'admin' ? ($user_data['admin_extension'] ?? '') : ($user_type === 'staff' ? ($user_data['staff_extension'] ?? '') : ($user_data['distrib_extension'] ?? ''))) == 'II' ? 'selected' : '' ?>>II</option>
                                                            <option value="III" <?= ($user_type === 'admin' ? ($user_data['admin_extension'] ?? '') : ($user_type === 'staff' ? ($user_data['staff_extension'] ?? '') : ($user_data['distrib_extension'] ?? ''))) == 'III' ? 'selected' : '' ?>>III</option>
                                                            <option value="IV" <?= ($user_type === 'admin' ? ($user_data['admin_extension'] ?? '') : ($user_type === 'staff' ? ($user_data['staff_extension'] ?? '') : ($user_data['distrib_extension'] ?? ''))) == 'IV' ? 'selected' : '' ?>>IV</option>
                                                        </select>
                                                        <small class="form-text text-muted">Choose if applicable (Jr., Sr., II, III, IV)</small>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Email</label>
                                                    <?php if ($user_type === 'admin'): ?>
                                                        <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($user_data['admin_email'] ?? '') ?>">
                                                    <?php elseif ($user_type === 'staff'): ?>
                                                        <input type="email" name="staff_email" class="form-control" value="<?= htmlspecialchars($user_data['staff_email'] ?? '') ?>">
                                                    <?php else: ?>
                                                        <input type="email" name="distrib_email" class="form-control" value="<?= htmlspecialchars($user_data['distrib_email'] ?? '') ?>">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Contact Number</label>
                                                    <input type="text" name="<?= $user_type === 'admin' ? 'admin_contact_number' : ($user_type === 'staff' ? 'staff_contact_number' : 'distrib_contact_number') ?>" class="form-control" 
                                                           value="<?= htmlspecialchars($user_type === 'admin' ? ($user_data['admin_contact_number'] ?? '') : ($user_type === 'staff' ? ($user_data['staff_contact_number'] ?? '') : ($user_data['distrib_contact_number'] ?? ''))) ?>">
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Gender</label>
                                                    <?php 
                                                        // Get gender from the database
                                                        if ($user_type === 'distributor') {
                                                            $gender = isset($user_data['distrib_gender']) ? $user_data['distrib_gender'] : '';
                                                        } else {
                                                            $gender_field = $user_type . '_gender';
                                                            $gender = isset($user_data[$gender_field]) ? $user_data[$gender_field] : '';
                                                        }
                                                        
                                                        // Debug gender value
                                                        echo "<!-- Gender value: " . htmlspecialchars($gender) . " -->";
                                                    ?>
                                                    <select name="<?= $user_type ?>_gender" class="form-control" id="gender_select" required>
                                                        <option value="" <?= empty($gender) ? 'selected' : '' ?>>Select Gender</option>
                                                        <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                                                        <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                                                        <option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
                                                    </select>
                                                    <input type="hidden" id="gender_backup" value="<?= htmlspecialchars($gender) ?>"><?php echo '<!-- Debug Gender: ' . htmlspecialchars($gender) . ' -->'; ?>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Birthday</label>
                                                    <?php 
                                                        // Get birthday from the database
                                                        if ($user_type === 'distributor') {
                                                            $birthday = isset($user_data['distrib_birthday']) ? $user_data['distrib_birthday'] : '';
                                                        } else {
                                                            $birthday_field = $user_type . '_birthday';
                                                            $birthday = isset($user_data[$birthday_field]) ? $user_data[$birthday_field] : '';
                                                        }
                                                        
                                                        // Debug birthday value
                                                        echo "<!-- Birthday value: " . htmlspecialchars($birthday) . " -->";
                                                    ?>
                                                    <input type="date" name="<?= $user_type ?>_birthday" id="birthday_field" class="form-control" 
                                                           value="<?= htmlspecialchars($birthday) ?>" required>
                                                    <input type="hidden" id="birthday_backup" value="<?= htmlspecialchars($birthday) ?>"><?php echo '<!-- Debug Birthday: ' . htmlspecialchars($birthday) . ' -->'; ?>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Age</label>
                                                    <?php 
                                                        // Get age from the database
                                                        if ($user_type === 'distributor') {
                                                            $age = isset($user_data['distrib_age']) ? $user_data['distrib_age'] : '';
                                                        } else {
                                                            $age_field = $user_type . '_age';
                                                            $age = isset($user_data[$age_field]) ? $user_data[$age_field] : '';
                                                        }
                                                        
                                                        // Debug age value
                                                        echo "<!-- Age value: " . htmlspecialchars((string)$age) . " -->";
                                                    ?>
                                                    <input type="number" name="<?= $user_type ?>_age" id="age_field" class="form-control" 
                                                           value="<?= htmlspecialchars((string)$age) ?>" min="1" max="120">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Civil Status</label>
                                                    <?php 
                                                        // Get civil status from the database
                                                        if ($user_type === 'distributor') {
                                                            $civil_status = isset($user_data['distrib_civil_status']) ? $user_data['distrib_civil_status'] : '';
                                                        } else {
                                                            $civil_status_field = $user_type . '_civil_status';
                                                            $civil_status = isset($user_data[$civil_status_field]) ? $user_data[$civil_status_field] : '';
                                                        }
                                                        
                                                        // Debug civil status value
                                                        echo "<!-- Civil Status value: " . htmlspecialchars($civil_status) . " -->";
                                                    ?>
                                                    <select name="<?= $user_type ?>_civil_status" id="civil_status_select" class="form-control" required>
                                                        <option value="" <?= empty($civil_status) ? 'selected' : '' ?>>Select Status</option>
                                                        <option value="Single" <?= $civil_status === 'Single' ? 'selected' : '' ?>>Single</option>
                                                        <option value="Married" <?= $civil_status === 'Married' ? 'selected' : '' ?>>Married</option>
                                                        <option value="Divorced" <?= $civil_status === 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                                                        <option value="Widowed" <?= $civil_status === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                                                    </select>
                                                    <input type="hidden" id="civil_status_backup" value="<?= htmlspecialchars($civil_status) ?>"><?php echo '<!-- Debug Civil Status: ' . htmlspecialchars($civil_status) . ' -->'; ?>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Address</label>
                                                    <?php 
                                                        // Get address from the database
                                                        if ($user_type === 'distributor') {
                                                            $address = isset($user_data['distrib_address']) ? $user_data['distrib_address'] : '';
                                                        } else {
                                                            $address_field = $user_type . '_address';
                                                            $address = isset($user_data[$address_field]) ? $user_data[$address_field] : '';
                                                        }
                                                        
                                                        // Debug address value
                                                        echo "<!-- Address value: " . htmlspecialchars($address) . " -->";
                                                    ?>
                                                    <textarea name="<?= $user_type ?>_address" id="address_field" class="form-control" rows="2"><?= htmlspecialchars($address) ?></textarea>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Outlet/Place <?= $user_type === 'distributor' ? '*' : '' ?></label>
                                                    <?php 
                                                    // Get outlet value from the database and make sure it's well defined
                                                    $outlet_value = '';
                                                    if ($user_type === 'distributor' && !empty($user_data['distrib_outlet'])) {
                                                        $outlet_value = $user_data['distrib_outlet'];
                                                        error_log("Distributor outlet value: " . $outlet_value);
                                                    } elseif ($user_type === 'admin' && !empty($user_data['admin_outlet'])) {
                                                        $outlet_value = $user_data['admin_outlet'];
                                                        error_log("Admin outlet value: " . $outlet_value);
                                                    } elseif ($user_type === 'staff' && !empty($user_data['staff_outlet'])) {
                                                        $outlet_value = $user_data['staff_outlet'];
                                                        error_log("Staff outlet value: " . $outlet_value);
                                                    }
                                                    
                                                    // Additional debugging for outlet value
                                                    if (empty($outlet_value)) {
                                                        error_log("WARNING: Outlet value is empty for {$user_type} ID {$user_id}");
                                                    } else {
                                                        error_log("OUTLET VALUE CONFIRMATION: {$outlet_value}");
                                                    }
                                                    
                                                    echo "<!-- Outlet value from database: " . htmlspecialchars($outlet_value) . " -->";
                                                    ?>
                                                    <!-- Hidden debug display field -->
                                                    <input type="hidden" id="outlet_debug_display" value="<?= htmlspecialchars($outlet_value) ?>">
                                                    
                                                    <!-- Outlet dropdown styled to match the image -->
                                                    <select id="outlet" name="<?= $user_type ?>_outlet" class="form-control" <?= $user_type === 'distributor' ? 'required' : '' ?>>
                                                        <option value="">Select a place in the Philippines</option>
                                                        <?php
                                                        // Only add the current outlet if it seems like a valid place (not a person's name)
                                                        $valid_outlet = !empty($outlet_value) && 
                                                                       (strpos($outlet_value, ' - ') !== false || 
                                                                        strpos($outlet_value, 'Region') !== false || 
                                                                        strpos($outlet_value, 'Province') !== false);
                                                        if ($valid_outlet): 
                                                        ?>
                                                        <option value="<?= htmlspecialchars($outlet_value) ?>" selected><?= htmlspecialchars($outlet_value) ?></option>
                                                        <?php endif; ?>
                                                    </select>
                                                    <small class="form-text text-muted">Select a place in the Philippines<?= $user_type === 'distributor' ? ' (required)' : '' ?></small>
                                                    <div id="outletDebug" style="display: none;"></div>
                                                    
                                                    <script>
                                                    // Add search functionality for outlet dropdown
                                                    document.addEventListener('DOMContentLoaded', function() {
                                                        var outletSearch = document.getElementById('outlet_search');
                                                        var clearSearch = document.getElementById('clear_outlet_search');
                                                        var outletSelect = document.getElementById('outlet');
                                                        var outletDebug = document.getElementById('outletDebug');
                                                        var allOptions = [];
                                                        var searchTimeout;
                                                        
                                                        // Helper function to check if a string is a valid place format
                                                        function isValidPlace(place) {
                                                            if (!place) return false;
                                                            
                                                            // Must contain proper region-province-city format with dashes
                                                            if (place.includes(' - ')) return true;
                                                            
                                                            // Must contain region/province keywords
                                                            if (place.includes('Region') || place.includes('Province')) return true;
                                                            
                                                            // Valid places shouldn't be too short
                                                            if (place.length < 5) return false;
                                                            
                                                            // Should not be someone's name (name likely won't have these keywords)
                                                            var locationKeywords = ['City', 'Municipality', 'Capital', 'Town', 'Province', 'Island'];
                                                            for (var i = 0; i < locationKeywords.length; i++) {
                                                                if (place.includes(locationKeywords[i])) return true;
                                                            }
                                                            
                                                            // Check if it matches known Philippines location patterns
                                                            var philippineLocationPattern = /Metro|Davao|Cebu|Manila|Luzon|Visayas|Mindanao|Palawan|Boracay|Baguio|Iloilo|Zamboanga|Batangas|Quezon|Makati|Tagaytay|Taguig|Pasay|Pasig|Muntinlupa|Cavite|Laguna|Bataan|Pampanga|Bulacan|Nueva|Albay|Camarines|Sorsogon|Ilocos|Pangasinan|Benguet|Cagayan|Tarlac|Zambales|Bataan|Rizal|Leyte|Samar|Bohol|Negros|Panay|Palawan|Marinduque|Romblon|Siquijor|Basilan|Lanao|Maguindanao|Sultan|Sulu|Tawi|Compostela|Cotabato|Agusan|Bukidnon|Misamis|Surigao|Marawi|Cagayan/i;
                                                            if (philippineLocationPattern.test(place)) return true;
                                                            
                                                            // If it passed no checks, it's probably not a valid place
                                                            return false;
                                                        }
                                                        
                                                        // Store the original options once they are loaded
                                                        function storeOriginalOptions() {
                                                            allOptions = Array.from(outletSelect.options).map(opt => {
                                                                return {value: opt.value, text: opt.text, selected: opt.selected};
                                                            });
                                                            console.log("Stored", allOptions.length, "original outlet options");
                                                        }
                                                        
                                                        // First store options after a small delay to allow API to load
                                                        setTimeout(storeOriginalOptions, 1500);
                                                        
                                                        // Filter function for local filtering
                                                        function filterOptions(searchTerm) {
                                                            if (!allOptions.length) {
                                                                storeOriginalOptions(); // Try to store again if not yet available
                                                            }
                                                            
                                                            if (!allOptions.length) {
                                                                console.warn('No options to filter');
                                                                return;
                                                            }
                                                            
                                                            // Get currently selected option
                                                            var currentSelected = outletSelect.value;
                                                            
                                                            // Clear dropdown
                                                            outletSelect.innerHTML = '';
                                                            
                                                            // Add matching options
                                                            var matchCount = 0;
                                                            var searchTermLower = searchTerm.toLowerCase();
                                                            
                                                            // First add the currently selected option if any
                                                            if (currentSelected) {
                                                                var selectedOption = allOptions.find(opt => opt.value === currentSelected);
                                                                if (selectedOption) {
                                                                    var option = new Option(selectedOption.text, selectedOption.value, true, true);
                                                                    outletSelect.add(option);
                                                                    matchCount++;
                                                                }
                                                            }
                                                            
                                                            // Then add filtering matches
                                                            allOptions.forEach(function(opt) {
                                                                // Skip if this is the current selected option (already added)
                                                                if (opt.value === currentSelected) return;
                                                                
                                                                // Check if option matches search
                                                                if (searchTerm === '' || opt.text.toLowerCase().includes(searchTermLower)) {
                                                                    var option = new Option(opt.text, opt.value, false, false);
                                                                    outletSelect.add(option);
                                                                    matchCount++;
                                                                }
                                                            });
                                                            
                                                            return matchCount;
                                                        }
                                                        
                                                        // Search directly from API for better results
                                                        function searchFromAPI(searchTerm) {
                                                            if (!searchTerm || searchTerm.length < 3) {
                                                                return filterOptions(searchTerm);
                                                            }
                                                            
                                                            // Show loading indicator
                                                            if (outletDebug) {
                                                                outletDebug.style.display = 'block';
                                                                outletDebug.innerHTML = '<div class="text-muted">Searching places...</div>';
                                                            }
                                                            
                                                            // Call the Philippine places API with search parameter
                                                            fetch(`../philippine_places_api.php?search=${encodeURIComponent(searchTerm)}`)
                                                                .then(response => {
                                                                    if (!response.ok) {
                                                                        throw new Error('Search failed');
                                                                    }
                                                                    return response.json();
                                                                })
                                                                .then(data => {
                                                                    // Get currently selected option to preserve it
                                                                    var currentSelected = outletSelect.value;
                                                                    
                                                                    // Clear dropdown except for the current selection
                                                                    outletSelect.innerHTML = '';
                                                                    
                                                                    // Add current selection first if any
                                                                    if (currentSelected) {
                                                                        var selectedOption = allOptions.find(opt => opt.value === currentSelected);
                                                                        if (selectedOption) {
                                                                            var option = new Option(selectedOption.text, selectedOption.value, true, true);
                                                                            outletSelect.add(option);
                                                                        }
                                                                    }
                                                                    
                                                                    // Process search results
                                                                    var matchCount = 0;
                                                                    
                                                                    // Add matching options from search results
                                                                    if (Array.isArray(data)) {
                                                                        data.forEach(function(region) {
                                                                            var regionName = region.region;
                                                                            
                                                                            region.provinces.forEach(function(province) {
                                                                                var provinceName = province.province;
                                                                                
                                                                                province.cities.forEach(function(city) {
                                                                                    var optText = `${regionName} - ${provinceName} - ${city}`;
                                                                                    
                                                                                    // Skip if this is the current selected option (already added)
                                                                                    if (optText === currentSelected) return;
                                                                                    
                                                                                    var option = new Option(optText, optText, false, false);
                                                                                    outletSelect.add(option);
                                                                                    matchCount++;
                                                                                });
                                                                            });
                                                                        });
                                                                    }
                                                                    
                                                                    // Update debug info
                                                                    if (outletDebug) {
                                                                        if (matchCount > 0) {
                                                                            outletDebug.innerHTML = `<small class="text-success">Found ${matchCount} matches for "${searchTerm}"</small>`;
                                                                        } else {
                                                                            outletDebug.innerHTML = `<small class="text-muted">No places found matching "${searchTerm}"</small>`;
                                                                        }
                                                                        
                                                                        // Hide after 3 seconds
                                                                        setTimeout(function() {
                                                                            outletDebug.style.display = 'none';
                                                                        }, 3000);
                                                                    }
                                                                })
                                                                .catch(error => {
                                                                    console.error('Search API error:', error);
                                                                    // Fall back to local filtering
                                                                    filterOptions(searchTerm);
                                                                    
                                                                    if (outletDebug) {
                                                                        outletDebug.innerHTML = '<small class="text-danger">Search failed. Using local filtering.</small>';
                                                                        // Hide after 3 seconds
                                                                        setTimeout(function() {
                                                                            outletDebug.style.display = 'none';
                                                                        }, 3000);
                                                                    }
                                                                });
                                                        }
                                                        
                                                        // Search event handler with debounce
                                                        if (outletSearch) {
                                                            outletSearch.addEventListener('input', function() {
                                                                clearTimeout(searchTimeout);
                                                                const searchTerm = this.value.trim();
                                                                
                                                                // For empty or very short search, use immediate local filtering
                                                                if (!searchTerm || searchTerm.length < 2) {
                                                                    filterOptions(searchTerm);
                                                                    if (outletDebug) outletDebug.style.display = 'none';
                                                                    return;
                                                                }
                                                                
                                                                // For longer searches, debounce and use API
                                                                searchTimeout = setTimeout(function() {
                                                                    searchFromAPI(searchTerm);
                                                                }, 300);
                                                            });
                                                        }
                                                        
                                                        // Clear search button
                                                        if (clearSearch) {
                                                            clearSearch.addEventListener('click', function() {
                                                                if (outletSearch) outletSearch.value = '';
                                                                filterOptions('');
                                                                if (outletDebug) outletDebug.style.display = 'none';
                                                            });
                                                        }
                                                    });
                                                    </script>
                                                    <?php if($user_type === 'distributor'): ?>
                                                    <input type="hidden" name="outlet_debug" value="<?= htmlspecialchars($user_data['distrib_outlet'] ?? '') ?>">
                                                    <?php else: ?>
                                                    <input type="hidden" name="outlet_debug" value="<?= htmlspecialchars($outlet_value) ?>">
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Username</label>
                                                    <?php
                                                    // Get username from the database
                                                    $username_value = '';
                                                    if ($user_type === 'distributor' && isset($user_data['distrib_username'])) {
                                                        $username_value = $user_data['distrib_username'];
                                                    } elseif ($user_type === 'admin' && isset($user_data['admin_username'])) {
                                                        $username_value = $user_data['admin_username'];
                                                    } elseif ($user_type === 'staff' && isset($user_data['staff_username'])) {
                                                        $username_value = $user_data['staff_username'];
                                                    }
                                                    echo "<!-- Username value: " . htmlspecialchars($username_value) . " -->";
                                                    ?>
                                                    <input type="text" name="<?= $user_type ?>_username" class="form-control" 
                                                           value="<?= htmlspecialchars($username_value) ?>" id="username_field">
                                                    <?php if($user_type === 'distributor'): ?>
                                                    <input type="hidden" id="username_debug" name="username_debug" value="<?= htmlspecialchars($username_value) ?>">
                                                    <small class="form-text text-muted">Current username: <span id="username_display"><?= htmlspecialchars($username_value) ?></span></small>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Role</label>
                                                    <input type="text" name="admin_role" class="form-control" 
                                                           value="<?= htmlspecialchars($user_data['role'] ?? ucfirst($user_type)) ?>" readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center mt-4">
                                        <button type="submit" class="btn btn-primary btn-lg" id="saveButton">
                                            <i class="fa fa-save"></i> Save Changes
                                        </button>
                                        <a href="s_<?= $user_type ?>_list.php" class="btn btn-secondary btn-lg ms-2">
                                            <i class="fa fa-times"></i> Cancel
                                        </a>
                                    </div>
                                    
                                    <?php if ($user_type === 'distributor'): ?>
                                    <!-- Add hidden backup fields for distributor data -->
                                    <input type="hidden" name="distrib_outlet_backup" value="<?= htmlspecialchars($user_data['distrib_outlet'] ?? '') ?>">
                                    <input type="hidden" name="distrib_gender_backup" value="<?= htmlspecialchars($user_data['distrib_gender'] ?? '') ?>">
                                    <input type="hidden" name="distrib_birthday_backup" value="<?= htmlspecialchars($user_data['distrib_birthday'] ?? '') ?>">
                                    <input type="hidden" name="distrib_age_backup" value="<?= htmlspecialchars($user_data['distrib_age'] ?? '') ?>">
                                    <input type="hidden" name="distrib_civil_status_backup" value="<?= htmlspecialchars($user_data['distrib_civil_status'] ?? '') ?>">
                                    <input type="hidden" name="distrib_address_backup" value="<?= htmlspecialchars($user_data['distrib_address'] ?? '') ?>">
                                    <input type="hidden" name="outlet_debug" value="<?= htmlspecialchars($user_data['distrib_outlet'] ?? '') ?>">
                                    <input type="hidden" name="distrib_username_backup" value="<?= htmlspecialchars($user_data['distrib_username'] ?? '') ?>">
                                    <input type="hidden" name="distrib_extension_backup" value="<?= htmlspecialchars($user_data['distrib_extension'] ?? '') ?>">
                                    <input type="hidden" name="distrib_email_backup" value="<?= htmlspecialchars($user_data['distrib_email'] ?? '') ?>">
                                    <input type="hidden" name="distrib_contact_number_backup" value="<?= htmlspecialchars($user_data['distrib_contact_number'] ?? '') ?>">
                                    
                                    <script>
                                        // Ensure we have the current outlet and username values in hidden fields
                                        var currentOutletValue = "<?= htmlspecialchars($user_data['distrib_outlet'] ?? '') ?>";
                                        var currentUsernameValue = "<?= htmlspecialchars($user_data['distrib_username'] ?? '') ?>";
                                        
                                        // Log all form fields for debug purposes
                                        console.log("Current form data:", {
                                            outlet: currentOutletValue,
                                            username: currentUsernameValue,
                                            gender: "<?= htmlspecialchars($user_data['distrib_gender'] ?? '') ?>",
                                            birthday: "<?= htmlspecialchars($user_data['distrib_birthday'] ?? '') ?>",
                                            age: "<?= htmlspecialchars($user_data['distrib_age'] ?? '') ?>",
                                            civilStatus: "<?= htmlspecialchars($user_data['distrib_civil_status'] ?? '') ?>",
                                            address: "<?= htmlspecialchars($user_data['distrib_address'] ?? '') ?>"
                                        });
                                        
                                        // Handle username field monitoring
                                        var usernameField = document.getElementById('username_field');
                                        var usernameDebug = document.getElementById('username_debug');
                                        var usernameDisplay = document.getElementById('username_display');
                                        
                                        if (usernameField && usernameDebug) {
                                            usernameField.addEventListener('change', function() {
                                                var newValue = this.value.trim();
                                                console.log("Username field changed to:", newValue);
                                                
                                                // Update debug field
                                                if (usernameDebug) {
                                                    usernameDebug.value = newValue || currentUsernameValue;
                                                }
                                                
                                                // Update display
                                                if (usernameDisplay) {
                                                    usernameDisplay.textContent = newValue || currentUsernameValue;
                                                }
                                            });
                                        }
                                        
                                        // Additional validation for distributor form submission
                                        document.getElementById('profileForm').addEventListener('submit', function(e) {
                                            var outletSelect = document.getElementById('outlet');
                                            var outletDebugDisplay = document.getElementById('outlet_debug_display');
                                            var outletDebugField = document.querySelector('input[name="outlet_debug"]');
                                            
                                            console.log("Form submitting with outlet value:", outletSelect.value);
                                            
                                            // Before submission, check and fix critical fields
                                            var genderSelect = document.getElementById('gender_select');
                                            var birthdayField = document.getElementById('birthday_field');
                                            var civilStatusSelect = document.getElementById('civil_status_select');
                                            var usernameField = document.getElementById('username_field');
                                            
                                            // Create a hidden field to store the current outlet value
                                            var manualOutletField = document.getElementById('manual_outlet_value');
                                            if (!manualOutletField) {
                                                manualOutletField = document.createElement('input');
                                                manualOutletField.type = 'hidden';
                                                manualOutletField.name = 'manual_outlet_value';
                                                manualOutletField.id = 'manual_outlet_value';
                                                this.appendChild(manualOutletField);
                                            }
                                            
                                            // Log values being submitted
                                            console.log("Submitting values:", {
                                                gender: genderSelect ? genderSelect.value : 'not found',
                                                birthday: birthdayField ? birthdayField.value : 'not found',
                                                civilStatus: civilStatusSelect ? civilStatusSelect.value : 'not found',
                                                outlet: outletSelect ? outletSelect.value : 'not found',
                                                username: usernameField ? usernameField.value : 'not found'
                                            });
                                            
                                            // Check outlet field with multiple fallback mechanisms
                                            var outletValue = '';
                                            
                                            // Priority 1: Selected value in dropdown
                                            if (outletSelect && outletSelect.value && outletSelect.value.trim() !== '') {
                                                outletValue = outletSelect.value;
                                                console.log("Using selected outlet value:", outletValue);
                                            } 
                                            // Priority 2: Debug display field
                                            else if (outletDebugDisplay && outletDebugDisplay.value && outletDebugDisplay.value.trim() !== '') {
                                                outletValue = outletDebugDisplay.value;
                                                console.log("Using outlet debug display value:", outletValue);
                                                
                                                // Set the dropdown value by adding an option and selecting it
                                                if (outletSelect) {
                                                    // Preserve the default option
                                                    var defaultOption = '<option value="">Select a place in the Philippines</option>';
                                                    outletSelect.innerHTML = defaultOption + 
                                                        `<option value="${outletValue}" selected>${outletValue}</option>`;
                                                }
                                            } 
                                            // Priority 3: Debug hidden field
                                            else if (outletDebugField && outletDebugField.value && outletDebugField.value.trim() !== '') {
                                                outletValue = outletDebugField.value;
                                                console.log("Using outlet debug field value:", outletValue);
                                                
                                                // Set the dropdown value by adding an option and selecting it
                                                if (outletSelect) {
                                                    // Preserve the default option
                                                    var defaultOption = '<option value="">Select a place in the Philippines</option>';
                                                    outletSelect.innerHTML = defaultOption + 
                                                        `<option value="${outletValue}" selected>${outletValue}</option>`;
                                                }
                                            }
                                            // Priority 4: Current outlet from JS variable
                                            else if (currentOutletValue && currentOutletValue.trim() !== '') {
                                                outletValue = currentOutletValue;
                                                console.log("Using currentOutletValue variable:", outletValue);
                                                
                                                // Set the dropdown value by adding an option and selecting it
                                                if (outletSelect) {
                                                    // Preserve the default option
                                                    var defaultOption = '<option value="">Select a place in the Philippines</option>';
                                                    outletSelect.innerHTML = defaultOption + 
                                                        `<option value="${outletValue}" selected>${outletValue}</option>`;
                                                }
                                            }
                                            
                                            // If we found an outlet value, make sure it's stored in all relevant places
                                            if (outletValue) {
                                                // Update manual outlet field for submission
                                                if (manualOutletField) {
                                                    manualOutletField.value = outletValue;
                                                }
                                                
                                                // Update debug field if it exists
                                                if (outletDebugField) {
                                                    outletDebugField.value = outletValue;
                                                }
                                                
                                                // Update debug display if it exists
                                                if (outletDebugDisplay) {
                                                    outletDebugDisplay.value = outletValue;
                                                }
                                            }
                                            // If still no outlet and this is a distributor, prevent submission
                                            else if (<?= $user_type === 'distributor' ? 'true' : 'false' ?>) {
                                                e.preventDefault();
                                                alert('Please select an outlet. It is required for distributors.');
                                                if (outletSelect) outletSelect.focus();
                                                return;
                                            }
                                            
                                            // Check username field
                                            if (usernameField && (!usernameField.value || usernameField.value.trim() === '')) {
                                                if (currentUsernameValue && currentUsernameValue.trim() !== '') {
                                                    console.log("Using backup username value:", currentUsernameValue);
                                                    usernameField.value = currentUsernameValue;
                                                }
                                            }
                                        });
                                    </script>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Ensure saved data is properly displayed when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log("DOM loaded, setting up form values");
        
        // Debug HTML element IDs
        console.log("Elements found:", {
            gender: document.getElementById('gender_select') ? "Yes" : "No",
            birthday: document.getElementById('birthday_field') ? "Yes" : "No",
            age: document.getElementById('age_field') ? "Yes" : "No",
            civilStatus: document.getElementById('civil_status_select') ? "Yes" : "No", 
            address: document.getElementById('address_field') ? "Yes" : "No"
        });
        
        // Get current values directly from the database
        // Use variables directly from PHP
        const dbGender = "<?= $user_type === 'distributor' ? addslashes($user_data['distrib_gender'] ?? '') : addslashes($user_data[$user_type.'_gender'] ?? '') ?>";
        const dbBirthday = "<?= $user_type === 'distributor' ? addslashes($user_data['distrib_birthday'] ?? '') : addslashes($user_data[$user_type.'_birthday'] ?? '') ?>";
        const dbAge = "<?= $user_type === 'distributor' ? addslashes((string)($user_data['distrib_age'] ?? '')) : addslashes((string)($user_data[$user_type.'_age'] ?? '')) ?>";
        const dbCivilStatus = "<?= $user_type === 'distributor' ? addslashes($user_data['distrib_civil_status'] ?? '') : addslashes($user_data[$user_type.'_civil_status'] ?? '') ?>";
        const dbAddress = "<?= $user_type === 'distributor' ? addslashes($user_data['distrib_address'] ?? '') : addslashes($user_data[$user_type.'_address'] ?? '') ?>";
        
        console.log("DB Values:", {
            gender: dbGender,
            birthday: dbBirthday,
            age: dbAge,
            civilStatus: dbCivilStatus,
            address: dbAddress
        });
        
        // Direct DOM manipulation to set values - most reliable method
        
        // Gender field
        if (document.getElementById('gender_select')) {
            const genderSelect = document.getElementById('gender_select');
            const genderBackup = document.getElementById('gender_backup').value;
            const genderToUse = dbGender || genderBackup;
            
            if (genderToUse) {
                console.log('Setting gender to:', genderToUse);
                // Try to find and select the matching option
                for (let i = 0; i < genderSelect.options.length; i++) {
                    if (genderSelect.options[i].value === genderToUse) {
                        genderSelect.selectedIndex = i;
                        console.log('Set gender select to:', genderToUse);
                        break;
                    }
                }
            } else {
                // Default to Male if no value is found
                console.log('No gender value found, defaulting to Male');
                for (let i = 0; i < genderSelect.options.length; i++) {
                    if (genderSelect.options[i].value === 'Male') {
                        genderSelect.selectedIndex = i;
                        break;
                    }
                }
            }
            
            // Make sure gender is not empty
            if (genderSelect.value === '') {
                console.log('Gender still empty, selecting first non-empty option');
                for (let i = 0; i < genderSelect.options.length; i++) {
                    if (genderSelect.options[i].value !== '') {
                        genderSelect.selectedIndex = i;
                        break;
                    }
                }
            }
        }
        
        // Birthday field
        if (document.getElementById('birthday_field')) {
            const birthdayField = document.getElementById('birthday_field');
            const birthdayBackup = document.getElementById('birthday_backup').value;
            const birthdayToUse = dbBirthday || birthdayBackup;
            
            if (birthdayToUse) {
                console.log('Setting birthday to:', birthdayToUse);
                birthdayField.value = birthdayToUse;
                
                // Force setting as attribute as well for stubborn browsers
                birthdayField.setAttribute('value', birthdayToUse);
                console.log('Set birthday field to:', birthdayToUse);
            } else {
                // Default to today's date if no value is found
                const today = new Date().toISOString().split('T')[0];
                console.log('No birthday value found, defaulting to today:', today);
                birthdayField.value = today;
                birthdayField.setAttribute('value', today);
            }
        }
        
        // Age field
        if (document.getElementById('age_field')) {
            const ageField = document.getElementById('age_field');
            if (dbAge) {
                ageField.value = dbAge;
                console.log('Set age field to:', dbAge);
                
                // Force setting as attribute as well
                ageField.setAttribute('value', dbAge);
            }
        }
        
        // Civil Status field
        if (document.getElementById('civil_status_select')) {
            const civilStatusSelect = document.getElementById('civil_status_select');
            const civilStatusBackup = document.getElementById('civil_status_backup').value;
            const civilStatusToUse = dbCivilStatus || civilStatusBackup;
            
            if (civilStatusToUse) {
                console.log('Setting civil status to:', civilStatusToUse);
                // Try to find and select the matching option
                for (let i = 0; i < civilStatusSelect.options.length; i++) {
                    if (civilStatusSelect.options[i].value === civilStatusToUse) {
                        civilStatusSelect.selectedIndex = i;
                        console.log('Set civil status select to:', civilStatusToUse);
                        break;
                    }
                }
            } else {
                // Default to Single if no value is found
                console.log('No civil status value found, defaulting to Single');
                for (let i = 0; i < civilStatusSelect.options.length; i++) {
                    if (civilStatusSelect.options[i].value === 'Single') {
                        civilStatusSelect.selectedIndex = i;
                        break;
                    }
                }
            }
            
            // Make sure civil status is not empty
            if (civilStatusSelect.value === '') {
                console.log('Civil status still empty, selecting first non-empty option');
                for (let i = 0; i < civilStatusSelect.options.length; i++) {
                    if (civilStatusSelect.options[i].value !== '') {
                        civilStatusSelect.selectedIndex = i;
                        break;
                    }
                }
            }
        }
        
        // Address field
        if (document.getElementById('address_field')) {
            const addressField = document.getElementById('address_field');
            if (dbAddress) {
                addressField.value = dbAddress;
                console.log('Set address field to:', dbAddress);
                addressField.innerHTML = dbAddress; // For textareas, also set innerHTML
            }
        }
        
        // Auto-calculate age from birthday if birthday is set
        calculateAgeFromBirthday();
        
        // Add a small delay and recheck (helps with some browser race conditions)
        setTimeout(function() {
            console.log("Rechecking form values after delay");
            
            // Check current values after setting
            if (document.getElementById('gender_select'))
                console.log("Gender value now:", document.getElementById('gender_select').value);
            if (document.getElementById('birthday_field'))
                console.log("Birthday value now:", document.getElementById('birthday_field').value);
            if (document.getElementById('age_field'))
                console.log("Age value now:", document.getElementById('age_field').value);
            if (document.getElementById('civil_status_select'))
                console.log("Civil status value now:", document.getElementById('civil_status_select').value);
            if (document.getElementById('address_field'))
                console.log("Address value now:", document.getElementById('address_field').value);
        }, 500);
    });
    
    // Function to calculate age from birthday
    function calculateAgeFromBirthday() {
        const birthdayField = document.getElementById('birthday_field');
        const ageField = document.getElementById('age_field');
        
        if (birthdayField && ageField && birthdayField.value) {
            const today = new Date();
            const birthDate = new Date(birthdayField.value);
            
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            if (age >= 0) {
                ageField.value = age;
                console.log('Calculated age:', age);
            }
        }
    }
    
    // Add event listener to birthday field to auto-calculate age
    if (document.getElementById('birthday_field')) {
        document.getElementById('birthday_field').addEventListener('change', calculateAgeFromBirthday);
    }
    
    // Fetch Philippine places from API and populate the Outlet dropdown as 'Region - Province - City'
    fetch('../philippine_places_api.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            console.log("Philippine places API data received:", data);
            var select = document.getElementById('outlet');
            var outletDebugDisplay = document.getElementById('outlet_debug_display');
            
            if (!select) {
                console.error("Outlet select element not found!");
                return;
            }
            
            // Style the select element to match the image
            select.classList.add('form-select');
            select.style.height = 'auto';
            select.style.padding = '.375rem .75rem';
            select.style.borderRadius = '4px';
            select.style.borderColor = '#ced4da';
            select.style.marginBottom = '10px';
            
            // Get current outlet value with fallback for any user type
            var currentOutlet = "";
            <?php if ($user_type === 'distributor'): ?>
            currentOutlet = "<?= addslashes($user_data['distrib_outlet'] ?? '') ?>";
            <?php elseif ($user_type === 'admin'): ?>
            currentOutlet = "<?= addslashes($user_data['admin_outlet'] ?? '') ?>";
            <?php elseif ($user_type === 'staff'): ?>
            currentOutlet = "<?= addslashes($user_data['staff_outlet'] ?? '') ?>";
            <?php endif; ?>
            
            console.log("Current outlet value:", currentOutlet);
            
            // Always display the current outlet value in the debug display
            if (outletDebugDisplay) {
                outletDebugDisplay.value = currentOutlet;
                console.log("Set outlet debug display to:", currentOutlet);
            }
            
            // Update hidden outlet_debug field to ensure we have the current value
            var outletDebugField = document.querySelector('input[name="outlet_debug"]');
            if (outletDebugField) {
                outletDebugField.value = currentOutlet;
            }
            
            // Update backup field if it exists
            var outletBackupField = document.querySelector('input[name="distrib_outlet_backup"]');
            if (outletBackupField) {
                outletBackupField.value = currentOutlet;
            }
            
            // Ensure the form validates correctly - prevent submission if outlet is empty
            if (<?= $user_type === 'distributor' ? 'true' : 'false' ?>) {
                document.querySelector('form').addEventListener('submit', function(e) {
                    // Check outlet value from all possible sources
                    var outletValue = select.value || 
                                     (outletDebugField ? outletDebugField.value : '') || 
                                     (outletBackupField ? outletBackupField.value : '') || 
                                     '';
                    
                    // Validate if the outlet value is a proper place format (Region - Province - City)
                    var isValidPlace = function(place) {
                        return place && 
                              (place.includes(' - ') || 
                               place.includes('Region') || 
                               place.includes('Province'));
                    };
                    
                    // If the value doesn't look like a place, check if we have a selected option in the dropdown
                    if (!isValidPlace(outletValue) && select.selectedIndex > 0) {
                        outletValue = select.options[select.selectedIndex].value;
                        console.log("Using selected dropdown option instead:", outletValue);
                    }
                    
                    console.log("Form submit - final outlet value:", outletValue);
                    
                    if (!outletValue || outletValue.trim() === '') {
                        e.preventDefault();
                        alert('Outlet is required for distributors.');
                        select.focus();
                    } else {
                        // Ensure the value is in the select
                        var found = false;
                        for (var i = 0; i < select.options.length; i++) {
                            if (select.options[i].value === outletValue) {
                                found = true;
                                select.selectedIndex = i;
                                break;
                            }
                        }
                        
                        // If not found, add it
                        if (!found) {
                            select.innerHTML += `<option value="${outletValue}" selected>${outletValue}</option>`;
                        }
                    }
                });
            }
            
            var options = [];
            if (Array.isArray(data)) {
                // Process the nested structure: regions > provinces > cities
                data.forEach(function(region) {
                    var regionName = region.region;
                    
                    // Each region has provinces
                    region.provinces.forEach(function(province) {
                        var provinceName = province.province;
                        
                        // Each province has cities/municipalities
                        province.cities.forEach(function(city) {
                            // Format as "Region - Province - City"
                            var optText = `${regionName} - ${provinceName} - ${city}`;
                            options.push({ value: optText, label: optText });
                        });
                    });
                });
                
                console.log(`Generated ${options.length} place options from Philippine Places API`);
            } else {
                console.error("API data is not in the expected format:", data);
            }
            
            if (select) {
                // Preserve existing selection first
                var existingSelection = select.value;
                console.log("Preserving existing selection:", existingSelection);
                
                // Clear the select
                select.innerHTML = '';
                
                // For non-distributors, add an empty option
                if (<?= $user_type === 'distributor' ? 'false' : 'true' ?>) {
                    select.innerHTML = '<option value="">--</option>';
                }
                
            // Determine the outlet to use, prioritizing current DB value
            var outletToUse = '';
            
            // Validate if the current outlet looks like a Philippine place (should contain ' - ' separator)
            var isValidPlace = function(place) {
                return place && 
                      (place.includes(' - ') || 
                       place.includes('Region') || 
                       place.includes('Province'));
            };
            
            if (currentOutlet && currentOutlet.trim() !== '' && isValidPlace(currentOutlet)) {
                outletToUse = currentOutlet;
                console.log("Using database outlet value:", outletToUse);
            } else if (existingSelection && existingSelection.trim() !== '' && isValidPlace(existingSelection)) {
                outletToUse = existingSelection;
                console.log("Using existing selection as outlet:", outletToUse);
            } else {
                console.log("Current outlet value doesn't look like a place, ignoring:", currentOutlet);
            }
            
            // Start with default empty option
            select.innerHTML = '<option value="">Select a place in the Philippines</option>';
            
            // Add the outlet value if available and valid
            if (outletToUse) {
                if (isValidPlace(outletToUse)) {
                    select.innerHTML += `<option value="${outletToUse}" selected>${outletToUse}</option>`;
                    console.log("Added outlet as selected option:", outletToUse);
                } else {
                    console.log("Current outlet value appears to be invalid:", outletToUse);
                    // Show a message to the user
                    if (outletDebug) {
                        outletDebug.style.display = 'block';
                        outletDebug.innerHTML = `<div class="alert alert-warning mt-2">The current value "${outletToUse}" doesn't appear to be a valid place. Please select a new location.</div>`;
                        
                        // Hide after 10 seconds
                        setTimeout(function() {
                            outletDebug.style.display = 'none';
                        }, 10000);
                    }
                }
            }                // Add all options from API
                var foundMatch = false;
                
                // Group options by region for better organization
                var regionGroups = {};
                
                options.forEach(function(opt) {
                    // Extract region from the option value (first part of "Region - Province - City")
                    var region = opt.value.split(' - ')[0];
                    
                    if (!regionGroups[region]) {
                        regionGroups[region] = [];
                    }
                    
                    // Don't add duplicates - check against our determined outletToUse
                    if (opt.value === outletToUse) {
                        foundMatch = true;
                        return; // Skip this iteration since we already added it as selected option
                    }
                    
                    regionGroups[region].push(opt);
                });
                
                // Sort regions alphabetically
                var sortedRegions = Object.keys(regionGroups).sort();
                
                // Add options by region
                sortedRegions.forEach(function(region) {
                    // Sort options within each region
                    regionGroups[region].sort(function(a, b) {
                        return a.label.localeCompare(b.label);
                    });
                    
                    // Add each option
                    regionGroups[region].forEach(function(opt) {
                        select.innerHTML += `<option value="${opt.value}">${opt.label}</option>`;
                    });
                });
                
                // Log whether we found a match in the API data
                console.log("Found outlet in API data:", foundMatch ? "Yes" : "No");
                
                // For distributors, if no selection and no current value, select the first option
                if (<?= $user_type === 'distributor' ? 'true' : 'false' ?>) {
                    if (!currentOutlet && !existingSelection && select.options.length > 0) {
                        select.selectedIndex = 0;
                        console.log("Auto-selected first option for distributor:", select.value);
                    }
                    
                    // Always update the outlet debug field when selection changes
                    select.addEventListener('change', function() {
                        console.log("Outlet changed to:", this.value);
                        if (outletDebugDisplay) {
                            outletDebugDisplay.value = this.value;
                        }
                        if (outletDebugField) {
                            outletDebugField.value = this.value;
                        }
                        if (outletBackupField) {
                            outletBackupField.value = this.value;
                        }
                    });
                }
                
                // Final check - ensure we have a selection for distributors
                if (<?= $user_type === 'distributor' ? 'true' : 'false' ?>) {
                    console.log("Final outlet dropdown state:", {
                        value: select.value,
                        options: select.options.length,
                        selectedIndex: select.selectedIndex,
                        debugValue: outletDebugDisplay ? outletDebugDisplay.value : 'N/A',
                        hiddenValue: outletDebugField ? outletDebugField.value : 'N/A',
                        backupValue: outletBackupField ? outletBackupField.value : 'N/A'
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error fetching Philippine places:', error);
            
            // Fallback in case API fails - just use current outlet value
            var select = document.getElementById('outlet');
            var currentOutlet = "";
            <?php if ($user_type === 'distributor'): ?>
            currentOutlet = "<?= addslashes($user_data['distrib_outlet'] ?? '') ?>";
            <?php elseif ($user_type === 'admin'): ?>
            currentOutlet = "<?= addslashes($user_data['admin_outlet'] ?? '') ?>";
            <?php elseif ($user_type === 'staff'): ?>
            currentOutlet = "<?= addslashes($user_data['staff_outlet'] ?? '') ?>";
            <?php endif; ?>
            
            if (select) {
                // Clear any existing options
                select.innerHTML = '';
                
                // Add a default option
                select.innerHTML = '<option value="">Select a place in the Philippines</option>';
                
                // Add the current outlet if available
                if (currentOutlet) {
                    select.innerHTML += `<option value="${currentOutlet}" selected>${currentOutlet}</option>`;
                    console.log("API failed: Using saved outlet value:", currentOutlet);
                }
                
                // Add a few common places manually as fallback
                const commonPlaces = [
                    "National Capital Region - Metro Manila - Manila",
                    "National Capital Region - Metro Manila - Quezon City",
                    "CALABARZON - Cavite - Tagaytay",
                    "CALABARZON - Laguna - Santa Rosa",
                    "Central Visayas - Cebu - Cebu City",
                    "Davao Region - Davao City - Davao City",
                    "Bangsamoro Autonomous Region - Tawi-Tawi - Bongao" // Added this to match the screenshot
                ];
                
                commonPlaces.forEach(place => {
                    // Don't duplicate current outlet
                    if (place !== currentOutlet) {
                        select.innerHTML += `<option value="${place}">${place}</option>`;
                    }
                });
                
                console.log("API failed: Added fallback common places");
                
                // Style the select to match the image
                select.classList.add('form-select');
                select.style.height = 'auto';
                select.style.padding = '.375rem .75rem';
                select.style.borderRadius = '4px';
                select.style.borderColor = '#ced4da';
                select.style.marginBottom = '10px';
            } else {
                console.error("API failed and outlet select element not found");
            }
            
            // Show error message to user
            const outletDebug = document.getElementById('outletDebug');
            if (outletDebug) {
                outletDebug.style.display = 'block';
                outletDebug.innerHTML = '<div class="alert alert-warning mt-2 small">Unable to load complete list of places. Limited options available.</div>';
                
                // Hide after 5 seconds
                setTimeout(() => {
                    outletDebug.style.display = 'none';
                }, 5000);
            }
        });
    </script>
    
    <script>
        // Sidebar functionality with proper variable declarations
        var sideBarIsOpen = true;
        var toggleBtn = document.getElementById('toggleBtn');
        var dashboard_sidebar = document.getElementById('dashboard_sidebar');
        var dashboard_content_container = document.getElementById('dashboard_content_container');
        var dashboard_logo = document.getElementById('dashboard_logo');
        var userImage = document.getElementById('userImage');
        var userName = document.getElementById('userName');

        if (toggleBtn) {
            toggleBtn.addEventListener("click", (event) => {
                event.preventDefault();

                if (sideBarIsOpen) {
                    if (dashboard_sidebar) dashboard_sidebar.style.width = '8%';
                    if (dashboard_content_container) dashboard_content_container.style.width = '92%';
                    if (dashboard_logo) dashboard_logo.style.fontSize = '30px';
                    if (userImage) userImage.style.width = '70px';
                    if (userName) userName.style.fontSize = '15px';

                    let menuIcons = document.getElementsByClassName('menuText');
                    for (let i = 0; i < menuIcons.length; i++) {
                        menuIcons[i].style.display = 'none';
                    }
                    let menuList = document.getElementsByClassName('dashboard_menu_list')[0];
                    if (menuList) menuList.style.textAlign = 'center';
                    sideBarIsOpen = false;
                } else {
                    if (dashboard_sidebar) dashboard_sidebar.style.width = '20%';
                    if (dashboard_content_container) dashboard_content_container.style.width = '80%';
                    if (dashboard_logo) dashboard_logo.style.fontSize = '50px';
                    if (userImage) userImage.style.width = '70px';
                    if (userName) userName.style.fontSize = '15px';

                    let menuIcons = document.getElementsByClassName('menuText');
                    for (let i = 0; i < menuIcons.length; i++) {
                        menuIcons[i].style.display = 'inline-block';
                    }
                    let menuList = document.getElementsByClassName('dashboard_menu_list')[0];
                    if (menuList) menuList.style.textAlign = 'left';
                    sideBarIsOpen = true;
                }
            });
        }

        // Sub menu functionality
        document.addEventListener('click', function (e) {
            let clickedElement = e.target;

            if (clickedElement.classList.contains('showHideSubMenu')) {
                let subMenu = clickedElement.closest('li').querySelector('.subMenus');
                let mainMenuIcon = clickedElement.closest('li').querySelector('.mainMenuIconArrow');

                let subMenus = document.querySelectorAll('.subMenus');
                subMenus.forEach((sub) => {
                    if (subMenu !== sub) sub.style.display = 'none';
                });

                if (subMenu != null) {
                    if (subMenu.style.display === 'block') {
                        subMenu.style.display = 'none';
                        mainMenuIcon.classList.remove('fa-angle-down');
                        mainMenuIcon.classList.remove('fa-angle-left');
                    } else {
                        subMenu.style.display = 'block';
                        mainMenuIcon.classList.remove('fa-angle-left');
                        mainMenuIcon.classList.remove('fa-angle-down');
                    }
                }
            }
        });

        // Image preview functionality with error handling
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Check file size (5MB = 5 * 1024 * 1024 bytes)
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({
                        title: 'File Too Large',
                        text: 'Please select an image smaller than 5MB.',
                        icon: 'error',
                        confirmButtonColor: '#410101'
                    });
                    input.value = '';
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({
                        title: 'Invalid File Type',
                        text: 'Please select a JPEG, PNG, or GIF image.',
                        icon: 'error',
                        confirmButtonColor: '#410101'
                    });
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }


        // Auto-calculate age from birthday
        document.querySelector('input[name="admin_birthday"],input[name="staff_birthday"],input[name="distrib_birthday"]').addEventListener('change', function() {
            const birthday = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - birthday.getFullYear();
            const monthDiff = today.getMonth() - birthday.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthday.getDate())) {
                age--;
            }
            if (age >= 0 && age <= 120) {
                // Find the correct age field
                if (this.name === 'admin_birthday') {
                    document.querySelector('input[name="admin_age"]').value = age;
                } else if (this.name === 'staff_birthday') {
                    document.querySelector('input[name="staff_age"]').value = age;
                } else {
                    document.querySelector('input[name="distrib_age"]').value = age;
                }
            }
        });

        // Form validation before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            var userType = document.querySelector('input[name="user_type"]').value;
            var firstNameField = userType === 'admin' ? 'admin_fname' : (userType === 'staff' ? 'staff_fname' : 'distrib_fname');

            var lastNameField = userType === 'admin' ? 'admin_lname' : (userType === 'staff' ? 'staff_lname' : 'distrib_lname');
            var emailField = userType === 'admin' ? 'admin_email' : (userType === 'staff' ? 'staff_email' : 'distrib_email');

            var firstNameInput = document.querySelector('input[name="admin_fname"]') || document.querySelector('input[name="staff_fname"]') || document.querySelector('input[name="distrib_fname"]');
            var lastNameInput = document.querySelector('input[name="admin_lname"]') || document.querySelector('input[name="staff_lname"]') || document.querySelector('input[name="distrib_lname"]');
            var emailInput = document.querySelector('input[name="admin_email"]') || document.querySelector('input[name="staff_email"]') || document.querySelector('input[name="distrib_email"]');
            
            // Check required fields
            var genderSelect = document.getElementById('gender_select');
            var birthdayField = document.getElementById('birthday_field');
            var civilStatusSelect = document.getElementById('civil_status_select');

            var firstName = firstNameInput ? firstNameInput.value.trim() : '';
            var lastName = lastNameInput ? lastNameInput.value.trim() : '';
            var email = emailInput ? emailInput.value.trim() : '';
            
            // Before submission, ensure gender and civil status are not empty
            if (genderSelect && genderSelect.value === '') {
                console.log("Gender is empty, setting to default");
                // Set default to Male
                for (let i = 0; i < genderSelect.options.length; i++) {
                    if (genderSelect.options[i].value === 'Male') {
                        genderSelect.selectedIndex = i;
                        break;
                    }
                }
            }
            
            if (civilStatusSelect && civilStatusSelect.value === '') {
                console.log("Civil status is empty, setting to default");
                // Set default to Single
                for (let i = 0; i < civilStatusSelect.options.length; i++) {
                    if (civilStatusSelect.options[i].value === 'Single') {
                        civilStatusSelect.selectedIndex = i;
                        break;
                    }
                }
            }

            if (!firstName) {
                e.preventDefault();
                Swal.fire({
                    title: 'Validation Error',
                    text: 'First name is required.',
                    icon: 'error',
                    confirmButtonColor: '#410101'
                });
                if (firstNameInput) firstNameInput.focus();
                return;
            }

            if (!lastName) {
                e.preventDefault();
                Swal.fire({
                    title: 'Validation Error',
                    text: 'Last name is required.',
                    icon: 'error',
                    confirmButtonColor: '#410101'
                });
                if (lastNameInput) lastNameInput.focus();
                return;
            }

            if (email && !isValidEmail(email)) {
                e.preventDefault();
                Swal.fire({
                    title: 'Validation Error',
                    text: 'Please enter a valid email address.',
                    icon: 'error',
                    confirmButtonColor: '#410101'
                });
                if (emailInput) emailInput.focus();
                return;
            }

            Swal.fire({
                title: 'Saving Profile...',
                text: 'Please wait while we update the profile.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });
        
        // Email validation function
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    </script>
</body>
</html>