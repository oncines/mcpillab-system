<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is admin
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

$employee_id = $_GET['id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if employee exists
    $check_query = "SELECT id FROM employees WHERE id = :employee_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':employee_id', $employee_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }
    
    // Delete attendance records first (to handle foreign key constraint)
    $delete_attendance_query = "DELETE FROM attendance WHERE employee_id = :employee_id";
    $delete_attendance_stmt = $db->prepare($delete_attendance_query);
    $delete_attendance_stmt->bindParam(':employee_id', $employee_id);
    $delete_attendance_stmt->execute();
    
    // Delete the employee
    $delete_query = "DELETE FROM employees WHERE id = :employee_id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(':employee_id', $employee_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete employee']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
