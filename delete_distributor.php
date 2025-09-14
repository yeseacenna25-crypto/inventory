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
    // Get the input data - first try to get it from php://input
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    
    // Debug logging
    error_log("Raw input: " . $raw_input);
    error_log("Decoded input: " . print_r($input, true));
    error_log("JSON decode error: " . json_last_error_msg());
    
    // If JSON decoding failed, try to get from $_POST
    if (json_last_error() !== JSON_ERROR_NONE) {
        $input = $_POST;
        error_log("Using POST data instead: " . print_r($input, true));
    }
    
    // Check if distributor_id exists and is valid
    if (!isset($input['distributor_id'])) {
        throw new Exception('Distributor ID parameter is missing');
    }
    
    $distributor_id = $input['distributor_id'];
    
    // Validate the distributor ID
    if (empty($distributor_id) || !is_numeric($distributor_id) || intval($distributor_id) <= 0) {
        $error_message = 'Invalid Distributor ID';
        if (isset($input['distributor_id'])) {
            $error_message .= ' (received: ' . var_export($input['distributor_id'], true) . ')';
        }
        throw new Exception($error_message);
    }
    
    $distributor_id_to_delete = intval($distributor_id);
    $current_admin_id = $_SESSION['admin_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if distributor exists and get their information
    $check_stmt = $conn->prepare("SELECT distributor_id, distrib_fname, distrib_lname, distrib_profile_image FROM distributor_signup WHERE distributor_id = ?");
    $check_stmt->bind_param("i", $distributor_id_to_delete);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Distributor not found');
    }
    
    $distributor_data = $result->fetch_assoc();
    $check_stmt->close();
    
    // Check for existing orders by this distributor
    // First check if distributor_id column exists in orders table
    $orders_columns = $conn->query("SHOW COLUMNS FROM orders LIKE 'distributor_id'");
    $has_distributor_id_column = ($orders_columns && $orders_columns->num_rows > 0);
    
    $orders_count = 0;
    if ($has_distributor_id_column) {
        $orders_check = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE distributor_id = ?");
        $orders_check->bind_param("i", $distributor_id_to_delete);
        $orders_check->execute();
        $orders_result = $orders_check->get_result();
        $orders_data = $orders_result->fetch_assoc();
        $orders_count = $orders_data['order_count'];
        $orders_check->close();
    }
    
    if ($orders_count > 0) {
        // If distributor has orders, we might want to handle this differently
        // For now, we'll allow deletion but log it
        $log_note = "Distributor had " . $orders_count . " associated orders";
    } else {
        $log_note = "No associated orders found";
    }
    
    // Log the deletion activity in distributor_profile_logs table (if table exists)
    $log_table_check = $conn->query("SHOW TABLES LIKE 'distributor_profile_logs'");
    if ($log_table_check && $log_table_check->num_rows > 0) {
        try {
            $log_stmt = $conn->prepare("INSERT INTO distributor_profile_logs (user_id, field_name, old_value, new_value, changed_by, ip_address, user_agent, change_date) VALUES (?, 'account_deleted', 'active', ?, ?, ?, ?, NOW())");
            
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $log_value = "deleted - " . $log_note;
            
            $log_stmt->bind_param("issss", $distributor_id_to_delete, $log_value, $current_admin_id, $ip_address, $user_agent);
            $log_stmt->execute();
            $log_stmt->close();
        } catch (Exception $log_error) {
            // Log error but don't stop the deletion process
            error_log("Logging error: " . $log_error->getMessage());
        }
    }
    
    // Update orders to set distributor_id to NULL instead of deleting them (only if column exists)
    if ($has_distributor_id_column && $orders_count > 0) {
        $update_orders = $conn->prepare("UPDATE orders SET distributor_id = NULL WHERE distributor_id = ?");
        $update_orders->bind_param("i", $distributor_id_to_delete);
        $update_orders->execute();
        $update_orders->close();
    }
    
    // Delete the distributor record
    $delete_stmt = $conn->prepare("DELETE FROM distributor_signup WHERE distributor_id = ?");
    $delete_stmt->bind_param("i", $distributor_id_to_delete);
    
    if (!$delete_stmt->execute()) {
        throw new Exception('Failed to delete distributor record');
    }
    
    $affected_rows = $delete_stmt->affected_rows;
    $delete_stmt->close();
    
    if ($affected_rows === 0) {
        throw new Exception('No distributor record was deleted');
    }
    
    // Delete profile image if it exists
    if (!empty($distributor_data['distrib_profile_image'])) {
        $image_path = 'uploads/' . $distributor_data['distrib_profile_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    $message = 'Distributor "' . $distributor_data['distrib_fname'] . ' ' . $distributor_data['distrib_lname'] . '" has been successfully deleted';
    if ($has_distributor_id_column && $orders_count > 0) {
        $message .= '. Associated orders have been preserved with distributor reference removed.';
    } else if (!$has_distributor_id_column) {
        $message .= '. Orders table does not track distributor references.';
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
