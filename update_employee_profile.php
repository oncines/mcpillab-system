<?php
require_once 'config.php';

// Admin-only endpoint
if (!is_admin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$employee_id = intval($_POST['employee_id'] ?? 0);
$section = $_POST['section'] ?? '';

if (!$employee_id || !$section) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Get the employee's user_id
    $stmt = $db->prepare("SELECT user_id, employee_id FROM employees WHERE id = :id");
    $stmt->execute([':id' => $employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }
    
    $user_id = $employee['user_id'];
    
    // Check if employee_profiles record exists
    $stmt = $db->prepare("SELECT id FROM employee_profiles WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $profile_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If profile doesn't exist, create it
    if (!$profile_exists) {
        $stmt = $db->prepare("
            INSERT INTO employee_profiles (user_id, employee_id) 
            VALUES (:user_id, :employee_id)
        ");
        $stmt->execute([
            ':user_id' => $user_id,
            ':employee_id' => $employee['employee_id']
        ]);
    }
    
    switch ($section) {
        case 'basic-info':
            // Update users table for name
            $stmt = $db->prepare("
                UPDATE users 
                SET full_name = :full_name 
                WHERE id = :user_id
            ");
            $stmt->execute([
                ':full_name' => sanitize_input($_POST['first_name'] . ' ' . $_POST['last_name']),
                ':user_id' => $user_id
            ]);
            
            // Update employees table
            $stmt = $db->prepare("
                UPDATE employees 
                SET first_name = :first_name, 
                    last_name = :last_name, 
                    phone = :phone 
                WHERE id = :employee_id
            ");
            $stmt->execute([
                ':first_name' => sanitize_input($_POST['first_name']),
                ':last_name' => sanitize_input($_POST['last_name']),
                ':phone' => sanitize_input($_POST['phone']),
                ':employee_id' => $employee_id
            ]);
            
            // Update employee_profiles table
            $stmt = $db->prepare("
                UPDATE employee_profiles 
                SET gender = :gender,
                    phone = :phone,
                    place_of_birth = :place_of_birth,
                    birth_date = :birth_date,
                    marital_status = :marital_status,
                    religion = :religion
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                ':gender' => sanitize_input($_POST['gender'] ?? ''),
                ':phone' => sanitize_input($_POST['phone']),
                ':place_of_birth' => sanitize_input($_POST['place_of_birth'] ?? ''),
                ':birth_date' => sanitize_input($_POST['birth_date'] ?? ''),
                ':marital_status' => sanitize_input($_POST['marital_status'] ?? ''),
                ':religion' => sanitize_input($_POST['religion'] ?? ''),
                ':user_id' => $user_id
            ]);
            break;
            
        case 'address':
            $stmt = $db->prepare("
                UPDATE employee_profiles 
                SET citizen_address = :citizen_address,
                    residential_address = :residential_address
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                ':citizen_address' => sanitize_input($_POST['citizen_address'] ?? ''),
                ':residential_address' => sanitize_input($_POST['residential_address'] ?? ''),
                ':user_id' => $user_id
            ]);
            break;
            
        case 'emergency':
            $stmt = $db->prepare("
                UPDATE employee_profiles 
                SET ec_name = :ec_name,
                    ec_relationship = :ec_relationship,
                    ec_phone = :ec_phone
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                ':ec_name' => sanitize_input($_POST['ec_name'] ?? ''),
                ':ec_relationship' => sanitize_input($_POST['ec_relationship'] ?? ''),
                ':ec_phone' => sanitize_input($_POST['ec_phone'] ?? ''),
                ':user_id' => $user_id
            ]);
            break;
            
        case 'education':
            // Delete existing education records
            $stmt = $db->prepare("DELETE FROM employee_education WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            
            // Insert new education records
            if (isset($_POST['education']) && is_array($_POST['education'])) {
                foreach ($_POST['education'] as $edu) {
                    if (!empty($edu['degree']) || !empty($edu['school'])) {
                        $stmt = $db->prepare("
                            INSERT INTO employee_education 
                            (user_id, degree, school, field, gpa, year_start, year_end)
                            VALUES (:user_id, :degree, :school, :field, :gpa, :year_start, :year_end)
                        ");
                        $stmt->execute([
                            ':user_id' => $user_id,
                            ':degree' => sanitize_input($edu['degree'] ?? ''),
                            ':school' => sanitize_input($edu['school'] ?? ''),
                            ':field' => sanitize_input($edu['field'] ?? ''),
                            ':gpa' => sanitize_input($edu['gpa'] ?? ''),
                            ':year_start' => sanitize_input($edu['year_start'] ?? ''),
                            ':year_end' => sanitize_input($edu['year_end'] ?? '')
                        ]);
                    }
                }
            }
            break;
            
        case 'family':
            // Delete existing family records
            $stmt = $db->prepare("DELETE FROM employee_family WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
            
            // Insert new family records
            if (isset($_POST['family']) && is_array($_POST['family'])) {
                foreach ($_POST['family'] as $fam) {
                    if (!empty($fam['type']) || !empty($fam['name'])) {
                        $stmt = $db->prepare("
                            INSERT INTO employee_family 
                            (user_id, family_type, person_name)
                            VALUES (:user_id, :family_type, :person_name)
                        ");
                        $stmt->execute([
                            ':user_id' => $user_id,
                            ':family_type' => sanitize_input($fam['type'] ?? ''),
                            ':person_name' => sanitize_input($fam['name'] ?? '')
                        ]);
                    }
                }
            }
            break;
            
        case 'employee-details':
            $stmt = $db->prepare("
                UPDATE employees 
                SET department = :department,
                    position = :position,
                    hire_date = :hire_date,
                    status = :status
                WHERE id = :employee_id
            ");
            $stmt->execute([
                ':department' => sanitize_input($_POST['department'] ?? ''),
                ':position' => sanitize_input($_POST['position'] ?? ''),
                ':hire_date' => sanitize_input($_POST['hire_date'] ?? ''),
                ':status' => sanitize_input($_POST['status'] ?? 'active'),
                ':employee_id' => $employee_id
            ]);
            break;
            
        case 'payroll-details':
            $stmt = $db->prepare("
                UPDATE employee_profiles 
                SET basic_salary = :basic_salary,
                    bank_name = :bank_name,
                    bank_account = :bank_account,
                    tax_id = :tax_id,
                    sss_number = :sss_number,
                    philhealth = :philhealth,
                    pagibig = :pagibig
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                ':basic_salary' => sanitize_input($_POST['basic_salary'] ?? ''),
                ':bank_name' => sanitize_input($_POST['bank_name'] ?? ''),
                ':bank_account' => sanitize_input($_POST['bank_account'] ?? ''),
                ':tax_id' => sanitize_input($_POST['tax_id'] ?? ''),
                ':sss_number' => sanitize_input($_POST['sss_number'] ?? ''),
                ':philhealth' => sanitize_input($_POST['philhealth'] ?? ''),
                ':pagibig' => sanitize_input($_POST['pagibig'] ?? ''),
                ':user_id' => $user_id
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid section']);
            exit;
    }
    
    echo json_encode(['success' => true, 'message' => 'Section updated successfully']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
