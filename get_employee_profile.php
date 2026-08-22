<?php
require_once 'config.php';

// Admin-only endpoint
if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$user_id = intval($_GET['user_id'] ?? 0);
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

global $pdo;

try {
    // ── Core user + employee profile ──────────────────────────────────
    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.full_name,
            u.email,
            u.role,

            ep.employee_id,
            ep.photo,
            ep.gender,
            ep.phone,
            ep.place_of_birth,
            ep.birth_date,
            ep.marital_status,
            ep.religion,

            ep.citizen_address,
            ep.residential_address,

            ep.ec_name,
            ep.ec_relationship,
            ep.ec_phone,

            ep.department,
            ep.date_hired,
            ep.status,

            ep.basic_salary,
            ep.bank_name,
            ep.bank_account,
            ep.tax_id,
            ep.sss_number,
            ep.philhealth,
            ep.pagibig

        FROM users u
        LEFT JOIN employee_profiles ep ON u.id = ep.user_id
        WHERE u.id = :user_id
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $user_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }

    // ── Education records ─────────────────────────────────────────────
    // Adjust table/column names to match your actual schema
    $education = [];
    if ($pdo->query("SHOW TABLES LIKE 'employee_education'")->rowCount() > 0) {
        $eduStmt = $pdo->prepare("
            SELECT degree, school, field, gpa, year_start, year_end
            FROM employee_education
            WHERE user_id = :user_id
            ORDER BY year_start DESC
        ");
        $eduStmt->execute([':user_id' => $user_id]);
        $education = $eduStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Family records ────────────────────────────────────────────────
    // Adjust table/column names to match your actual schema
    $family = [];
    if ($pdo->query("SHOW TABLES LIKE 'employee_family'")->rowCount() > 0) {
        $famStmt = $pdo->prepare("
            SELECT family_type AS type, person_name AS name
            FROM employee_family
            WHERE user_id = :user_id
            ORDER BY id ASC
        ");
        $famStmt->execute([':user_id' => $user_id]);
        $family = $famStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $employee['education'] = $education;
    $employee['family']    = $family;

    echo json_encode(['success' => true, 'employee' => $employee]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
