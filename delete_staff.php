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
    
    if (!isset($input['staff_id']) || empty($input['staff_id'])) {
        throw new Exception('Staff ID is required');
    }
    
    $staff_id_to_delete = (int)$input['staff_id'];
    $current_admin_id = $_SESSION['admin_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if staff exists and get their information
    $columns_query = "SHOW COLUMNS FROM staff_signup LIKE 'profile_image'";
    $columns_result = $conn->query($columns_query);
    $has_profile_image = ($columns_result && $columns_result->num_rows > 0);

    if ($has_profile_image) {
        $check_stmt = $conn->prepare("SELECT staff_id, staff_fname, staff_mname, staff_lname, profile_image FROM staff_signup WHERE staff_id = ?");
    } else {
        $check_stmt = $conn->prepare("SELECT staff_id, staff_fname, staff_mname, staff_lname FROM staff_signup WHERE staff_id = ?");
    }
    $check_stmt->bind_param("i", $staff_id_to_delete);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Staff member not found');
    }

    $staff_data = $result->fetch_assoc();
    $check_stmt->close();
    
    // Check for existing orders by this staff member
    // First check if staff_id column exists in orders table
    $orders_columns = $conn->query("SHOW COLUMNS FROM orders LIKE 'staff_id'");
    $has_staff_id_column = ($orders_columns && $orders_columns->num_rows > 0);
    
    $orders_count = 0;
    if ($has_staff_id_column) {
        $orders_check = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE staff_id = ?");
        $orders_check->bind_param("i", $staff_id_to_delete);
        $orders_check->execute();
        $orders_result = $orders_check->get_result();
        $orders_data = $orders_result->fetch_assoc();
        $orders_count = $orders_data['order_count'];
        $orders_check->close();
    }
    
    if ($orders_data['order_count'] > 0) {
        // If staff has orders, we might want to handle this differently
        // For now, we'll allow deletion but log it
        $log_note = "Staff had " . $orders_data['order_count'] . " associated orders";
    } else {
        $log_note = "No associated orders found";
    }
    
    // Log the deletion activity in staff_profile_logs table
    $log_stmt = $conn->prepare("INSERT INTO staff_profile_logs (staff_id, field_changed, old_value, new_value, changed_by, ip_address, user_agent, created_at) VALUES (?, 'account_deleted', 'active', ?, ?, ?, ?, NOW())");
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $log_value = "deleted - " . $log_note;
    $log_stmt->bind_param("issss", $staff_id_to_delete, $log_value, $current_admin_id, $ip_address, $user_agent);
    $log_stmt->execute();
    $log_stmt->close();
    
    // Update orders to set staff_id to NULL instead of deleting them (only if column exists)
    if ($has_staff_id_column && $orders_count > 0) {
        $update_orders = $conn->prepare("UPDATE orders SET staff_id = NULL WHERE staff_id = ?");
        $update_orders->bind_param("i", $staff_id_to_delete);
        $update_orders->execute();
        $update_orders->close();
    }
    
    // Delete the staff record
    $delete_stmt = $conn->prepare("DELETE FROM staff_signup WHERE staff_id = ?");
    $delete_stmt->bind_param("i", $staff_id_to_delete);
    
    if (!$delete_stmt->execute()) {
        throw new Exception('Failed to delete staff record');
    }
    
    $affected_rows = $delete_stmt->affected_rows;
    $delete_stmt->close();
    
    if ($affected_rows === 0) {
        throw new Exception('No staff record was deleted');
    }
    
    // Delete profile image if it exists
    if ($has_profile_image && !empty($staff_data['profile_image'])) {
        $image_path = 'uploads/' . $staff_data['profile_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    $message = 'Staff member "' . ($staff_data['staff_fname'] ?? '') . ' ' . ($staff_data['staff_lname'] ?? '') . '" has been successfully deleted';
    if ($has_staff_id_column && $orders_count > 0) {
        $message .= '. Associated orders have been preserved with staff reference removed.';
    } else if (!$has_staff_id_column) {
        $message .= '. Orders table does not track staff references.';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
} finally {
    $conn->close();
}
?>
