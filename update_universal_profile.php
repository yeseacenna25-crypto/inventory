<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    $_SESSION['error'] = "Please log in to access this page.";
    header("Location: admin_login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: admin_dashboard.php");
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    $_SESSION['error'] = "Database connection failed: " . $conn->connect_error;
    header("Location: admin_dashboard.php");
    exit();
}

try {
    // Get user type and ID from form
    $user_type = trim($_POST['user_type'] ?? '');
    $user_id = intval($_POST['user_id'] ?? 0);
    
    // Validate user type
    $allowed_types = ['admin', 'staff', 'distributor'];
    if (!in_array($user_type, $allowed_types)) {
        throw new Exception("Invalid user type.");
    }
    
    if ($user_id <= 0) {
        throw new Exception("Invalid user ID.");
    }
    
    // Determine table and ID column based on user type
    $table_config = [
    'admin' => ['table' => 'admin_signup', 'id_col' => 'admin_id'],
    'staff' => ['table' => 'staff_signup', 'id_col' => 'staff_id'],
    'distributor' => ['table' => 'distributor_signup', 'id_col' => 'distributor_id'] // Corrected to distributor_id
    ];
    
    $config = $table_config[$user_type];
    $table = $config['table'];
    $id_column = $config['id_col'];
    
    // Validate and sanitize input
    if ($user_type === 'admin') {
        $first_name = trim($_POST['admin_fname'] ?? '');
        $middle_name = trim($_POST['admin_mname'] ?? '');
        $last_name = trim($_POST['admin_lname'] ?? '');
        $extension = trim($_POST['admin_extension'] ?? '');
        $email = trim($_POST['admin_email'] ?? '');
        $contact_number = trim($_POST['admin_contact_number'] ?? '');
        $gender = trim($_POST['admin_gender'] ?? '');
        $birthday = trim($_POST['admin_birthday'] ?? '');
        $age = intval($_POST['admin_age'] ?? 0);
        $civil_status = trim($_POST['admin_civil_status'] ?? '');
        $address = trim($_POST['admin_address'] ?? '');
        // Prioritize manual outlet value if provided
        $outlet = isset($_POST['manual_outlet_value']) ? trim($_POST['manual_outlet_value']) : trim($_POST['admin_outlet'] ?? '');
        $username = trim($_POST['admin_username'] ?? '');
    } elseif ($user_type === 'staff') {
        $first_name = trim($_POST['staff_fname'] ?? '');
        $middle_name = trim($_POST['staff_mname'] ?? '');
        $last_name = trim($_POST['staff_lname'] ?? '');
        $extension = trim($_POST['staff_extension'] ?? '');
        $email = trim($_POST['staff_email'] ?? '');
        $contact_number = trim($_POST['staff_contact_number'] ?? '');
        $gender = trim($_POST['staff_gender'] ?? '');
        $birthday = trim($_POST['staff_birthday'] ?? '');
        $age = intval($_POST['staff_age'] ?? 0);
        $civil_status = trim($_POST['staff_civil_status'] ?? '');
        $address = trim($_POST['staff_address'] ?? '');
        // Prioritize manual outlet value if provided
        $outlet = isset($_POST['manual_outlet_value']) ? trim($_POST['manual_outlet_value']) : trim($_POST['staff_outlet'] ?? '');
        $username = trim($_POST['staff_username'] ?? '');
    } else {
        $first_name = trim($_POST['distrib_fname'] ?? '');
        $middle_name = trim($_POST['distrib_mname'] ?? '');
        $last_name = trim($_POST['distrib_lname'] ?? '');
        $extension = trim($_POST['distrib_extension'] ?? '');
        $email = trim($_POST['distrib_email'] ?? '');
        $contact_number = trim($_POST['distrib_contact_number'] ?? '');
        $gender = trim($_POST['distrib_gender'] ?? '');
        $birthday = trim($_POST['distrib_birthday'] ?? '');
        $age = intval($_POST['distrib_age'] ?? 0);
        $civil_status = trim($_POST['distrib_civil_status'] ?? '');
        $address = trim($_POST['distrib_address'] ?? '');
        // Prioritize manual outlet value if provided
        $outlet = isset($_POST['manual_outlet_value']) ? trim($_POST['manual_outlet_value']) : trim($_POST['distrib_outlet'] ?? '');
        // Fallback to backup values if available
        if (empty($outlet) && isset($_POST['distrib_outlet_backup'])) {
            $outlet = trim($_POST['distrib_outlet_backup']);
        }
        $username = trim($_POST['distrib_username'] ?? '');
        // Fallback to backup value if username is empty
        if (empty($username) && isset($_POST['distrib_username_backup'])) {
            $username = trim($_POST['distrib_username_backup']);
        }
    }
    
    // Validate outlet format for valid Philippine place (especially for distributors)
    function isValidPlace($place) {
        if (empty($place)) return false;
        
        // Check if it has the proper format with dashes
        if (strpos($place, ' - ') !== false) return true;
        
        // Or contains region/province keywords
        if (strpos($place, 'Region') !== false || strpos($place, 'Province') !== false) return true;
        
        // Check if it's too short to be a valid place
        if (strlen($place) < 5) return false;
        
        // Check against common Philippine location keywords
        $locationKeywords = ['City', 'Municipality', 'Capital', 'Town', 'Province', 'Island'];
        foreach ($locationKeywords as $keyword) {
            if (strpos($place, $keyword) !== false) return true;
        }
        
        return false;
    }
    
    // Validate required fields
    if (empty($first_name)) {
        throw new Exception("First name is required.");
    }
    
    if (empty($last_name)) {
        throw new Exception("Last name is required.");
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address.");
    }
    
    // Validate outlet for distributors
    if ($user_type === 'distributor') {
        if (empty($outlet)) {
            throw new Exception("Outlet is required for distributors.");
        }
        
        // Validate outlet format
        if (!isValidPlace($outlet)) {
            error_log("Invalid outlet format: $outlet for distributor ID: $user_id");
            throw new Exception("Invalid outlet format. Please select a valid Philippine location.");
        }
    }
    
    // Check available columns in the table first
    $column_check = $conn->query("SHOW COLUMNS FROM $table");
    $available_columns = [];
    while ($col = $column_check->fetch_assoc()) {
        $available_columns[] = $col['Field'];
    }
    
    // Log the available columns for debugging
    error_log("Available columns in $table: " . implode(', ', $available_columns));
    
    // Check if username is unique (if provided)
    if (!empty($username)) {
        // Use the correct column name based on user type
        if ($user_type === 'distributor') {
            $username_column = 'distrib_username'; // Special case for distributor table
        } else {
            $username_column = $user_type . '_username';
        }
        
        // Check if the column exists in the table before querying
        $column_exists = false;
        foreach ($available_columns as $col) {
            if ($col === $username_column) {
                $column_exists = true;
                break;
            }
        }
        
        // Only check uniqueness if the column exists
        if ($column_exists) {
            $check_stmt = $conn->prepare("SELECT $id_column FROM $table WHERE $username_column = ? AND $id_column != ?");
            $check_stmt->bind_param("si", $username, $user_id);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();
            
            if ($existing) {
                throw new Exception("Username already exists. Please choose a different username.");
            }
        } else {
            // Log that we're skipping username uniqueness check
            error_log("Username column '$username_column' not found in table '$table', skipping uniqueness check");
        }
    }
    
    // Get current user data for logging changes
    $current_stmt = $conn->prepare("SELECT * FROM $table WHERE $id_column = ?");
    $current_stmt->bind_param("i", $user_id);
    $current_stmt->execute();
    $current_data = $current_stmt->get_result()->fetch_assoc();
    $current_stmt->close();
    
    if (!$current_data) {
        throw new Exception("User record not found.");
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Handle profile image upload
    $profile_image_name = $current_data['profile_image'] ?? ''; // Keep current image by default
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['profile_image'];
        
        // Validate image
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($image['type'], $allowed_types)) {
            throw new Exception("Only JPEG, PNG, and GIF images are allowed.");
        }
        
        if ($image['size'] > $max_size) {
            throw new Exception("Image size must be less than 5MB.");
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $extension_file = pathinfo($image['name'], PATHINFO_EXTENSION);
        $profile_image_name = $user_type . '_' . $user_id . '_' . time() . '.' . $extension_file;
        $target_path = $upload_dir . $profile_image_name;
        
        // Move uploaded file
        if (!move_uploaded_file($image['tmp_name'], $target_path)) {
            throw new Exception("Failed to upload image.");
        }
        
        // Delete old image if it exists and is not the default
        if (!empty($current_data['profile_image']) && $current_data['profile_image'] !== $profile_image_name) {
            $old_image_path = $upload_dir . $current_data['profile_image'];
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }
    }
    
    // Available columns were already checked earlier
    // No need to query them again
    
    // Define field mappings based on user type
    if ($user_type === 'admin') {
        $field_mappings = [
            'admin_extension' => $extension,
            'admin_email' => $email,
            'admin_contact_number' => $contact_number,
            'admin_gender' => $gender,
            'admin_birthday' => $birthday,
            'admin_age' => $age,
            'admin_civil_status' => $civil_status,
            'admin_address' => $address,
            'admin_outlet' => $outlet,
            'admin_username' => $username
        ];
    } elseif ($user_type === 'staff') {
        $field_mappings = [
            'staff_extension' => $extension,
            'staff_email' => $email,
            'staff_contact_number' => $contact_number,
            'staff_gender' => $gender,
            'staff_birthday' => $birthday,
            'staff_age' => $age,
            'staff_civil_status' => $civil_status,
            'staff_address' => $address,
            'staff_outlet' => $outlet,
            'staff_username' => $username
        ];
    } else {
        $field_mappings = [
            'distrib_extension' => $extension,
            'distrib_email' => $email,
            'distrib_contact_number' => $contact_number,
            'distrib_gender' => $gender,
            'distrib_birthday' => $birthday,
            'distrib_age' => $age,
            'distrib_civil_status' => $civil_status,
            'distrib_address' => $address,
            'distrib_outlet' => $outlet,
            'distrib_username' => $username
        ];
    }
    $types = '';
    $update_fields = [];
    $update_values = [];
    
    if ($user_type === 'admin') {
        $update_fields[] = 'admin_fname = ?';
        $update_values[] = $first_name;
        $types .= 's';
        $update_fields[] = 'admin_mname = ?';
        $update_values[] = $middle_name;
        $types .= 's';
        $update_fields[] = 'admin_lname = ?';
        $update_values[] = $last_name;
        $types .= 's';
    } elseif ($user_type === 'staff') {
        $update_fields[] = 'staff_fname = ?';
        $update_values[] = $first_name;
        $types .= 's';
        $update_fields[] = 'staff_mname = ?';
        $update_values[] = $middle_name;
        $types .= 's';
        $update_fields[] = 'staff_lname = ?';
        $update_values[] = $last_name;
        $types .= 's';
    } else {
        $update_fields[] = 'distrib_fname = ?';
        $update_values[] = $first_name;
        $types .= 's';
        $update_fields[] = 'distrib_mname = ?';
        $update_values[] = $middle_name;
        $types .= 's';
        $update_fields[] = 'distrib_lname = ?';
        $update_values[] = $last_name;
        $types .= 's';
    }
    
    foreach ($field_mappings as $field => $value) {
        if (in_array($field, $available_columns)) {
            $update_fields[] = "$field = ?";
            $update_values[] = $value;
            if (strpos($field, 'age') !== false) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
        }
    }
    
    // Add profile image if the column exists
    if (in_array('profile_image', $available_columns)) {
        $update_fields[] = 'profile_image = ?';
        $update_values[] = $profile_image_name;
        $types .= 's';
    }
    
    // Add timestamp if the column exists
    if (in_array('updated_at', $available_columns)) {
        $update_fields[] = 'updated_at = ?';
        $update_values[] = date('Y-m-d H:i:s');
        $types .= 's';
    }
    
    // Build and execute update query
    $set_clause = implode(', ', $update_fields);
    $sql = "UPDATE $table SET $set_clause WHERE $id_column = ?";
    $update_values[] = $user_id;
    $types .= 'i';
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param($types, ...$update_values);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update profile: " . $stmt->error);
    }
    
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    // Create log table name
    $log_table = $user_type . '_profile_logs';
    
    // Check if log table exists, if not create it
    $log_table_check = $conn->query("SHOW TABLES LIKE '$log_table'");
    if ($log_table_check->num_rows == 0) {
        $create_log_table = "CREATE TABLE $log_table (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            {$id_column} INT NOT NULL,
            field_changed VARCHAR(50) NOT NULL,
            old_value TEXT,
            new_value TEXT,
            changed_by INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($create_log_table);
    }
    
    // Log profile changes
    $changes_logged = [];
    
    // Use correct field names based on user type
    if ($user_type === 'admin') {
        $fields_to_check = [
            'admin_fname' => $first_name,
            'admin_mname' => $middle_name,
            'admin_lname' => $last_name,
            'admin_extension' => $extension,
            'admin_email' => $email,
            'admin_contact_number' => $contact_number,
            'admin_gender' => $gender,
            'admin_birthday' => $birthday,
            'admin_age' => $age,
            'admin_civil_status' => $civil_status,
            'admin_address' => $address,
            'admin_outlet' => $outlet,
            'admin_username' => $username
        ];
    } elseif ($user_type === 'staff') {
        $fields_to_check = [
            'staff_fname' => $first_name,
            'staff_mname' => $middle_name,
            'staff_lname' => $last_name,
            'staff_extension' => $extension,
            'staff_email' => $email,
            'staff_contact_number' => $contact_number,
            'staff_gender' => $gender,
            'staff_birthday' => $birthday,
            'staff_age' => $age,
            'staff_civil_status' => $civil_status,
            'staff_address' => $address,
            'staff_outlet' => $outlet,
            'staff_username' => $username
        ];
    } else {
        $fields_to_check = [
            'distrib_fname' => $first_name,
            'distrib_mname' => $middle_name,
            'distrib_lname' => $last_name,
            'distrib_extension' => $extension,
            'distrib_email' => $email,
            'distrib_contact_number' => $contact_number,
            'distrib_gender' => $gender,
            'distrib_birthday' => $birthday,
            'distrib_age' => $age,
            'distrib_civil_status' => $civil_status,
            'distrib_address' => $address,
            'distrib_outlet' => $outlet,
            'distrib_username' => $username
        ];
    }
    
    foreach ($fields_to_check as $field => $new_value) {
        // Only check and log fields that exist in the table
        if (!in_array($field, $available_columns)) {
            error_log("Field '$field' not found in table '$table', skipping change logging");
            continue;
        }
        
        $old_value = $current_data[$field] ?? '';
        if ($old_value !== $new_value) {
            $log_stmt = $conn->prepare("INSERT INTO $log_table ({$id_column}, field_changed, old_value, new_value, changed_by, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $log_stmt->bind_param("issssss", $user_id, $field, $old_value, $new_value, $_SESSION['admin_id'], $ip_address, $user_agent);
            $log_stmt->execute();
            $log_stmt->close();
            
            $changes_logged[] = $field;
        }
    }
    
    // Log image change if applicable
    $old_image = $current_data['profile_image'] ?? '';
    if ($old_image !== $profile_image_name) {
        $log_stmt = $conn->prepare("INSERT INTO $log_table ({$id_column}, field_changed, old_value, new_value, changed_by, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $field_name = 'profile_image';
        
        $log_stmt->bind_param("issssss", $user_id, $field_name, $old_image, $profile_image_name, $_SESSION['admin_id'], $ip_address, $user_agent);
        $log_stmt->execute();
        $log_stmt->close();
        
        $changes_logged[] = 'profile_image';
    }
    
    // Commit transaction
    $conn->commit();
    
    // Set success message
    if ($affected_rows > 0 || !empty($changes_logged)) {
        $_SESSION['success'] = ucfirst($user_type) . " profile updated successfully!";
        if (!empty($changes_logged)) {
            $_SESSION['success'] .= " Changed fields: " . implode(', ', $changes_logged);
        }
    } else {
        $_SESSION['success'] = "No changes were made to the profile.";
    }
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Set error message
    $_SESSION['error'] = $e->getMessage();
    
    // Delete uploaded image if there was an error
    if (isset($target_path) && file_exists($target_path)) {
        unlink($target_path);
    }
} finally {
    $conn->close();
}

// Redirect back to edit profile page
$redirect_url = "edit_universal_profile.php?type=$user_type&id=$user_id";
header("Location: $redirect_url");
exit();
?>
