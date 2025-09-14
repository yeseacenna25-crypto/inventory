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
    header("Location: edit_profile.php");
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    $_SESSION['error'] = "Database connection failed: " . $conn->connect_error;
    header("Location: edit_profile.php");
    exit();
}

try {
    // Validate and sanitize input
    $admin_id = $_SESSION['admin_id'];
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
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
    
    // Get current admin data for logging changes
    $current_stmt = $conn->prepare("SELECT first_name, middle_name, last_name, email, phone, department, position, bio, profile_image FROM admin_signup WHERE admin_id = ?");
    $current_stmt->bind_param("i", $admin_id);
    $current_stmt->execute();
    $current_data = $current_stmt->get_result()->fetch_assoc();
    $current_stmt->close();
    
    if (!$current_data) {
        throw new Exception("Admin record not found.");
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Handle profile image upload
    $profile_image_name = $current_data['profile_image']; // Keep current image by default
    
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
        $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $profile_image_name = 'admin_' . $admin_id . '_' . time() . '.' . $extension;
        $target_path = $upload_dir . $profile_image_name;
        
        // Move uploaded file
        if (!move_uploaded_file($image['tmp_name'], $target_path)) {
            throw new Exception("Failed to upload image.");
        }
        
        // Delete old image if it exists and is not the default
        if ($current_data['profile_image'] && $current_data['profile_image'] !== $profile_image_name) {
            $old_image_path = $upload_dir . $current_data['profile_image'];
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }
    }
    
    // Prepare update query based on available columns
    $columns_to_update = ['first_name', 'middle_name', 'last_name'];
    $values = [$first_name, $middle_name, $last_name];
    $types = 'sss';
    
    // Check if additional columns exist and add them if they do
    $column_check = $conn->query("SHOW COLUMNS FROM admin_signup");
    $available_columns = [];
    while ($col = $column_check->fetch_assoc()) {
        $available_columns[] = $col['Field'];
    }
    
    if (in_array('email', $available_columns) && !empty($email)) {
        $columns_to_update[] = 'email';
        $values[] = $email;
        $types .= 's';
    }
    
    if (in_array('phone', $available_columns) && !empty($phone)) {
        $columns_to_update[] = 'phone';
        $values[] = $phone;
        $types .= 's';
    }
    
    if (in_array('department', $available_columns) && !empty($department)) {
        $columns_to_update[] = 'department';
        $values[] = $department;
        $types .= 's';
    }
    
    if (in_array('position', $available_columns) && !empty($position)) {
        $columns_to_update[] = 'position';
        $values[] = $position;
        $types .= 's';
    }
    
    if (in_array('bio', $available_columns) && !empty($bio)) {
        $columns_to_update[] = 'bio';
        $values[] = $bio;
        $types .= 's';
    }
    
    if (in_array('profile_image', $available_columns)) {
        $columns_to_update[] = 'profile_image';
        $values[] = $profile_image_name;
        $types .= 's';
    }
    
    if (in_array('profile_updated_at', $available_columns)) {
        $columns_to_update[] = 'profile_updated_at';
        $values[] = date('Y-m-d H:i:s');
        $types .= 's';
    }
    
    // Build and execute update query
    $set_clause = implode(' = ?, ', $columns_to_update) . ' = ?';
    $sql = "UPDATE admin_signup SET $set_clause WHERE admin_id = ?";
    $values[] = $admin_id;
    $types .= 'i';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update profile: " . $stmt->error);
    }
    
    $stmt->close();
    
    // Log profile changes if logging table exists
    if (in_array('admin_profile_logs', $available_columns)) {
        $changes_logged = [];
        
        // Check what changed and log it
        $fields_to_check = [
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'department' => $department,
            'position' => $position,
            'bio' => $bio
        ];
        
        foreach ($fields_to_check as $field => $new_value) {
            if (isset($current_data[$field]) && $current_data[$field] !== $new_value) {
                // Log the change
                $log_stmt = $conn->prepare("INSERT INTO admin_profile_logs (admin_id, field_changed, old_value, new_value, changed_by, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                
                $log_stmt->bind_param("issssss", $admin_id, $field, $current_data[$field], $new_value, $admin_id, $ip_address, $user_agent);
                $log_stmt->execute();
                $log_stmt->close();
                
                $changes_logged[] = $field;
            }
        }
        
        // Log image change if applicable
        if ($current_data['profile_image'] !== $profile_image_name) {
            $log_stmt = $conn->prepare("INSERT INTO admin_profile_logs (admin_id, field_changed, old_value, new_value, changed_by, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $field_name = 'profile_image';
            
            $log_stmt->bind_param("issssss", $admin_id, $field_name, $current_data['profile_image'], $profile_image_name, $admin_id, $ip_address, $user_agent);
            $log_stmt->execute();
            $log_stmt->close();
            
            $changes_logged[] = 'profile_image';
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Set success message
    $_SESSION['success'] = "Profile updated successfully!";
    
    // Update session data if name changed
    if ($current_data['first_name'] !== $first_name) {
        $_SESSION['admin_name'] = $first_name;
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
header("Location: edit_profile.php");
exit();
?>
