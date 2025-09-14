<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['staff_id'])) {
    $_SESSION['error'] = "Please log in to access this page.";
    header("Location: staff_login.php");
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
    header("Location: staff_dashboard.php");
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
        // Distributor handling - prioritize current values if fields are missing
        $first_name = trim($_POST['distrib_fname'] ?? '');
        $middle_name = trim($_POST['distrib_mname'] ?? '');
        $last_name = trim($_POST['distrib_lname'] ?? '');
        $extension = trim($_POST['distrib_extension'] ?? '');
        $email = trim($_POST['distrib_email'] ?? '');
        $contact_number = trim($_POST['distrib_contact_number'] ?? '');
        
        // Log the received POST data for debugging
        error_log("POST Data received for distributor: " . print_r($_POST, true));
        
        // Handle gender field with fallback
        if (empty($_POST['distrib_gender'])) {
            if (isset($_POST['distrib_gender_backup']) && !empty($_POST['distrib_gender_backup'])) {
                $gender = trim($_POST['distrib_gender_backup']);
                error_log("Using gender from backup field: " . $gender);
            } elseif (!empty($current_data['distrib_gender'])) {
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
        
        // Handle birthday field with fallback
        if (empty($_POST['distrib_birthday'])) {
            if (isset($_POST['distrib_birthday_backup']) && !empty($_POST['distrib_birthday_backup'])) {
                $birthday = trim($_POST['distrib_birthday_backup']);
                error_log("Using birthday from backup field: " . $birthday);
            } elseif (!empty($current_data['distrib_birthday'])) {
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
        
        // Handle age field with fallback
        if (empty($_POST['distrib_age']) || intval($_POST['distrib_age']) <= 0) {
            if (isset($_POST['distrib_age_backup']) && !empty($_POST['distrib_age_backup']) && intval($_POST['distrib_age_backup']) > 0) {
                $age = intval($_POST['distrib_age_backup']);
                error_log("Using age from backup field: " . $age);
            } elseif (!empty($current_data['distrib_age'])) {
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
        
        // Handle civil status field with fallback
        if (empty($_POST['distrib_civil_status'])) {
            if (isset($_POST['distrib_civil_status_backup']) && !empty($_POST['distrib_civil_status_backup'])) {
                $civil_status = trim($_POST['distrib_civil_status_backup']);
                error_log("Using civil status from backup field: " . $civil_status);
            } elseif (!empty($current_data['distrib_civil_status'])) {
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
        
        // Handle address field with fallback
        if (empty($_POST['distrib_address'])) {
            if (isset($_POST['distrib_address_backup']) && !empty($_POST['distrib_address_backup'])) {
                $address = trim($_POST['distrib_address_backup']);
                error_log("Using address from backup field: " . $address);
            } elseif (!empty($current_data['distrib_address'])) {
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
        
        // Handle outlet field with multiple fallback strategies
        // Log all possible outlet sources for debugging
        error_log("Outlet sources available:");
        error_log("POST distrib_outlet: " . (isset($_POST['distrib_outlet']) ? $_POST['distrib_outlet'] : 'not set'));
        error_log("POST manual_outlet_value: " . (isset($_POST['manual_outlet_value']) ? $_POST['manual_outlet_value'] : 'not set'));
        error_log("POST distrib_outlet_backup: " . (isset($_POST['distrib_outlet_backup']) ? $_POST['distrib_outlet_backup'] : 'not set'));
        error_log("POST outlet_debug: " . (isset($_POST['outlet_debug']) ? $_POST['outlet_debug'] : 'not set'));
        error_log("Current data outlet: " . (isset($current_data['distrib_outlet']) ? $current_data['distrib_outlet'] : 'not set'));
        
        // Function to check if a value looks like a valid place (Region - Province - City format)
        function isValidPlace($place) {
            if (empty($place)) return false;
            
            // Must contain proper region-province-city format with dashes
            if (strpos($place, ' - ') !== false) return true;
            
            // Must contain region/province keywords
            if (strpos($place, 'Region') !== false || strpos($place, 'Province') !== false) return true;
            
            // Valid places shouldn't be too short
            if (strlen($place) < 5) return false;
            
            // Should not be someone's name (name likely won't have these keywords)
            $locationKeywords = ['City', 'Municipality', 'Capital', 'Town', 'Province', 'Island'];
            foreach ($locationKeywords as $keyword) {
                if (strpos($place, $keyword) !== false) return true;
            }
            
            // Check if it matches known Philippines location patterns
            $philippineLocationPattern = '/Metro|Davao|Cebu|Manila|Luzon|Visayas|Mindanao|Palawan|Boracay|Baguio|Iloilo|Zamboanga|Batangas|Quezon|Makati|Tagaytay|Taguig|Pasay|Pasig|Muntinlupa|Cavite|Laguna|Bataan|Pampanga|Bulacan|Nueva|Albay|Camarines|Sorsogon|Ilocos|Pangasinan|Benguet|Cagayan|Tarlac|Zambales|Bataan|Rizal|Leyte|Samar|Bohol|Negros|Panay|Palawan|Marinduque|Romblon|Siquijor|Basilan|Lanao|Maguindanao|Sultan|Sulu|Tawi|Compostela|Cotabato|Agusan|Bukidnon|Misamis|Surigao|Marawi|Cagayan/i';
            if (preg_match($philippineLocationPattern, $place)) return true;
            
            // If it passed no checks, it's probably not a valid place
            return false;
        }
        
        // Check if this is a form submission with an empty outlet but other fields are populated
        $form_has_data = !empty($_POST['distrib_fname']) || !empty($_POST['distrib_lname']) || 
                         !empty($_POST['distrib_gender']) || !empty($_POST['distrib_birthday']);
        
        // For distributor, outlet is critical - make extra effort to get a value
        // Prioritize sources in this order: 
        // 1. Manual outlet value, 2. Direct POST field, 3. Debug field, 4. Backup field, 5. Current data, 6. Database query
        $found_outlet = false;
        
        // 1. Manual outlet value (highest priority - set by the form validation)
        if (!empty($_POST['manual_outlet_value']) && isValidPlace($_POST['manual_outlet_value'])) {
            $outlet = trim($_POST['manual_outlet_value']);
            error_log("SUCCESS: Using manual outlet value: " . $outlet);
            $found_outlet = true;
        }
        // 2. Direct POST field
        elseif (!empty($_POST['distrib_outlet']) && isValidPlace($_POST['distrib_outlet'])) {
            $outlet = trim($_POST['distrib_outlet']);
            error_log("SUCCESS: Using submitted outlet (POST distrib_outlet): " . $outlet);
            $found_outlet = true;
        }
        // 3. Debug field
        elseif (!empty($_POST['outlet_debug']) && isValidPlace($_POST['outlet_debug'])) {
            $outlet = trim($_POST['outlet_debug']);
            error_log("SUCCESS: Using outlet from debug field: " . $outlet);
            $found_outlet = true;
        }
        // 4. Backup field
        elseif (!empty($_POST['distrib_outlet_backup']) && isValidPlace($_POST['distrib_outlet_backup'])) {
            $outlet = trim($_POST['distrib_outlet_backup']);
            error_log("SUCCESS: Using outlet from backup field: " . $outlet);
            $found_outlet = true;
        }
        // 5. Current data - only use if it's a valid place
        elseif (!empty($current_data['distrib_outlet']) && isValidPlace($current_data['distrib_outlet'])) {
            $outlet = $current_data['distrib_outlet'];
            error_log("SUCCESS: Using existing outlet from current_data: " . $outlet);
            $found_outlet = true;
        }
        // 5. Database query - last resort
        else {
            // Last resort - try to get outlet from database
            $check_outlet = $conn->prepare("SELECT distrib_outlet FROM distributor_signup WHERE distributor_id = ?");
            $check_outlet->bind_param("i", $user_id);
            $check_outlet->execute();
            $outlet_result = $check_outlet->get_result();
            
            if ($outlet_result && $row = $outlet_result->fetch_assoc()) {
                $outlet_value = $row['distrib_outlet'] ?? '';
                if (!empty($outlet_value) && isValidPlace($outlet_value)) {
                    $outlet = $outlet_value;
                    error_log("SUCCESS: Retrieved outlet from database query: " . $outlet);
                    $found_outlet = true;
                } else {
                    error_log("Failed to find valid outlet in database query - value is empty or not a place: " . $outlet_value);
                }
            } else {
                error_log("Could not retrieve outlet from database query!");
            }
            
            $check_outlet->close();
        }
        
        // Additional logging about outlet result
        if ($found_outlet) {
            error_log("FINAL OUTLET VALUE: " . $outlet);
        } else {
            error_log("WARNING: No outlet value found from any source!");
            // If we have form data but no outlet, make sure to preserve any existing outlet
            if ($form_has_data) {
                // Double-check the current data one more time - using a safer query method
                $outlet_sql = "SELECT distrib_outlet FROM distributor_signup WHERE distributor_id = ?";
                if ($check_outlet_stmt = $conn->prepare($outlet_sql)) {
                    $check_outlet_stmt->bind_param("i", $user_id);
                    $check_outlet_stmt->execute();
                    $outlet_result = $check_outlet_stmt->get_result();
                    
                    if ($outlet_result && $row = $outlet_result->fetch_assoc()) {
                        $outlet_value = $row['distrib_outlet'] ?? '';
                        if (!empty($outlet_value) && isValidPlace($outlet_value)) {
                            $outlet = $outlet_value;
                            error_log("EMERGENCY FALLBACK: Retrieved valid outlet directly from database: " . $outlet);
                            $found_outlet = true;
                        } else {
                            error_log("EMERGENCY FALLBACK FAILED: Retrieved invalid outlet: " . $outlet_value);
                        }
                    } else {
                        // Last resort - use a placeholder value
                        $outlet = 'Please Update Outlet';
                        error_log("EMERGENCY FALLBACK: Using placeholder outlet: " . $outlet);
                    }
                    $check_outlet_stmt->close();
                } else {
                    // Query preparation failed
                    $outlet = 'Please Update Outlet';
                    error_log("EMERGENCY FALLBACK: Database query failed - using placeholder outlet");
                }
            }
        }
        
        // Final verification - if outlet is still empty, use a default placeholder
        if (empty($outlet)) {
            // Check if this is a form submission with an empty outlet but other fields are populated
            $form_has_data = !empty($_POST['distrib_fname']) || !empty($_POST['distrib_lname']) || 
                            !empty($_POST['distrib_gender']) || !empty($_POST['distrib_birthday']);
                            
            if ($form_has_data) {
                error_log("CRITICAL: Form has data but outlet is empty, querying database one last time");
                // Try one more database query - with safer prepared statement
                $final_outlet_sql = "SELECT distrib_outlet FROM distributor_signup WHERE distributor_id = ?";
                if ($final_stmt = $conn->prepare($final_outlet_sql)) {
                    $final_stmt->bind_param("i", $user_id);
                    $final_stmt->execute();
                    $final_result = $final_stmt->get_result();
                    
                    if ($final_result && $outlet_row = $final_result->fetch_assoc()) {
                        $outlet_value = $outlet_row['distrib_outlet'] ?? '';
                        if (!empty($outlet_value) && isValidPlace($outlet_value)) {
                            $outlet = $outlet_value;
                            error_log("FINAL RESORT: Found valid outlet in database: " . $outlet);
                        } else {
                            error_log("FINAL RESORT: Found invalid outlet in database: " . $outlet_value);
                            $outlet = 'Please Update Outlet';
                        }
                    } else {
                        $outlet = 'Please Update Outlet';
                        error_log("FINAL RESORT: Using placeholder for outlet: " . $outlet);
                    }
                    $final_stmt->close();
                } else {
                    $outlet = 'Please Update Outlet';
                    error_log("FINAL RESORT: Database query failed - using placeholder for outlet");
                }
            } else {
                $outlet = 'No Outlet Specified';
                error_log("WARNING: Using default placeholder for outlet: " . $outlet);
            }
        } else {
            error_log("CONFIRMED: Final outlet value to be stored: " . $outlet);
        }
        
        // Handle username field with multiple fallback strategies
        // Log all possible username sources for debugging
        error_log("Username sources available:");
        error_log("POST distrib_username: " . (isset($_POST['distrib_username']) ? $_POST['distrib_username'] : 'not set'));
        error_log("POST distrib_username_backup: " . (isset($_POST['distrib_username_backup']) ? $_POST['distrib_username_backup'] : 'not set'));
        error_log("POST username_debug: " . (isset($_POST['username_debug']) ? $_POST['username_debug'] : 'not set'));
        error_log("Current data username: " . (isset($current_data['distrib_username']) ? $current_data['distrib_username'] : 'not set'));
        
        // Prioritize sources in this order: 
        // 1. Direct POST field, 2. Debug field, 3. Backup field, 4. Current data
        if (!empty($_POST['distrib_username'])) {
            $username = trim($_POST['distrib_username']);
            error_log("Using submitted username (POST distrib_username): " . $username);
        }
        elseif (!empty($_POST['username_debug'])) {
            $username = trim($_POST['username_debug']);
            error_log("Using username from debug field: " . $username);
        }
        elseif (!empty($_POST['distrib_username_backup'])) {
            $username = trim($_POST['distrib_username_backup']);
            error_log("Using username from backup field: " . $username);
        }
        elseif (!empty($current_data['distrib_username'])) {
            $username = $current_data['distrib_username'];
            error_log("Using existing username from database: " . $username);
        }
        else {
            // Generate a username from first name and last name if nothing else is available
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $first_name) . 
                              substr(preg_replace('/[^a-zA-Z0-9]/', '', $last_name), 0, 3) . 
                              rand(100, 999));
            error_log("Generated username from first name and last name: " . $username);
        }
        
        // Final verification - if username is still empty but we have first and last name
        if (empty($username) && (!empty($first_name) && !empty($last_name))) {
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $first_name) . 
                              substr(preg_replace('/[^a-zA-Z0-9]/', '', $last_name), 0, 3) . 
                              rand(100, 999));
            error_log("Final check - generated username from first name and last name: " . $username);
        }
        
        // Double-check critical fields for distributors
        if (empty($gender)) {
            $gender = 'Male'; // Default
            error_log("Double-check set empty gender to Male");
        }
        
        if (empty($birthday)) {
            $birthday = date('Y-m-d'); // Default to today
            error_log("Double-check set empty birthday to today");
        }
        
        if ($age <= 0) {
            // Calculate from birthday
            $birthdateObj = new DateTime($birthday);
            $todayObj = new DateTime();
            $interval = $todayObj->diff($birthdateObj);
            $age = $interval->y;
            error_log("Double-check calculated age from birthday: " . $age);
        }
        
        if (empty($civil_status)) {
            $civil_status = 'Single'; // Default
            error_log("Double-check set empty civil_status to Single");
        }
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
    
    // Check if username is unique (if provided)
    if (!empty($username)) {
        // Check if the username column exists in this table
        $column_check_result = $conn->query("SHOW COLUMNS FROM $table LIKE '{$user_type}_username'");
        $username_column_exists = ($column_check_result->num_rows > 0);
        
        if ($username_column_exists) {
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
        } else {
            // Log that we're skipping username check because column doesn't exist
            error_log("WARNING: Skipping username uniqueness check because '{$user_type}_username' column doesn't exist in $table");
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
    
    // Check outlet requirement for distributors
    if ($user_type === 'distributor' && empty($outlet)) {
        // Check if this is an existing distributor with profile data
        $check_profile = $conn->prepare("SELECT COUNT(*) as count FROM distributor_signup WHERE distributor_id = ? AND distrib_fname != ''");
        $check_profile->bind_param("i", $user_id);
        $check_profile->execute();
        $profile_result = $check_profile->get_result();
        $has_profile = ($profile_result && $row = $profile_result->fetch_assoc()) ? $row['count'] > 0 : false;
        $check_profile->close();
        
        if ($has_profile) {
            // For existing profiles, just issue a warning
            error_log("WARNING: Outlet value is empty for existing distributor ID " . $user_id);
            $_SESSION['warning'] = "Warning: Outlet field is empty. Please update this information.";
        } else {
            error_log("ERROR: Outlet value is empty for new distributor!");
            throw new Exception("Outlet is required for distributors.");
        }
    }
    
    // Check available columns in the table
    $column_check = $conn->query("SHOW COLUMNS FROM $table");
    $available_columns = [];
    
    while ($col = $column_check->fetch_assoc()) {
        $available_columns[] = $col['Field'];
    }
    
    // Prepare update fields
    if ($user_type === 'admin') {
        $field_mappings = [
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
        $field_mappings = [
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
        // Check which columns actually exist in the distributor table before mapping
        $distributor_columns = [];
        $col_result = $conn->query("SHOW COLUMNS FROM distributor_signup");
        while ($col = $col_result->fetch_assoc()) {
            $distributor_columns[] = $col['Field'];
        }
        
        // Define base mappings
        $field_mappings = [
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
            'distrib_outlet' => $outlet
        ];
        
        // Only add username field if it exists in the table
        if (in_array('distrib_username', $distributor_columns)) {
            $field_mappings['distrib_username'] = $username;
        } else {
            error_log("WARNING: 'distrib_username' field doesn't exist in the distributor_signup table");
        }
    }
    
    $update_fields = [];
    $update_values = [];
    $types = '';
    
    foreach ($field_mappings as $field => $value) {
        if (in_array($field, $available_columns)) {
            $update_fields[] = "$field = ?";
            $update_values[] = $value;
            if (strpos($field, 'age') !== false) {
                $types .= 'i';
            } else {
                $types .= 's';
            }
        } else {
            // Log that we're skipping a field because it doesn't exist in the table
            error_log("WARNING: Field '$field' doesn't exist in table '$table'. Skipping this field in the update.");
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
        // Create log table
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
    }
    
    // Log profile changes
    $changes_logged = [];
    
    // Get the list of available columns one more time to be safe
    $final_columns = [];
    $final_col_result = $conn->query("SHOW COLUMNS FROM $table");
    while ($col = $final_col_result->fetch_assoc()) {
        $final_columns[] = $col['Field'];
    }
    
    foreach ($field_mappings as $field => $new_value) {
        // Only process fields that actually exist in the table
        if (in_array($field, $final_columns)) {
            $old_value = $current_data[$field] ?? '';
            if ($old_value !== $new_value) {
                // Log the change
                $log_stmt = $conn->prepare("INSERT INTO $log_table (user_id, field_name, old_value, new_value, changed_by, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                
                $log_stmt->bind_param("issssss", $user_id, $field, $old_value, $new_value, $_SESSION['staff_id'], $ip_address, $user_agent);
                $log_stmt->execute();
                $log_stmt->close();
                
                $changes_logged[] = $field;
            }
        } else {
            error_log("WARNING: Skipping logging for field '$field' because it doesn't exist in table '$table'");
        }
    }
    
    // Log image change if applicable
    $old_image = $current_data['profile_image'] ?? '';
    if ($old_image !== $profile_image_name) {
        $log_stmt = $conn->prepare("INSERT INTO $log_table (user_id, field_name, old_value, new_value, changed_by, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $field_name = 'profile_image';
        
        $log_stmt->bind_param("issssss", $user_id, $field_name, $old_image, $profile_image_name, $_SESSION['staff_id'], $ip_address, $user_agent);
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
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    // Set error message
    $_SESSION['error'] = $e->getMessage();
    error_log("Error updating profile: " . $e->getMessage());
    
    // Delete uploaded image if there was an error
    if (isset($target_path) && file_exists($target_path)) {
        unlink($target_path);
    }
} finally {
    // Close connection
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->close();
    }
}

// Redirect back to edit profile page
$redirect_url = "s_edit_universal_profile.php?type=$user_type&id=$user_id";
header("Location: $redirect_url");
exit();
?>