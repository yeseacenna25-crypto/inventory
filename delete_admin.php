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
    
    if (!isset($input['admin_id']) || empty($input['admin_id'])) {
        throw new Exception('Admin ID is required');
    }
    
    $admin_id_to_delete = (int)$input['admin_id'];
    $current_admin_id = $_SESSION['admin_id'];
    
    // Prevent admin from deleting themselves
    if ($admin_id_to_delete === $current_admin_id) {
        throw new Exception('You cannot delete your own account');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if admin exists and get their information
    // First check if profile_image column exists
    $columns_query = "SHOW COLUMNS FROM admin_signup LIKE 'profile_image'";
    $columns_result = $conn->query($columns_query);
    $has_profile_image = ($columns_result && $columns_result->num_rows > 0);
    
    if ($has_profile_image) {
        $check_stmt = $conn->prepare("SELECT admin_id, admin_fname, admin_lname, profile_image FROM admin_signup WHERE admin_id = ?");
    } else {
        $check_stmt = $conn->prepare("SELECT admin_id, admin_fname, admin_lname FROM admin_signup WHERE admin_id = ?");
    }
    $check_stmt->bind_param("i", $admin_id_to_delete);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Admin not found');
    }
    
    $admin_data = $result->fetch_assoc();
    $check_stmt->close();
    
    // Check if this is the last admin (prevent deleting all admins)
    $count_stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM admin_signup");
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_data = $count_result->fetch_assoc();
    $count_stmt->close();
    
    if ($count_data['admin_count'] <= 1) {
        throw new Exception('Cannot delete the last admin account');
    }
    
    // Log the deletion activity
    $log_stmt = $conn->prepare("INSERT INTO admin_profile_logs (admin_id, field_changed, old_value, new_value, changed_by, ip_address, user_agent, created_at) VALUES (?, 'account_deleted', 'active', 'deleted', ?, ?, ?, NOW())");
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $log_stmt->bind_param("iiss", $admin_id_to_delete, $current_admin_id, $ip_address, $user_agent);
    $log_stmt->execute();
    $log_stmt->close();
    
    // Delete the admin record
    $delete_stmt = $conn->prepare("DELETE FROM admin_signup WHERE admin_id = ?");
    $delete_stmt->bind_param("i", $admin_id_to_delete);
    
    if (!$delete_stmt->execute()) {
        throw new Exception('Failed to delete admin record');
    }
    
    $affected_rows = $delete_stmt->affected_rows;
    $delete_stmt->close();
    
    if ($affected_rows === 0) {
        throw new Exception('No admin record was deleted');
    }
    
    // Delete profile image if it exists
    if ($has_profile_image && !empty($admin_data['profile_image'])) {
        $image_path = 'uploads/' . $admin_data['profile_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Admin "' . $admin_data['admin_fname'] . ' ' . $admin_data['admin_lname'] . '" has been successfully deleted'
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
