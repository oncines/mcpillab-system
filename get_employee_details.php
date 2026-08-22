<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

$employee_id = $_GET['id'];

try {
    $employee = get_employee_by_id($employee_id);
    
    if ($employee) {
        // Get employee profile data
        $employee_profile = null;
        try {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->prepare("
                SELECT 
                    ep.*,
                    u.full_name,
                    u.email,
                    u.role
                FROM employee_profiles ep
                LEFT JOIN users u ON ep.user_id = u.id
                WHERE ep.user_id = :user_id OR ep.employee_id = :employee_id
                LIMIT 1
            ");
            $stmt->execute([':user_id' => $employee['user_id'] ?? 0, ':employee_id' => $employee['employee_id'] ?? '']);
            $employee_profile = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Profile table might not exist
        }

        // Get education records
        $education = [];
        try {
            $database = new Database();
            $db = $database->getConnection();
            if ($db->query("SHOW TABLES LIKE 'employee_education'")->rowCount() > 0) {
                $eduStmt = $db->prepare("
                    SELECT degree, school, field, gpa, year_start, year_end
                    FROM employee_education
                    WHERE user_id = :user_id
                    ORDER BY year_start DESC
                ");
                $eduStmt->execute([':user_id' => $employee['user_id'] ?? 0]);
                $education = $eduStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            // Education table might not exist
        }

        // Get family records
        $family = [];
        try {
            $database = new Database();
            $db = $database->getConnection();
            if ($db->query("SHOW TABLES LIKE 'employee_family'")->rowCount() > 0) {
                $famStmt = $db->prepare("
                    SELECT family_type AS type, person_name AS name
                    FROM employee_family
                    WHERE user_id = :user_id
                    ORDER BY id ASC
                ");
                $famStmt->execute([':user_id' => $employee['user_id'] ?? 0]);
                $family = $famStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            // Family table might not exist
        }

        echo json_encode([
            'success' => true,
            'employee' => $employee,
            'profile' => $employee_profile,
            'education' => $education,
            'family' => $family
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
