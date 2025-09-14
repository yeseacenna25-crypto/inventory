<?php
session_start();
$_SESSION['admin_id'] = 1; // Set for testing

echo "<h2>Direct Delete Test</h2>";

// Test the delete function directly
if (isset($_GET['test_delete'])) {
    header('Content-Type: application/json');
    
    $conn = new mysqli("localhost", "root", "", "inventory_negrita");
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Connection failed']);
        exit();
    }
    
    try {
        // Test admin delete (but don't actually delete)
        $admin_id = 2; // Use a test ID
        
        // Check if admin exists - this is where the error might occur
        $check_stmt = $conn->prepare("SELECT admin_id, first_name, last_name FROM admin_signup WHERE admin_id = ?");
        $check_stmt->bind_param("i", $admin_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin_data = $result->fetch_assoc();
            echo json_encode([
                'success' => true, 
                'message' => 'Test successful - would delete: ' . $admin_data['first_name'] . ' ' . $admin_data['last_name']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Admin not found']);
        }
        
        $check_stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Test</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<h3>Test Delete Functionality</h3>

<button onclick="testDelete()">Test Delete Function</button>

<script>
function testDelete() {
    fetch('?test_delete=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Success!', data.message, 'success');
            } else {
                Swal.fire('Error!', data.message, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error!', 'Network error: ' + error, 'error');
        });
}
</script>

<h3>Manual Delete Test</h3>
<p>Click this button to test the actual delete PHP file:</p>
<button onclick="testManualDelete()">Test Manual Delete</button>

<script>
function testManualDelete() {
    // Test the delete_admin.php file directly
    fetch('delete_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({admin_id: 999}) // Use non-existent ID for safety
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Success!', data.message, 'success');
        } else {
            Swal.fire('Expected Error!', data.message, 'info'); // Expected since ID 999 doesn't exist
        }
    })
    .catch(error => {
        Swal.fire('Error!', 'Network error: ' + error, 'error');
    });
}
</script>

</body>
</html>
