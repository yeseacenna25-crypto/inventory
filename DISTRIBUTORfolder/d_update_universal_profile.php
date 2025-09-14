<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['distributor_id'])) {
    $_SESSION['error'] = "Please log in to access this page.";
    header("Location: distributor_login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: d_distributor_dashboard.php");
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    $_SESSION['error'] = "Database connection failed: " . $conn->connect_error;
    header("Location: d_distributor_dashboard.php");
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
        'distributor' => ['table' => 'distributor_signup', 'id_col' => 'distributor_id']
    ];
    
    $config = $table_config[$user_type];
    $table = $config['table'];
    $id_column = $config['id_col'];
    
    // Get current user data for reference before changes
    $current_stmt = $conn->prepare("SELECT * FROM $table WHERE $id_column = ?");
    $current_stmt->bind_param("i", $user_id);
    $current_stmt->execute();
    $current_data = $current_stmt->get_result()->fetch_assoc();
    $current_stmt->close();
    
    if (!$current_data) {
        throw new Exception("User record not found.");
    }
    
    // Log the received POST data for debugging
    error_log("POST Data received: " . print_r($_POST, true));
    
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
        $outlet = trim($_POST['admin_outlet'] ?? '');
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
        $outlet = trim($_POST['staff_outlet'] ?? '');
        $username = trim($_POST['staff_username'] ?? '');
    } else {
        $first_name = trim($_POST['distrib_fname'] ?? '');
        $middle_name = trim($_POST['distrib_mname'] ?? '');
        $last_name = trim($_POST['distrib_lname'] ?? '');
        $extension = trim($_POST['distrib_extension'] ?? '');
        $email = trim($_POST['distrib_email'] ?? '');
        $contact_number = trim($_POST['distrib_contact_number'] ?? '');
        
        // Special handling for distributor fields that might be missing
        // If the direct form field is empty, check for the values directly from database
        if (empty($_POST['distrib_gender']) || $_POST['distrib_gender'] == '') {
            // Get from current data if available
            if (isset($current_data) && !empty($current_data['distrib_gender'])) {
                $gender = $current_data['distrib_gender'];
                error_log("Using existing gender from database: " . $gender);
            } else {
                $gender = 'Male'; // Default value
                error_log("Using default gender: Male");
            }
        } else {
            $gender = trim($_POST['distrib_gender']);
            error_log("Using submitted gender: " . $gender);
        }
        
        if (empty($_POST['distrib_birthday']) || $_POST['distrib_birthday'] == '') {
            if (isset($current_data) && !empty($current_data['distrib_birthday'])) {
                $birthday = $current_data['distrib_birthday'];
                error_log("Using existing birthday from database: " . $birthday);
            } else {
                $birthday = date('Y-m-d'); // Today as default
                error_log("Using default birthday: " . $birthday);
            }
        } else {
            $birthday = trim($_POST['distrib_birthday']);
            error_log("Using submitted birthday: " . $birthday);
        }
        
        if (empty($_POST['distrib_age']) || $_POST['distrib_age'] == '' || intval($_POST['distrib_age']) <= 0) {
            if (isset($current_data) && !empty($current_data['distrib_age'])) {
                $age = intval($current_data['distrib_age']);
                error_log("Using existing age from database: " . $age);
            } else {
                // Calculate age from birthday if available
                if (!empty($birthday)) {
                    $birthdateObj = new DateTime($birthday);
                    $todayObj = new DateTime();
                    $interval = $todayObj->diff($birthdateObj);
                    $age = $interval->y;
                    error_log("Calculated age from birthday: " . $age);
                } else {
                    $age = 0;
                    error_log("No age information available");
                }
            }
        } else {
            $age = intval($_POST['distrib_age']);
            error_log("Using submitted age: " . $age);
        }
        
        if (empty($_POST['distrib_civil_status']) || $_POST['distrib_civil_status'] == '') {
            if (isset($current_data) && !empty($current_data['distrib_civil_status'])) {
                $civil_status = $current_data['distrib_civil_status'];
                error_log("Using existing civil status from database: " . $civil_status);
            } else {
                $civil_status = 'Single'; // Default value
                error_log("Using default civil status: Single");
            }
        } else {
            $civil_status = trim($_POST['distrib_civil_status']);
            error_log("Using submitted civil status: " . $civil_status);
        }
        
        if (empty($_POST['distrib_address']) || $_POST['distrib_address'] == '') {
            if (isset($current_data) && !empty($current_data['distrib_address'])) {
                $address = $current_data['distrib_address'];
                error_log("Using existing address from database: " . $address);
            } else {
                $address = '';
                error_log("No address information available");
            }
        } else {
            $address = trim($_POST['distrib_address']);
            error_log("Using submitted address: " . $address);
        }
        
        // Check if outlet field exists in the POST data
        if (isset($_POST['distrib_outlet']) && !empty(trim($_POST['distrib_outlet']))) {
            $outlet = trim($_POST['distrib_outlet']);
            error_log("Distributor outlet found in POST data: '" . $outlet . "'");
        } 
        // Try the backup field if primary is empty
        else if (isset($_POST['distrib_outlet_backup']) && !empty(trim($_POST['distrib_outlet_backup']))) {
            $outlet = trim($_POST['distrib_outlet_backup']);
            error_log("Using backup outlet value: '" . $outlet . "'");
        }
        // Try the debug field as last resort
        else if (isset($_POST['outlet_debug']) && !empty(trim($_POST['outlet_debug']))) {
            $outlet = trim($_POST['outlet_debug']);
            error_log("Using debug outlet value: '" . $outlet . "'");
        }
        // If we still don't have a value, check if we have an existing one in the database
        else {
            error_log("WARNING: No outlet value found in POST data!");
            
            // Try to retrieve current outlet from database
            if (isset($current_data) && !empty($current_data['distrib_outlet'])) {
                $outlet = $current_data['distrib_outlet'];
                error_log("Using existing outlet from current_data: " . $outlet);
            } else {
                // Fallback to database query
                $check_outlet = $conn->prepare("SELECT distrib_outlet FROM distributor_signup WHERE distributor_id = ?");
                $check_outlet->bind_param("i", $user_id);
                $check_outlet->execute();
                $outlet_result = $check_outlet->get_result();
                
                if ($outlet_result && $row = $outlet_result->fetch_assoc()) {
                    $outlet = $row['distrib_outlet'] ?? '';
                    error_log("Retrieved outlet from database query: '" . $outlet . "'");
                } else {
                    $outlet = '';
                    error_log("Could not retrieve outlet from database!");
                }
                
                $check_outlet->close();
            }
        }
        
        $username = trim($_POST['distrib_username'] ?? '');
    }
    
    // Log final values for debugging
    error_log("Final values after validation:");
    error_log("First name: $first_name");
    error_log("Middle name: $middle_name");
    error_log("Last name: $last_name");
    error_log("Extension: $extension");
    error_log("Email: $email");
    error_log("Contact number: $contact_number");
    error_log("Gender: $gender");
    error_log("Birthday: $birthday");
    error_log("Age: $age");
    error_log("Civil status: $civil_status");
    error_log("Address: $address");
    error_log("Outlet: $outlet");
    error_log("Username: $username");
    
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
    
    // Check if username is unique (if provided)
    if (!empty($username)) {
        // Use the correct column name based on user type
        $username_column = $user_type . '_username';
        $check_stmt = $conn->prepare("SELECT $id_column FROM $table WHERE $username_column = ? AND $id_column != ?");
        $check_stmt->bind_param("si", $username, $user_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        
        if ($existing) {
            throw new Exception("Username already exists. Please choose a different username.");
        }
    }
    
    // Check available columns in the table
    $column_check = $conn->query("SHOW COLUMNS FROM $table");
    $available_columns = [];
    
    while ($col = $column_check->fetch_assoc()) {
        $available_columns[] = $col['Field'];
    }
    
    // Check if outlet is required for distributors
    if ($user_type === 'distributor') {
        // Log outlet value for debugging
        error_log("Distributor outlet value: '" . $outlet . "'");
        error_log("POST outlet value: '" . ($_POST['distrib_outlet'] ?? 'not set') . "'");
        
        // Debug the outlet value in detail
        error_log("Outlet value type: " . gettype($outlet));
        error_log("Outlet value length: " . strlen($outlet));
        error_log("Outlet value hex: " . bin2hex($outlet));
        
        // Modified check - use strict empty check but be more lenient with existing records
        if (empty($outlet) || trim($outlet) === '') {
            // Check if this is an existing distributor with profile data
            if ($user_id > 0) {
                $check_profile = $conn->prepare("SELECT distrib_outlet FROM distributor_signup WHERE distributor_id = ?");
                $check_profile->bind_param("i", $user_id);
                $check_profile->execute();
                $profile_result = $check_profile->get_result();
                $row = $profile_result->fetch_assoc();
                
                if ($row && !empty($row['distrib_outlet'])) {
                    // Use the existing outlet value from database
                    $outlet = $row['distrib_outlet'];
                    error_log("Using existing outlet value from database: " . $outlet);
                } else {
                    // For existing profiles, we'll just issue a warning but continue
                    error_log("WARNING: Outlet value is empty for existing distributor ID {$user_id}!");
                    $_SESSION['warning'] = "Warning: Outlet field is empty. Please update this information.";
                }
                
                $check_profile->close();
            } else {
                error_log("ERROR: Outlet value is empty for new distributor!");
                throw new Exception("Outlet is required for distributors.");
            }
        } else {
            error_log("SUCCESS: Outlet value is NOT empty: '" . $outlet . "'");
        }
    }
    
    // Double-check critical fields for distributors
    if ($user_type === 'distributor') {
        // Make one final check to ensure we have all required fields
        // If any required field is empty, get it from the database
        if (empty($gender) || empty($birthday) || empty($civil_status) || $age <= 0) {
            $check_fields = $conn->prepare("SELECT distrib_gender, distrib_birthday, distrib_civil_status, distrib_age, distrib_address FROM distributor_signup WHERE distributor_id = ?");
            $check_fields->bind_param("i", $user_id);
            $check_fields->execute();
            $field_result = $check_fields->get_result();
            $field_data = $field_result->fetch_assoc();
            $check_fields->close();
            
            if ($field_data) {
                // Fill in any missing fields with database values
                if (empty($gender)) {
                    $gender = $field_data['distrib_gender'] ?? 'Male';
                    error_log("Final check: Using gender from database: " . $gender);
                }
                
                if (empty($birthday)) {
                    $birthday = $field_data['distrib_birthday'] ?? date('Y-m-d');
                    error_log("Final check: Using birthday from database: " . $birthday);
                }
                
                if ($age <= 0 && !empty($field_data['distrib_age'])) {
                    $age = intval($field_data['distrib_age']);
                    error_log("Final check: Using age from database: " . $age);
                }
                
                if (empty($civil_status)) {
                    $civil_status = $field_data['distrib_civil_status'] ?? 'Single';
                    error_log("Final check: Using civil status from database: " . $civil_status);
                }
                
                if (empty($address)) {
                    $address = $field_data['distrib_address'] ?? '';
                    error_log("Final check: Using address from database: " . $address);
                }
            } else {
                // Set defaults if needed
                if (empty($gender)) {
                    $gender = 'Male';
                    error_log("Final check: Using default gender: Male");
                }
                
                if (empty($birthday)) {
                    $birthday = date('Y-m-d');
                    error_log("Final check: Using default birthday: " . $birthday);
                }
                
                if (empty($civil_status)) {
                    $civil_status = 'Single';
                    error_log("Final check: Using default civil status: Single");
                }
                
                if ($age <= 0) {
                    // Calculate age from birthday
                    $birthdateObj = new DateTime($birthday);
                    $todayObj = new DateTime();
                    $interval = $todayObj->diff($birthdateObj);
                    $age = $interval->y;
                    error_log("Final check: Calculated age from birthday: " . $age);
                }
            }
        }
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
    
    // Configure field mappings
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
    
    // Prepare update fields and values
    $update_fields = [];
    $update_values = [];
    $types = '';
    
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
        // Determine the exact column name to use in the log table
        error_log("Creating log table: $log_table");
        
        $create_log_table = "CREATE TABLE $log_table (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL COMMENT 'References {$id_column} in {$table}',
            field_name VARCHAR(100) NOT NULL,
            old_value TEXT,
            new_value TEXT,
            changed_by INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($create_log_table);
        
        if ($conn->error) {
            error_log("Error creating log table: " . $conn->error);
        } else {
            error_log("Log table created successfully");
        }
    } else {
        // Table exists, check if it has the user_id column
        $column_check = $conn->query("SHOW COLUMNS FROM $log_table LIKE 'user_id'");
        if ($column_check->num_rows == 0) {
            // Need to add the user_id column
            error_log("Adding user_id column to existing log table: $log_table");
            
            // First, rename the old ID column if it exists
            $old_column_check = $conn->query("SHOW COLUMNS FROM $log_table LIKE '$id_column'");
            if ($old_column_check->num_rows > 0) {
                $alter_sql = "ALTER TABLE $log_table CHANGE `$id_column` user_id INT NOT NULL COMMENT 'References {$id_column} in {$table}'";
                $conn->query($alter_sql);
                
                if ($conn->error) {
                    error_log("Error renaming column in log table: " . $conn->error);
                } else {
                    error_log("Renamed column in log table successfully");
                }
            } else {
                // If the old column doesn't exist, just add the new one
                $alter_sql = "ALTER TABLE $log_table ADD COLUMN user_id INT NOT NULL COMMENT 'References {$id_column} in {$table}' AFTER log_id";
                $conn->query($alter_sql);
                
                if ($conn->error) {
                    error_log("Error adding user_id column to log table: " . $conn->error);
                } else {
                    error_log("Added user_id column to log table successfully");
                }
            }
        }
        
        // Also check if we need to update field_changed to field_name
        $field_column_check = $conn->query("SHOW COLUMNS FROM $log_table LIKE 'field_changed'");
        if ($field_column_check->num_rows > 0) {
            error_log("Updating field_changed column to field_name in log table: $log_table");
            $alter_sql = "ALTER TABLE $log_table CHANGE `field_changed` field_name VARCHAR(100) NOT NULL";
            $conn->query($alter_sql);
            
            if ($conn->error) {
                error_log("Error renaming field_changed column: " . $conn->error);
            } else {
                error_log("Renamed field_changed column successfully");
            }
        }
        
        // Check if we need to update created_at to change_date
        $date_column_check = $conn->query("SHOW COLUMNS FROM $log_table LIKE 'created_at'");
        if ($date_column_check->num_rows > 0) {
            error_log("Updating created_at column to change_date in log table: $log_table");
            $alter_sql = "ALTER TABLE $log_table CHANGE `created_at` change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            $conn->query($alter_sql);
            
            if ($conn->error) {
                error_log("Error renaming created_at column: " . $conn->error);
            } else {
                error_log("Renamed created_at column successfully");
            }
        }
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
        $old_value = $current_data[$field] ?? '';
        if ($old_value !== $new_value) {
            // Use generic user_id field for logging with correct column names
            $log_stmt = $conn->prepare("INSERT INTO $log_table (user_id, field_name, old_value, new_value, changed_by, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            error_log("Logging change for user_id: $user_id, field: $field");
            $log_stmt->bind_param("issssss", $user_id, $field, $old_value, $new_value, $_SESSION['distributor_id'], $ip_address, $user_agent);
            $log_stmt->execute();
            $log_stmt->close();
            
            $changes_logged[] = $field;
        }
    }
    
    // Log image change if applicable
    $old_image = $current_data['profile_image'] ?? '';
    if ($old_image !== $profile_image_name) {
        // Use generic user_id field for logging profile image changes with correct column names
        $log_stmt = $conn->prepare("INSERT INTO $log_table (user_id, field_name, old_value, new_value, changed_by, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $field_name = 'profile_image';
        
        error_log("Logging profile image change for user_id: $user_id");
        $log_stmt->bind_param("issssss", $user_id, $field_name, $old_image, $profile_image_name, $_SESSION['distributor_id'], $ip_address, $user_agent);
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
$redirect_url = "d_edit_universal_profile.php?type=$user_type&id=$user_id";
header("Location: $redirect_url");
exit();
?>