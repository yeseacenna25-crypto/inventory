<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "inventory_negrita");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Get the input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['user_type']) || !isset($input['user_id']) || empty($input['user_type']) || empty($input['user_id'])) {
        throw new Exception('User type and ID are required');
    }
    
    $user_type = strtolower(trim($input['user_type']));
    $user_id_to_delete = (int)$input['user_id'];
    $current_admin_id = $_SESSION['admin_id'];
    
    // Validate user type
    $allowed_types = ['admin', 'staff', 'distributor'];
    if (!in_array($user_type, $allowed_types)) {
        throw new Exception('Invalid user type');
    }
    
    // Configure table and column names based on user type
    $config = [
        'admin' => [
            'table' => 'admin_signup',
            'id_column' => 'admin_id',
            'log_table' => 'admin_profile_logs'
        ],
        'staff' => [
            'table' => 'staff_signup',
            'id_column' => 'staff_id',
            'log_table' => 'staff_profile_logs'
        ],
        'distributor' => [
            'table' => 'distributor_signup',
            'id_column' => 'distributor_id',
            'log_table' => 'distributor_profile_logs'
        ]
    ];
    
    $table = $config[$user_type]['table'];
    $id_column = $config[$user_type]['id_column'];
    $log_table = $config[$user_type]['log_table'];
    
    // Prevent admin from deleting themselves
    if ($user_type === 'admin' && $user_id_to_delete === $current_admin_id) {
        throw new Exception('You cannot delete your own account');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if user exists and get their information
    // First check if profile_image column exists in the table
    $columns_query = "SHOW COLUMNS FROM {$table} LIKE 'profile_image'";
    $columns_result = $conn->query($columns_query);
    $has_profile_image = ($columns_result && $columns_result->num_rows > 0);
    
    if ($has_profile_image) {
        $check_stmt = $conn->prepare("SELECT {$id_column}, first_name, last_name, profile_image FROM {$table} WHERE {$id_column} = ?");
    } else {
        $check_stmt = $conn->prepare("SELECT {$id_column}, first_name, last_name FROM {$table} WHERE {$id_column} = ?");
    }
    $check_stmt->bind_param("i", $user_id_to_delete);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception(ucfirst($user_type) . ' not found');
    }
    
    $user_data = $result->fetch_assoc();
    $check_stmt->close();
    
    // Special check for admin - prevent deleting the last admin
    if ($user_type === 'admin') {
        $count_stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM admin_signup");
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_data = $count_result->fetch_assoc();
        $count_stmt->close();
        
        if ($count_data['admin_count'] <= 1) {
            throw new Exception('Cannot delete the last admin account');
        }
    }
    
    // Check for existing orders by this user
    $orders_count = 0;
    $has_user_column = false;
    
    if ($user_type === 'staff' || $user_type === 'distributor') {
        $column_name = $user_type . '_id';
        
        // Check if the column exists in orders table
        $orders_columns = $conn->query("SHOW COLUMNS FROM orders LIKE '$column_name'");
        $has_user_column = ($orders_columns && $orders_columns->num_rows > 0);
        
        if ($has_user_column) {
            $orders_check = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE {$column_name} = ?");
            $orders_check->bind_param("i", $user_id_to_delete);
            $orders_check->execute();
            $orders_result = $orders_check->get_result();
            $orders_data = $orders_result->fetch_assoc();
            $orders_count = $orders_data['order_count'];
            $orders_check->close();
            
            // Update orders to set user_id to NULL instead of deleting them
            if ($orders_count > 0) {
                $update_orders = $conn->prepare("UPDATE orders SET {$column_name} = NULL WHERE {$column_name} = ?");
                $update_orders->bind_param("i", $user_id_to_delete);
                $update_orders->execute();
                $update_orders->close();
            }
        }
    }
    
    // Log the deletion activity
    $log_note = $orders_count > 0 ? "deleted - had {$orders_count} associated orders" : "deleted - no associated orders";
    $log_stmt = $conn->prepare("INSERT INTO {$log_table} (user_id, field_name, old_value, new_value, changed_by, ip_address, user_agent, change_date) VALUES (?, 'account_deleted', 'active', ?, ?, ?, ?, NOW())");
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $log_stmt->bind_param("issss", $user_id_to_delete, $log_note, $current_admin_id, $ip_address, $user_agent);
    $log_stmt->execute();
    $log_stmt->close();
    
    // Delete the user record
    $delete_stmt = $conn->prepare("DELETE FROM {$table} WHERE {$id_column} = ?");
    $delete_stmt->bind_param("i", $user_id_to_delete);
    
    if (!$delete_stmt->execute()) {
        throw new Exception('Failed to delete ' . $user_type . ' record');
    }
    
    $affected_rows = $delete_stmt->affected_rows;
    $delete_stmt->close();
    
    if ($affected_rows === 0) {
        throw new Exception('No ' . $user_type . ' record was deleted');
    }
    
    // Delete profile image if it exists
    if ($has_profile_image && !empty($user_data['profile_image'])) {
        $image_path = 'uploads/' . $user_data['profile_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    $message = ucfirst($user_type) . ' "' . $user_data['first_name'] . ' ' . $user_data['last_name'] . '" has been successfully deleted';
    if ($orders_count > 0) {
        $message .= '. Associated orders have been preserved with ' . $user_type . ' reference removed.';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    if (isset($conn)) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
