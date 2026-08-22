<?php
// Additional Functions for MCPIL Laboratory Management System

// User Authentication Functions
function login_user($email, $password, $selected_role = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, username, email, password, full_name, role FROM users WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if password is correct
        if (password_verify($password, $user['password'])) {
            // Check if role matches (if role is specified)
            if ($selected_role && $user['role'] !== $selected_role) {
                return false;
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            
            return true;
        }
    }
    
    return false;
}

function register_user($username, $email, $password, $full_name, $role = 'employee') {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Check if user already exists
        $check_query = "SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':username', $username);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            return false; // User already exists
        }
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $query = "INSERT INTO users (username, email, password, full_name, role) VALUES (:username, :email, :password, :full_name, :role)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':role', $role);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create user");
        }
        
        $user_id = $db->lastInsertId();
        
        // If role is 'employee', create employee profile automatically
        if ($role === 'employee') {
            // Split full name into first and last name
            $name_parts = explode(' ', $full_name, 2);
            $first_name = $name_parts[0];
            $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
            
            // Generate employee ID
            $employee_id = generate_employee_id();
            
            // Insert employee profile
            $emp_query = "INSERT INTO employees (user_id, employee_id, first_name, last_name, email, hire_date, status) 
                          VALUES (:user_id, :employee_id, :first_name, :last_name, :email, CURDATE(), 'active')";
            $emp_stmt = $db->prepare($emp_query);
            $emp_stmt->bindParam(':user_id', $user_id);
            $emp_stmt->bindParam(':employee_id', $employee_id);
            $emp_stmt->bindParam(':first_name', $first_name);
            $emp_stmt->bindParam(':last_name', $last_name);
            $emp_stmt->bindParam(':email', $email);
            
            if (!$emp_stmt->execute()) {
                throw new Exception("Failed to create employee profile");
            }
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Registration error: " . $e->getMessage());
        return false;
    }
}

// Employee ID Generation Function
function generate_employee_id() {
    $database = new Database();
    $db = $database->getConnection();

    // A row can be deleted or an ID can be entered manually, so a row count
    // is not a safe way to calculate the next employee number.  Start after
    // the highest ID for this year and confirm that the candidate is unused.
    $prefix = 'EMP' . date('Y');
    $stmt = $db->prepare("SELECT employee_id FROM employees WHERE employee_id LIKE :prefix ORDER BY employee_id DESC");
    $stmt->execute([':prefix' => $prefix . '%']);

    $highest = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $employee_id) {
        if (preg_match('/^' . preg_quote($prefix, '/') . '(\\d+)$/', $employee_id, $matches)) {
            $highest = max($highest, (int) $matches[1]);
        }
    }

    do {
        $highest++;
        $candidate = $prefix . str_pad($highest, 3, '0', STR_PAD_LEFT);
        $check = $db->prepare("SELECT 1 FROM employees WHERE employee_id = :employee_id LIMIT 1");
        $check->execute([':employee_id' => $candidate]);
    } while ($check->fetchColumn());

    return $candidate;
}

// Purchase Order Functions
function create_purchase_order($po_number, $supplier_id, $order_date, $expected_delivery_date, $items, $notes, $created_by) {
    error_log("create_purchase_order called with: po_number=$po_number, supplier_id=$supplier_id, items_count=" . count($items));
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Get store name from user session
        $store_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] . ' Store' : 'Unknown Store';
        error_log("Store name: $store_name");
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($items as $item) {
            $total_amount += $item['quantity'] * $item['unit_price'];
        }
        error_log("Total amount calculated: $total_amount");
        
        // Check if PO number already exists
        $check_query = "SELECT COUNT(*) as count FROM purchase_orders WHERE po_number = :po_number";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':po_number', $po_number);
        $check_stmt->execute();
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists['count'] > 0) {
            error_log("PO number already exists: $po_number");
            throw new Exception("Purchase Order number already exists");
        }
        
        // Check if supplier exists
        $supplier_check = "SELECT COUNT(*) as count FROM suppliers WHERE id = :supplier_id";
        $supplier_stmt = $db->prepare($supplier_check);
        $supplier_stmt->bindParam(':supplier_id', $supplier_id);
        $supplier_stmt->execute();
        $supplier_exists = $supplier_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($supplier_exists['count'] == 0) {
            error_log("Supplier does not exist: $supplier_id");
            throw new Exception("Invalid supplier selected");
        }
        
        // Insert purchase order
        $query = "INSERT INTO purchase_orders (po_number, supplier_id, store_name, order_date, expected_delivery_date, total_amount, notes, created_by, status) 
                  VALUES (:po_number, :supplier_id, :store_name, :order_date, :expected_delivery_date, :total_amount, :notes, :created_by, 'Pending')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':po_number', $po_number);
        $stmt->bindParam(':supplier_id', $supplier_id);
        $stmt->bindParam(':store_name', $store_name);
        $stmt->bindParam(':order_date', $order_date);
        $stmt->bindParam(':expected_delivery_date', $expected_delivery_date);
        $stmt->bindParam(':total_amount', $total_amount);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':created_by', $created_by);
        
        if (!$stmt->execute()) {
            $error_info = $stmt->errorInfo();
            error_log("Failed to insert purchase order: " . $error_info[2]);
            throw new Exception("Failed to insert purchase order: " . $error_info[2]);
        }
        
        $po_id = $db->lastInsertId();
        error_log("Purchase order inserted with ID: $po_id");
        
        // Insert purchase order items
        foreach ($items as $item) {
            $total_price = $item['quantity'] * $item['unit_price'];
            $item_query = "INSERT INTO purchase_order_items (po_id, item_name, quantity, unit_price, total_price) 
                           VALUES (:po_id, :item_name, :quantity, :unit_price, :total_price)";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->bindParam(':po_id', $po_id);
            $item_stmt->bindParam(':item_name', $item['item_name']);
            $item_stmt->bindParam(':quantity', $item['quantity']);
            $item_stmt->bindParam(':unit_price', $item['unit_price']);
            $item_stmt->bindParam(':total_price', $total_price);
            
            if (!$item_stmt->execute()) {
                $error_info = $item_stmt->errorInfo();
                error_log("Failed to insert item: " . $error_info[2]);
                throw new Exception("Failed to insert item: " . $error_info[2]);
            }
        }
        
        $db->commit();
        error_log("Purchase order creation completed successfully");
        return $po_id;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Purchase Order creation error: " . $e->getMessage());
        return false;
    }
}

function update_purchase_order($po_id, $po_number, $supplier_id, $order_date, $expected_delivery_date, $items, $notes) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Calculate total amount
        $total_amount = 0;
        foreach ($items as $item) {
            $total_amount += $item['quantity'] * $item['unit_price'];
        }
        
        // Update purchase order
        $query = "UPDATE purchase_orders 
                  SET po_number = :po_number, supplier_id = :supplier_id, order_date = :order_date, 
                      expected_delivery_date = :expected_delivery_date, total_amount = :total_amount, notes = :notes 
                  WHERE id = :po_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':po_id', $po_id);
        $stmt->bindParam(':po_number', $po_number);
        $stmt->bindParam(':supplier_id', $supplier_id);
        $stmt->bindParam(':order_date', $order_date);
        $stmt->bindParam(':expected_delivery_date', $expected_delivery_date);
        $stmt->bindParam(':total_amount', $total_amount);
        $stmt->bindParam(':notes', $notes);
        $stmt->execute();
        
        // Delete existing items
        $delete_query = "DELETE FROM purchase_order_items WHERE po_id = :po_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':po_id', $po_id);
        $delete_stmt->execute();
        
        // Insert updated items
        foreach ($items as $item) {
            $total_price = $item['quantity'] * $item['unit_price'];
            $item_query = "INSERT INTO purchase_order_items (po_id, item_name, quantity, unit_price, total_price) 
                           VALUES (:po_id, :item_name, :quantity, :unit_price, :total_price)";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->bindParam(':po_id', $po_id);
            $item_stmt->bindParam(':item_name', $item['item_name']);
            $item_stmt->bindParam(':quantity', $item['quantity']);
            $item_stmt->bindParam(':unit_price', $item['unit_price']);
            $item_stmt->bindParam(':total_price', $total_price);
            $item_stmt->execute();
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function get_purchase_orders($limit = 10, $offset = 0) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT po.*, s.name as supplier_name, u.full_name as created_by_name 
              FROM purchase_orders po 
              LEFT JOIN suppliers s ON po.supplier_id = s.id 
              LEFT JOIN users u ON po.created_by = u.id 
              ORDER BY po.created_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Employee Functions
function get_employees($limit = 10, $offset = 0) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT e.*, u.username, u.role 
              FROM employees e 
              LEFT JOIN users u ON e.user_id = u.id 
              ORDER BY e.created_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_employees_with_filters($limit = 10, $offset = 0, $search = '', $department_filter = '', $status_filter = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT e.*, u.username, u.role 
              FROM employees e 
              LEFT JOIN users u ON e.user_id = u.id 
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (e.first_name LIKE :search OR e.last_name LIKE :search OR e.email LIKE :search OR e.employee_id LIKE :search)";
        $search_param = '%' . $search . '%';
        $params[':search'] = $search_param;
    }
    
    if (!empty($department_filter)) {
        $query .= " AND e.department = :department";
        $params[':department'] = $department_filter;
    }
    
    if (!empty($status_filter)) {
        $query .= " AND e.status = :status";
        $params[':status'] = $status_filter;
    }
    
    $query .= " ORDER BY e.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_employees_count($search = '', $department_filter = '', $status_filter = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT COUNT(*) as total FROM employees e WHERE 1=1";
    
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (e.first_name LIKE :search OR e.last_name LIKE :search OR e.email LIKE :search OR e.employee_id LIKE :search)";
        $search_param = '%' . $search . '%';
        $params[':search'] = $search_param;
    }
    
    if (!empty($department_filter)) {
        $query .= " AND e.department = :department";
        $params[':department'] = $department_filter;
    }
    
    if (!empty($status_filter)) {
        $query .= " AND e.status = :status";
        $params[':status'] = $status_filter;
    }
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
}

function get_employee_statistics() {
    $database = new Database();
    $db = $database->getConnection();
    
    $stats = [];
    
    // Total employees
    $query = "SELECT COUNT(*) as total FROM employees";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_employees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Active employees
    $query = "SELECT COUNT(*) as total FROM employees WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['active_employees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Inactive employees
    $query = "SELECT COUNT(*) as total FROM employees WHERE status = 'inactive'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['inactive_employees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Employees by department
    $query = "SELECT department, COUNT(*) as count FROM employees GROUP BY department ORDER BY count DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['by_department'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // New employees this month
    $query = "SELECT COUNT(*) as total FROM employees WHERE MONTH(hire_date) = MONTH(CURDATE()) AND YEAR(hire_date) = YEAR(CURDATE())";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['new_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Average salary
    $query = "SELECT AVG(salary) as avg_salary FROM employees WHERE salary > 0";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $avg_salary = $stmt->fetch(PDO::FETCH_ASSOC)['avg_salary'];
    $stats['average_salary'] = $avg_salary ? (float)$avg_salary : 0;
    
    return $stats;
}

function get_employee_by_id($id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT e.*, u.username, u.role 
              FROM employees e 
              LEFT JOIN users u ON e.user_id = u.id 
              WHERE e.id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function determine_attendance_status($check_in_time, $check_out_time = null) {
    if (!$check_in_time) {
        return 'present'; // Default status
    }
    
    // Check for overtime based on check_out time first
    if ($check_out_time) {
        $check_out_timestamp = strtotime($check_out_time);
        $check_out_hour = (int)date('H', $check_out_timestamp);
        $check_out_minute = (int)date('i', $check_out_timestamp);
        $check_out_time_in_minutes = $check_out_hour * 60 + $check_out_minute;
        
        // If check out is after 5:00 PM (17:00), it's overtime
        if ($check_out_time_in_minutes > 17 * 60) {
            return 'overtime';
        }
    }
    
    $check_in_timestamp = strtotime($check_in_time);
    $hour = (int)date('H', $check_in_timestamp);
    $minute = (int)date('i', $check_in_timestamp);
    $time_in_minutes = $hour * 60 + $minute;
    
    // Define shift times in minutes from midnight
    $morning_start = 8 * 60;      // 8:00 AM = 480 minutes
    $morning_end = 12 * 60;       // 12:00 PM = 720 minutes  
    $afternoon_start = 13 * 60;   // 1:00 PM = 780 minutes
    $afternoon_end = 17 * 60;     // 5:00 PM = 1020 minutes
    
    // Determine status based on check-in time
    if ($time_in_minutes >= $morning_start - 30 && $time_in_minutes < $morning_start) {
        // 7:30 AM - 7:59 AM: Early but acceptable
        return 'present';
    } else if ($time_in_minutes >= $morning_start && $time_in_minutes < $morning_start + 30) {
        // 8:00 AM - 8:30 AM: On time for morning shift
        return 'present';
    } else if ($time_in_minutes >= $morning_start + 30 && $time_in_minutes < $morning_end) {
        // 8:30 AM - 12:00 PM: Late for morning shift
        return 'late';
    } else if ($time_in_minutes >= $morning_end && $time_in_minutes < $morning_end + 30) {
        // 12:00 PM - 12:30 PM: Lunch break (clock out time)
        return 'break';
    } else if ($time_in_minutes >= $afternoon_start - 30 && $time_in_minutes < $afternoon_start) {
        // 12:30 PM - 12:59 PM: Early for afternoon shift
        return 'present';
    } else if ($time_in_minutes >= $afternoon_start && $time_in_minutes < $afternoon_start + 30) {
        // 1:00 PM - 1:30 PM: On time for afternoon shift
        return 'present';
    } else if ($time_in_minutes >= $afternoon_start + 30 && $time_in_minutes <= $afternoon_end) {
        // 1:30 PM - 5:00 PM: Late for afternoon shift
        return 'late';
    } else if ($time_in_minutes > $afternoon_end) {
        // After 5:00 PM: Overtime or late departure (but only if no check_out provided)
        return 'present';
    } else {
        // Before 7:30 AM: Very early
        return 'present';
    }
}

function record_attendance($employee_id, $date, $check_in, $check_out = null, $status = 'present', $notes = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    // Calculate total hours
    $total_hours = 0;
    if ($check_in && $check_out) {
        $check_in_time = strtotime($check_in);
        $check_out_time = strtotime($check_out);
        $total_hours = ($check_out_time - $check_in_time) / 3600;
    }
    
    $query = "INSERT INTO attendance (employee_id, date, check_in, check_out, total_hours, status, notes) 
              VALUES (:employee_id, :date, :check_in, :check_out, :total_hours, :status, :notes)
              ON DUPLICATE KEY UPDATE 
              check_in = VALUES(check_in), 
              check_out = VALUES(check_out), 
              total_hours = VALUES(total_hours), 
              status = VALUES(status), 
              notes = VALUES(notes)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':check_in', $check_in);
    $stmt->bindParam(':check_out', $check_out);
    $stmt->bindParam(':total_hours', $total_hours);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':notes', $notes);
    
    return $stmt->execute();
}

function get_attendance_records_with_shifts($employee_id = null, $date_from = null, $date_to = null, $limit = 10, $offset = 0) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT a.*, e.first_name, e.last_name, e.employee_id, e.position as job_title, 'On-Site' as work_model
              FROM attendance a 
              LEFT JOIN employees e ON a.employee_id = e.id 
              WHERE 1=1";
    
    $params = [];
    
    if ($employee_id) {
        $query .= " AND a.employee_id = :employee_id";
        $params[':employee_id'] = $employee_id;
    }
    
    if ($date_from) {
        $query .= " AND a.date >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    if ($date_to) {
        $query .= " AND a.date <= :date_to";
        $params[':date_to'] = $date_to;
    }
    
    $query .= " ORDER BY a.date DESC, a.check_in ASC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process each record to determine shift information
    foreach ($records as &$record) {
        $record['shift_info'] = determine_shift_info($record['check_in'], $record['check_out']);
        $record['status_display'] = get_status_display($record['status']);
    }
    
    return $records;
}

function determine_shift_info($check_in, $check_out) {
    if (!$check_in) {
        return ['shift' => 'Unknown', 'period' => 'No time recorded'];
    }
    
    $check_in_timestamp = strtotime($check_in);
    $hour = (int)date('H', $check_in_timestamp);
    
    if ($hour >= 6 && $hour < 12) {
        return ['shift' => 'Morning', 'period' => '8:00 AM - 12:00 PM'];
    } else if ($hour >= 12 && $hour < 14) {
        return ['shift' => 'Lunch Break', 'period' => '12:00 PM - 1:00 PM'];
    } else if ($hour >= 13 && $hour < 18) {
        return ['shift' => 'Afternoon', 'period' => '1:00 PM - 5:00 PM'];
    } else {
        return ['shift' => 'Overtime', 'period' => 'After 5:00 PM'];
    }
}

function get_status_display($status) {
    $status_map = [
        'present' => ['text' => 'Present', 'class' => 'success'],
        'late' => ['text' => 'Late', 'class' => 'warning'],
        'absent' => ['text' => 'Absent', 'class' => 'danger'],
        'half_day' => ['text' => 'Half Day', 'class' => 'info'],
        'break' => ['text' => 'Break', 'class' => 'secondary'],
        'overtime' => ['text' => 'Overtime', 'class' => 'primary']
    ];
    
    return $status_map[$status] ?? ['text' => 'Unknown', 'class' => 'secondary'];
}

function get_attendance_records($employee_id = null, $date_from = null, $date_to = null, $limit = 10, $offset = 0) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT a.*, e.first_name, e.last_name, e.employee_id, e.position as job_title, 'On-Site' as work_model
              FROM attendance a 
              LEFT JOIN employees e ON a.employee_id = e.id 
              WHERE 1=1";
    
    $params = [];
    
    if ($employee_id) {
        $query .= " AND a.employee_id = :employee_id";
        $params[':employee_id'] = $employee_id;
    }
    
    if ($date_from) {
        $query .= " AND a.date >= :date_from";
        $params[':date_from'] = $date_from;
    }
    
    if ($date_to) {
        $query .= " AND a.date <= :date_to";
        $params[':date_to'] = $date_to;
    }
    
    $query .= " ORDER BY a.date DESC, e.last_name ASC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_attendance_history($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get employee_id from user_id
    $query = "SELECT id FROM employees WHERE user_id = :user_id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        return []; // No employee found for this user
    }
    
    // Get attendance records for this employee (no limit for history)
    return get_attendance_records($employee['id'], null, null, 1000, 0);
}

// Delivery Functions
function ensure_delivery_statuses($db) {
    $stmt = $db->query("SHOW COLUMNS FROM deliveries LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$column || empty($column['Type'])) {
        return;
    }

    preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $column['Type'], $matches);
    $statuses = array_map(static fn($status) => stripcslashes($status), $matches[1] ?? []);

    foreach (['pending', 'approved', 'in_transit', 'delivered', 'cancelled'] as $required_status) {
        if (!in_array($required_status, $statuses, true)) {
            $statuses[] = $required_status;
        }
    }

    $enum_values = implode(',', array_map([$db, 'quote'], array_unique($statuses)));
    $db->exec("ALTER TABLE deliveries MODIFY COLUMN status ENUM({$enum_values}) DEFAULT 'pending'");
}

function create_delivery($po_id, $supplier_id, $delivery_date, $expected_date, $tracking_number, $carrier, $notes, $created_by) {
    $database = new Database();
    $db = $database->getConnection();

    ensure_delivery_statuses($db);

    $po_query = "SELECT supplier_id, status FROM purchase_orders WHERE id = :po_id";
    $po_stmt = $db->prepare($po_query);
    $po_stmt->bindValue(':po_id', $po_id, PDO::PARAM_INT);
    $po_stmt->execute();
    $purchase_order = $po_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase_order || !in_array($purchase_order['status'], ['Approved', 'Processing'], true)) {
        return false;
    }

    if (empty($supplier_id)) {
        $supplier_id = $purchase_order['supplier_id'];
    }

    if ((int) $supplier_id !== (int) $purchase_order['supplier_id']) {
        return false;
    }

    $delivery_number = generate_delivery_number();

    $query = "INSERT INTO deliveries (delivery_number, po_id, supplier_id, delivery_date, expected_date, tracking_number, carrier, notes, created_by) 
              VALUES (:delivery_number, :po_id, :supplier_id, :delivery_date, :expected_date, :tracking_number, :carrier, :notes, :created_by)";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':delivery_number', $delivery_number);
    $stmt->bindValue(':po_id', $po_id, PDO::PARAM_INT);
    $stmt->bindValue(':supplier_id', $supplier_id, PDO::PARAM_INT);
    $stmt->bindValue(':delivery_date', $delivery_date);
    $stmt->bindValue(':expected_date', $expected_date ?: null);
    $stmt->bindValue(':tracking_number', $tracking_number ?: null);
    $stmt->bindValue(':carrier', $carrier ?: null);
    $stmt->bindValue(':notes', $notes ?: null);
    $stmt->bindValue(':created_by', $created_by, PDO::PARAM_INT);

    return $stmt->execute();
}

function get_deliveries($status = null, $limit = 10, $offset = 0) {
    $database = new Database();
    $db = $database->getConnection();

    ensure_delivery_statuses($db);

    $query = "SELECT d.*, po.po_number, s.name as supplier_name, s.email as supplier_email, u.full_name as created_by_name 
              FROM deliveries d 
              LEFT JOIN purchase_orders po ON d.po_id = po.id 
              LEFT JOIN suppliers s ON d.supplier_id = s.id 
              LEFT JOIN users u ON d.created_by = u.id 
              WHERE 1=1";
    
    $params = [];
    
    if ($status) {
        $query .= " AND d.status = :status";
        $params[':status'] = $status;
    }
    
    $query .= " ORDER BY d.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function set_delivery_status($delivery_id, $status) {
    $allowed_statuses = ['pending', 'approved', 'in_transit', 'delivered', 'cancelled'];
    if (!in_array($status, $allowed_statuses, true)) {
        return false;
    }

    $database = new Database();
    $db = $database->getConnection();

    ensure_delivery_statuses($db);

    $query = "UPDATE deliveries SET status = :status WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':id', $delivery_id, PDO::PARAM_INT);

    return $stmt->execute();
}

function get_delivery_by_id($delivery_id) {
    $database = new Database();
    $db = $database->getConnection();

    ensure_delivery_statuses($db);

    $query = "SELECT d.*, po.po_number, po.order_date, po.expected_delivery_date as po_expected_delivery_date,
                     s.name as supplier_name, s.contact_person, s.email as supplier_email, s.phone as supplier_phone,
                     s.address as supplier_address, u.full_name as created_by_name
              FROM deliveries d
              LEFT JOIN purchase_orders po ON d.po_id = po.id
              LEFT JOIN suppliers s ON d.supplier_id = s.id
              LEFT JOIN users u ON d.created_by = u.id
              WHERE d.id = :id
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $delivery_id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function update_delivery_details($delivery_id, $delivery_date, $expected_date, $status, $tracking_number, $carrier, $notes) {
    $allowed_statuses = ['pending', 'approved', 'in_transit', 'delivered', 'cancelled'];
    if (!in_array($status, $allowed_statuses, true)) {
        return false;
    }

    $database = new Database();
    $db = $database->getConnection();

    ensure_delivery_statuses($db);

    $query = "UPDATE deliveries
              SET delivery_date = :delivery_date,
                  expected_date = :expected_date,
                  status = :status,
                  tracking_number = :tracking_number,
                  carrier = :carrier,
                  notes = :notes
              WHERE id = :id";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':delivery_date', $delivery_date);
    $stmt->bindValue(':expected_date', $expected_date ?: null);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':tracking_number', $tracking_number ?: null);
    $stmt->bindValue(':carrier', $carrier ?: null);
    $stmt->bindValue(':notes', $notes ?: null);
    $stmt->bindValue(':id', $delivery_id, PDO::PARAM_INT);

    return $stmt->execute();
}

// Dashboard Statistics
function get_dashboard_stats() {
    $database = new Database();
    $db = $database->getConnection();
    
    $stats = [];
    
    // Total Purchase Orders
    $query = "SELECT COUNT(*) as total FROM purchase_orders";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_purchase_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Pending Deliveries
    $query = "SELECT COUNT(*) as total FROM deliveries WHERE status IN ('pending', 'in_transit')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending_deliveries'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total Employees
    $query = "SELECT COUNT(*) as total FROM employees WHERE status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_employees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Today's Attendance
    $query = "SELECT COUNT(*) as total FROM attendance WHERE date = CURDATE() AND status = 'present'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['today_attendance'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return $stats;
}

// Get suppliers for dropdown
function get_suppliers() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, name, supplier_code FROM suppliers WHERE status = 'active' ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Update PO status
function update_po_status($po_id, $status, $admin_notes = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE purchase_orders SET status = :status, notes = CONCAT(IFNULL(notes, ''), '\n\nAdmin Update: ', :admin_notes) 
              WHERE id = :po_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':po_id', $po_id);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':admin_notes', $admin_notes);
    
    return $stmt->execute();
}

// Get PO details with items
function get_po_details($po_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get PO details
    $query = "SELECT po.*, u.full_name as created_by_name, u.role as created_by_role, s.name as supplier_name 
              FROM purchase_orders po 
              LEFT JOIN users u ON po.created_by = u.id 
              LEFT JOIN suppliers s ON po.supplier_id = s.id 
              WHERE po.id = :po_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':po_id', $po_id);
    $stmt->execute();
    $po_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($po_details) {
        // Get PO items
        $items_query = "SELECT * FROM purchase_order_items WHERE po_id = :po_id";
        $items_stmt = $db->prepare($items_query);
        $items_stmt->bindParam(':po_id', $po_id);
        $items_stmt->execute();
        $po_details['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $po_details;
}

// Add message to PO
function add_po_message($po_id, $user_id, $message, $message_type) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO purchase_order_messages (po_id, user_id, message, message_type) 
              VALUES (:po_id, :user_id, :message, :message_type)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':po_id', $po_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':message_type', $message_type);
    
    return $stmt->execute();
}

// Get PO messages
function get_po_messages($po_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT pom.*, u.full_name, u.role 
              FROM purchase_order_messages pom 
              LEFT JOIN users u ON pom.user_id = u.id 
              WHERE pom.po_id = :po_id 
              ORDER BY pom.created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':po_id', $po_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get POs for admin (all POs)
function get_purchase_orders_admin($limit = 10, $offset = 0, $status_filter = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT po.*, u.full_name as created_by_name, u.role as created_by_role, s.name as supplier_name 
              FROM purchase_orders po 
              LEFT JOIN users u ON po.created_by = u.id 
              LEFT JOIN suppliers s ON po.supplier_id = s.id 
              WHERE 1=1";
    
    $params = [];
    
    if ($status_filter) {
        $query .= " AND po.status = :status";
        $params[':status'] = $status_filter;
    }
    
    $query .= " ORDER BY po.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get POs for store users (only their own POs)
function get_purchase_orders_store($user_id, $limit = 10, $offset = 0) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT po.*, s.name as supplier_name 
              FROM purchase_orders po 
              LEFT JOIN suppliers s ON po.supplier_id = s.id 
              WHERE po.created_by = :user_id 
              ORDER BY po.created_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Camera Attendance Functions
function record_camera_attendance($employee_id, $capture_date, $capture_time, $photo_path, 
                                $latitude = null, $longitude = null, $location_address = '', 
                                $azimuth = '', $temperature = null, $device_info = '', $notes = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO camera_attendance (employee_id, capture_date, capture_time, photo_path, 
              latitude, longitude, location_address, azimuth, temperature, device_info, notes) 
              VALUES (:employee_id, :capture_date, :capture_time, :photo_path, 
              :latitude, :longitude, :location_address, :azimuth, :temperature, :device_info, :notes)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->bindParam(':capture_date', $capture_date);
    $stmt->bindParam(':capture_time', $capture_time);
    $stmt->bindParam(':photo_path', $photo_path);
    $stmt->bindParam(':latitude', $latitude);
    $stmt->bindParam(':longitude', $longitude);
    $stmt->bindParam(':location_address', $location_address);
    $stmt->bindParam(':azimuth', $azimuth);
    $stmt->bindParam(':temperature', $temperature);
    $stmt->bindParam(':device_info', $device_info);
    $stmt->bindParam(':notes', $notes);
    
    return $stmt->execute();
}

function get_camera_attendance_by_employee($employee_id, $date = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT ca.*, e.first_name, e.last_name, e.employee_id as emp_id 
              FROM camera_attendance ca 
              LEFT JOIN employees e ON ca.employee_id = e.id 
              WHERE ca.employee_id = :employee_id";
    
    if ($date) {
        $query .= " AND ca.capture_date = :date";
    }
    
    $query .= " ORDER BY ca.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':employee_id', $employee_id);
    
    if ($date) {
        $stmt->bindParam(':date', $date);
    }
    
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_employee_by_user_id($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM employees WHERE user_id = :user_id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_camera_attendance_records($limit = 50, $offset = 0, $employee_filter = null, $date_filter = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT ca.*, e.first_name, e.last_name, e.employee_id as emp_id, e.position 
              FROM camera_attendance ca 
              LEFT JOIN employees e ON ca.employee_id = e.id 
              WHERE 1=1";
    
    $params = [];
    
    if ($employee_filter) {
        $query .= " AND ca.employee_id = :employee_id";
        $params[':employee_id'] = $employee_filter;
    }
    
    if ($date_filter) {
        $query .= " AND ca.capture_date = :date";
        $params[':date'] = $date_filter;
    }
    
    $query .= " ORDER BY ca.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindParam($key, $value);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function create_sample_attendance_data() {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get all employees
    $query = "SELECT * FROM employees";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($employees)) {
        return false;
    }
    
    // Clear existing attendance data
    $delete_query = "DELETE FROM attendance";
    $db->exec($delete_query);
    
    // Add sample attendance for the last 7 days
    $today = date('Y-m-d');
    
    foreach ($employees as $employee) {
        for ($day = 0; $day < 7; $day++) {
            $date = date('Y-m-d', strtotime("-$day days", strtotime($today)));
            
            // Skip weekends
            if (date('N', strtotime($date)) >= 6) continue;
            
            // Random check-in times between 7:00 AM and 9:30 AM
            $check_in_hour = rand(7, 9);
            $check_in_minute = $check_in_hour == 7 ? rand(0, 59) : rand(0, 30);
            $check_in = sprintf("%02d:%02d:00", $check_in_hour, $check_in_minute);
            
            // Random check-out times between 4:00 PM and 7:00 PM
            $check_out_hour = rand(16, 19);
            $check_out_minute = rand(0, 59);
            $check_out = sprintf("%02d:%02d:00", $check_out_hour, $check_out_minute);
            
            // Calculate total hours
            $check_in_time = strtotime($check_in);
            $check_out_time = strtotime($check_out);
            $total_hours = round(($check_out_time - $check_in_time) / 3600, 2);
            
            // Random status (mostly present, some late)
            $statuses = ['present', 'present', 'present', 'present', 'late'];
            $status = $statuses[array_rand($statuses)];
            
            // Adjust check-in time for late employees
            if ($status === 'late') {
                $check_in_hour = rand(9, 10);
                $check_in_minute = rand(1, 59);
                $check_in = sprintf("%02d:%02d:00", $check_in_hour, $check_in_minute);
                $check_in_time = strtotime($check_in);
                $total_hours = round(($check_out_time - $check_in_time) / 3600, 2);
            }
            
            $notes = $status === 'late' ? 'Late arrival' : 'Regular attendance';
            
            // Insert attendance record
            record_attendance($employee['id'], $date, $check_in, $check_out, $status, $notes);
        }
    }
    
    return true;
}

// Attendance Notification Functions
function create_attendance_notification($employee_id, $camera_attendance_id, $message, $type = 'new_attendance', $priority = 'medium') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO attendance_notifications (employee_id, camera_attendance_id, notification_type, message, priority) 
              VALUES (:employee_id, :camera_attendance_id, :notification_type, :message, :priority)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->bindParam(':camera_attendance_id', $camera_attendance_id);
    $stmt->bindParam(':notification_type', $type);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':priority', $priority);
    
    return $stmt->execute();
}

function get_unread_attendance_notifications($limit = 10) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT an.*, e.first_name, e.last_name, e.employee_id as emp_id, ca.photo_path, ca.capture_time
              FROM attendance_notifications an
              LEFT JOIN employees e ON an.employee_id = e.id
              LEFT JOIN camera_attendance ca ON an.camera_attendance_id = ca.id
              WHERE an.is_read = FALSE
              ORDER BY an.created_at DESC
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function mark_notification_read($notification_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "UPDATE attendance_notifications SET is_read = TRUE WHERE id = :notification_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':notification_id', $notification_id);
    
    return $stmt->execute();
}

function get_pending_camera_attendance($limit = 20) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT ca.*, e.first_name, e.last_name, e.employee_id as emp_id, e.position
              FROM camera_attendance ca
              LEFT JOIN employees e ON ca.employee_id = e.id
              WHERE ca.verification_status = 'pending' 
              AND ca.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
              ORDER BY ca.created_at DESC
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// General Messaging Functions
function send_message($sender_id, $recipient_id, $message, $subject = '') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO messages (sender_id, recipient_id, message, subject) 
                  VALUES (:sender_id, :recipient_id, :message, :subject)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':sender_id', $sender_id);
        $stmt->bindParam(':recipient_id', $recipient_id);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':subject', $subject);
        
        return $stmt->execute();
    } catch(PDOException $e) {
        // Table doesn't exist yet
        if (strpos($e->getMessage(), "Base table or view not found") !== false) {
            return false;
        }
        throw $e;
    }
}

function get_messages($user_id, $limit = 50, $offset = 0) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT m.*, 
                  sender.full_name as sender_name, sender.role as sender_role,
                  recipient.full_name as recipient_name, recipient.role as recipient_role
                  FROM messages m
                  LEFT JOIN users sender ON m.sender_id = sender.id
                  LEFT JOIN users recipient ON m.recipient_id = recipient.id
                  WHERE (m.sender_id = :user_id OR m.recipient_id = :user_id)
                  ORDER BY m.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Table doesn't exist yet
        if (strpos($e->getMessage(), "Base table or view not found") !== false) {
            return [];
        }
        throw $e;
    }
}

function get_unread_message_count($user_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT COUNT(*) as unread_count 
                  FROM messages 
                  WHERE recipient_id = :user_id AND is_read = 0";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['unread_count'];
    } catch(PDOException $e) {
        // Table doesn't exist yet, return 0
        if (strpos($e->getMessage(), "Base table or view not found") !== false) {
            return 0;
        }
        throw $e;
    }
}

function mark_message_as_read($message_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "UPDATE messages SET is_read = 1 WHERE id = :message_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':message_id', $message_id);
        
        return $stmt->execute();
    } catch(PDOException $e) {
        // Table doesn't exist yet
        if (strpos($e->getMessage(), "Base table or view not found") !== false) {
            return false;
        }
        throw $e;
    }
}

function get_chat_messages($user_id1, $user_id2) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT m.*, 
                  sender.full_name as sender_name, sender.role as sender_role,
                  recipient.full_name as recipient_name, recipient.role as recipient_role
                  FROM messages m
                  LEFT JOIN users sender ON m.sender_id = sender.id
                  LEFT JOIN users recipient ON m.recipient_id = recipient.id
                  WHERE ((m.sender_id = :user_id1 AND m.recipient_id = :user_id2) OR 
                         (m.sender_id = :user_id2 AND m.recipient_id = :user_id1))
                  ORDER BY m.created_at ASC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id1', $user_id1);
        $stmt->bindParam(':user_id2', $user_id2);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add message type based on content (for demo purposes)
        foreach ($messages as &$msg) {
            $msg['message_type'] = 'text'; // Default to text
            
            // Check if message contains document indicators
            if (strpos($msg['message'], '.docx') !== false || strpos($msg['message'], '.pdf') !== false) {
                $msg['message_type'] = 'document';
            }
            // Check for voice message indicators
            elseif (strpos($msg['message'], '[voice:') !== false) {
                $msg['message_type'] = 'voice';
            }
            // Check for video message indicators
            elseif (strpos($msg['message'], '[video:') !== false) {
                $msg['message_type'] = 'video';
            }
        }
        
        return $messages;
    } catch(PDOException $e) {
        // Table doesn't exist yet
        if (strpos($e->getMessage(), "Base table or view not found") !== false) {
            return [];
        }
        throw $e;
    }
}

function get_admin_users() {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT id, full_name, role FROM users WHERE role IN ('admin', 'manager') ORDER BY full_name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        // Table doesn't exist yet
        if (strpos($e->getMessage(), "Base table or view not found") !== false) {
            return [];
        }
        throw $e;
    }
}

function delete_purchase_order($po_id) {
    return archive_purchase_order($po_id);
}

function archive_purchase_order($po_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        ensure_purchase_order_archived_status($db);

        $db->beginTransaction();

        // Keep purchase orders for reports and audit history instead of deleting them.
        $query = "UPDATE purchase_orders SET status = 'Archived' WHERE id = :po_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':po_id', $po_id);
        $stmt->execute();
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function ensure_purchase_order_archived_status($db) {
    $stmt = $db->query("SHOW COLUMNS FROM purchase_orders LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$column || stripos($column['Type'], 'enum(') !== 0 || strpos($column['Type'], "'Archived'") !== false) {
        return;
    }

    preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $column['Type'], $matches);
    $statuses = array_map(fn($status) => stripcslashes($status), $matches[1]);
    $statuses[] = 'Archived';
    $enum_values = implode(',', array_map(fn($status) => $db->quote($status), array_unique($statuses)));
    $db->exec("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM({$enum_values}) DEFAULT 'Pending'");
}

function ensure_purchase_invoice_approval_statuses($db) {
    $stmt = $db->query("SHOW COLUMNS FROM purchase_invoices LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$column || stripos($column['Type'], 'enum(') !== 0) {
        return;
    }

    preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $column['Type'], $matches);
    $statuses = array_map(fn($status) => stripcslashes($status), $matches[1]);

    foreach (['pending', 'approved', 'rejected'] as $required_status) {
        if (!in_array($required_status, $statuses, true)) {
            $statuses[] = $required_status;
        }
    }

    $enum_values = implode(',', array_map(fn($status) => $db->quote($status), array_unique($statuses)));
    $db->exec("ALTER TABLE purchase_invoices MODIFY COLUMN status ENUM({$enum_values}) DEFAULT 'pending'");
}

function get_purchase_invoices_admin($limit = 100, $offset = 0, $status_filter = null) {
    $database = new Database();
    $db = $database->getConnection();
    ensure_purchase_invoice_approval_statuses($db);

    $query = "SELECT pi.*, po.po_number, po.order_date, po.created_by, s.name as supplier_name, u.full_name as created_by_name
              FROM purchase_invoices pi
              LEFT JOIN purchase_orders po ON pi.po_id = po.id
              LEFT JOIN suppliers s ON po.supplier_id = s.id
              LEFT JOIN users u ON po.created_by = u.id
              WHERE 1=1";
    $params = [];

    if ($status_filter) {
        $query .= " AND pi.status = :status";
        $params[':status'] = $status_filter;
    }

    $query .= " ORDER BY pi.created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function update_purchase_invoice_status($invoice_id, $status, $admin_notes = '') {
    $allowed_statuses = ['pending', 'approved', 'rejected', 'paid', 'unpaid', 'partially_paid'];
    if (!in_array($status, $allowed_statuses, true)) {
        return false;
    }

    $database = new Database();
    $db = $database->getConnection();
    ensure_purchase_invoice_approval_statuses($db);

    $query = "UPDATE purchase_invoices
              SET status = :status
              WHERE id = :invoice_id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':invoice_id', $invoice_id, PDO::PARAM_INT);

    return $stmt->execute();
}

function update_purchase_invoice_details($invoice_id, $invoice_number, $invoice_date, $due_date, $status) {
    $allowed_statuses = ['pending', 'approved', 'rejected', 'paid', 'unpaid', 'partially_paid'];
    if (!in_array($status, $allowed_statuses, true)) {
        return false;
    }

    $database = new Database();
    $db = $database->getConnection();
    ensure_purchase_invoice_approval_statuses($db);

    $query = "UPDATE purchase_invoices
              SET invoice_number = :invoice_number,
                  invoice_date = :invoice_date,
                  due_date = :due_date,
                  status = :status
              WHERE id = :invoice_id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':invoice_number', $invoice_number);
    $stmt->bindValue(':invoice_date', $invoice_date);
    $stmt->bindValue(':due_date', $due_date);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':invoice_id', $invoice_id, PDO::PARAM_INT);

    return $stmt->execute();
}

function approve_all_pending_purchase_invoices() {
    $database = new Database();
    $db = $database->getConnection();
    ensure_purchase_invoice_approval_statuses($db);

    $query = "UPDATE purchase_invoices
              SET status = 'approved'
              WHERE status IN ('pending', 'unpaid', 'partially_paid')";
    return $db->exec($query);
}

function approve_camera_attendance($camera_attendance_id, $admin_notes = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get camera attendance details
    $query = "SELECT * FROM camera_attendance WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $camera_attendance_id);
    $stmt->execute();
    $camera_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($camera_attendance) {
        // Update camera attendance verification status
        $query = "UPDATE camera_attendance SET verification_status = 'verified' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $camera_attendance_id);
        $stmt->execute();
        
        // Create or update regular attendance record
        $attendance_notes = "Camera attendance approved - Location: {$camera_attendance['location_address']}, Photo: {$camera_attendance['photo_path']}";
        if ($admin_notes) {
            $attendance_notes .= " | Admin notes: {$admin_notes}";
        }
        
        $success = record_attendance(
            $camera_attendance['employee_id'], 
            $camera_attendance['capture_date'], 
            $camera_attendance['capture_time'], 
            null, 
            'present', 
            $attendance_notes
        );
        
        // Mark related notifications as read
        $query = "UPDATE attendance_notifications SET is_read = TRUE WHERE camera_attendance_id = :camera_attendance_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':camera_attendance_id', $camera_attendance_id);
        $stmt->execute();
        
        return $success;
    }
    
    return false;
}

function reject_camera_attendance($camera_attendance_id, $admin_notes = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get camera attendance details
    $query = "SELECT * FROM camera_attendance WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $camera_attendance_id);
    $stmt->execute();
    $camera_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($camera_attendance) {
        // Update camera attendance verification status
        $query = "UPDATE camera_attendance SET verification_status = 'rejected' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $camera_attendance_id);
        $stmt->execute();
        
        // Create or update regular attendance record as rejected
        $attendance_notes = "Camera attendance rejected - Location: {$camera_attendance['location_address']}, Photo: {$camera_attendance['photo_path']}";
        if ($admin_notes) {
            $attendance_notes .= " | Admin notes: {$admin_notes}";
        }
        
        $success = record_attendance(
            $camera_attendance['employee_id'], 
            $camera_attendance['capture_date'], 
            $camera_attendance['capture_time'], 
            null, 
            'absent', 
            $attendance_notes
        );
        
        // Mark related notifications as read
        $query = "UPDATE attendance_notifications SET is_read = TRUE WHERE camera_attendance_id = :camera_attendance_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':camera_attendance_id', $camera_attendance_id);
        $stmt->execute();
        
        return $success;
    }
    
    return false;
}

function get_employee_attendance_history($employee_id, $date_from, $date_to, $limit = 50, $offset = 0) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get regular attendance records
    $query = "SELECT a.id, a.employee_id, a.date, a.check_in, a.check_out, a.break_duration, 
                     a.total_hours, a.status, a.notes, a.created_at, 
                     e.first_name, e.last_name, e.employee_id as emp_id, 
                     'regular' as attendance_type, null as photo_path, null as verification_status,
                     COALESCE(a.check_out, a.check_in) as sort_time
              FROM attendance a 
              LEFT JOIN employees e ON a.employee_id = e.id 
              WHERE a.employee_id = :employee_id 
              AND a.date BETWEEN :date_from AND :date_to
              
              UNION ALL
              
              SELECT ca.id, ca.employee_id, ca.capture_date as date,
                     CASE WHEN LOWER(ca.notes) LIKE '%clock_out%' OR LOWER(ca.notes) LIKE '%clock out%' THEN null ELSE ca.capture_time END as check_in,
                     CASE WHEN LOWER(ca.notes) LIKE '%clock_out%' OR LOWER(ca.notes) LIKE '%clock out%' THEN ca.capture_time ELSE null END as check_out,
                     null as break_duration, null as total_hours, 'present' as status, 
                     ca.notes, ca.created_at, e.first_name, e.last_name, e.employee_id as emp_id,
                     'camera' as attendance_type, ca.photo_path, ca.verification_status,
                     ca.capture_time as sort_time
              FROM camera_attendance ca 
              LEFT JOIN employees e ON ca.employee_id = e.id 
              WHERE ca.employee_id = :employee_id 
              AND ca.capture_date BETWEEN :date_from AND :date_to
              
              ORDER BY date DESC, sort_time DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->bindParam(':date_from', $date_from);
    $stmt->bindParam(':date_to', $date_to);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add watermark to attendance photo
function add_watermark_to_photo($image_path, $attendance_data = []) {
    // Check if GD library is available
    if (!extension_loaded('gd')) {
        error_log('GD library not available for watermarking');
        return $image_path;
    }
    
    // Get image info
    $image_info = getimagesize($image_path);
    if (!$image_info) {
        error_log('Unable to get image info for: ' . $image_path);
        return $image_path;
    }
    
    $mime_type = $image_info['mime'];
    $width = $image_info[0];
    $height = $image_info[1];
    
    // Create image resource based on mime type
    switch ($mime_type) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($image_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($image_path);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($image_path);
            break;
        default:
            error_log('Unsupported image type: ' . $mime_type);
            return $image_path;
    }
    
    if (!$image) {
        error_log('Failed to create image resource from: ' . $image_path);
        return $image_path;
    }
    
    // Set up colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $blue = imagecolorallocate($image, 21, 101, 192);
    
    // Add semi-transparent overlay background for better text visibility
    $overlay_color = imagecolorallocatealpha($image, 0, 0, 0, 70);
    imagefilledrectangle($image, 0, $height - 120, $width, $height, $overlay_color);
    
    // Set up font paths - use built-in font if TTF not available
    $font_size = 12;
    $line_height = 18;
    $margin = 15;
    $start_y = $height - 105;
    
    // Prepare watermark text
    $current_time = isset($attendance_data['capture_time']) ? $attendance_data['capture_time'] : date('H:i:s');
    $current_date = isset($attendance_data['capture_date']) ? $attendance_data['capture_date'] : date('Y-m-d');
    $location = isset($attendance_data['location_address']) ? $attendance_data['location_address'] : 'Unknown Location';
    $azimuth = isset($attendance_data['azimuth']) ? $attendance_data['azimuth'] : 'N 0°';
    $coords = '';
    
    if (isset($attendance_data['latitude']) && isset($attendance_data['longitude'])) {
        $coords = $attendance_data['latitude'] . '°N, ' . $attendance_data['longitude'] . '°E';
    }
    
    // Format date for display
    $formatted_date = date('M d, Y', strtotime($current_date));
    $weekday = date('D', strtotime($current_date));
    
    // Watermark text lines
    $watermark_lines = [
        'McPILLAB APP - Camera Attendance',
        'Time: ' . date('h:i A', strtotime($current_time)),
        'Date: ' . $formatted_date . ' ' . $weekday,
        'Location: ' . substr($location, 0, 40) . (strlen($location) > 40 ? '...' : ''),
        'Azimuth: ' . $azimuth
    ];
    
    if (!empty($coords)) {
        $watermark_lines[] = 'Coordinate: ' . $coords;
    }
    
    $watermark_lines[] = 'Time & location verified by McPILLAB APP';
    
    // Try to use TTF font if available
    $font_file = __DIR__ . '/fonts/arial.ttf';
    $use_ttf = file_exists($font_file);
    
    // Draw watermark text
    foreach ($watermark_lines as $index => $line) {
        $y_position = $start_y + ($index * $line_height);
        
        if ($use_ttf) {
            // Use TTF font for better quality
            imagettftext($image, $font_size, 0, $margin, $y_position, $white, $font_file, $line);
        } else {
            // Use built-in font as fallback
            imagestring($image, 2, $margin, $y_position - 8, $line, $white);
        }
    }
    
    // Add McPILLAB logo/text at the top
    $logo_text = 'McPILLAB';
    $logo_box_width = 150;
    $logo_box_height = 30;
    $logo_x = $width - $logo_box_width - 10;
    $logo_y = 10;
    
    // Draw semi-transparent background for logo
    $logo_bg = imagecolorallocatealpha($image, 21, 101, 192, 80);
    imagefilledrectangle($image, $logo_x, $logo_y, $logo_x + $logo_box_width, $logo_y + $logo_box_height, $logo_bg);
    
    // Draw logo text
    if ($use_ttf) {
        imagettftext($image, 14, 0, $logo_x + 10, $logo_y + 20, $white, $font_file, $logo_text);
    } else {
        imagestring($image, 4, $logo_x + 10, $logo_y + 8, $logo_text, $white);
    }
    
    // Save the watermarked image
    $temp_path = $image_path;
    
    // Create backup of original
    $backup_path = str_replace('.jpg', '_original.jpg', $image_path);
    if (!file_exists($backup_path)) {
        copy($image_path, $backup_path);
    }
    
    // Save watermarked image
    switch ($mime_type) {
        case 'image/jpeg':
            imagejpeg($image, $temp_path, 90);
            break;
        case 'image/png':
            imagepng($image, $temp_path, 9);
            break;
        case 'image/gif':
            imagegif($image, $temp_path);
            break;
    }
    
    // Free memory
    imagedestroy($image);
    
    return $temp_path;
}

// Inventory Management Functions

// Function to add inventory item
function add_inventory_item($item_name, $item_code, $category, $description, $unit, $quantity, $unit_price, $supplier_name, $location, $min_stock_level) {
    error_log("add_inventory_item called with: item_name=$item_name, item_code=$item_code, category=$category, unit=$unit, quantity=$quantity, unit_price=$unit_price, supplier_name=$supplier_name, location=$location, min_stock_level=$min_stock_level");
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        error_log("Database connection failed in add_inventory_item");
        return false;
    }
    
    try {
        $db->beginTransaction();
        error_log("Transaction started");
        
        // Check if item_code already exists
        $check_query = "SELECT COUNT(*) as count FROM inventory_items WHERE barcode = :item_code";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':item_code', $item_code);
        $check_stmt->execute();
        $exists = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($exists['count'] > 0) {
            error_log("Item code already exists: $item_code");
            $db->rollBack();
            return false;
        }
        
        // Handle supplier - find existing or create new
        $supplier_id = null;
        if (!empty($supplier_name)) {
            // Check if supplier already exists
            $supplier_check = "SELECT id FROM suppliers WHERE name = :supplier_name";
            $supplier_stmt = $db->prepare($supplier_check);
            $supplier_stmt->bindParam(':supplier_name', $supplier_name);
            $supplier_stmt->execute();
            $supplier_result = $supplier_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($supplier_result) {
                $supplier_id = $supplier_result['id'];
                error_log("Found existing supplier: $supplier_name with ID: $supplier_id");
            } else {
                // Create new supplier
                $supplier_code = 'SUP' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $supplier_name), 0, 5)) . time();
                $create_supplier = "INSERT INTO suppliers (supplier_code, name, status) VALUES (:supplier_code, :supplier_name, 'active')";
                $create_stmt = $db->prepare($create_supplier);
                $create_stmt->bindParam(':supplier_code', $supplier_code);
                $create_stmt->bindParam(':supplier_name', $supplier_name);
                $create_stmt->execute();
                $supplier_id = $db->lastInsertId();
                error_log("Created new supplier: $supplier_name with ID: $supplier_id");
            }
        }
        
        // Insert inventory item
        $query = "INSERT INTO inventory_items (item_name, barcode, category, unit, unit_price, supplier_id, location, min_stock_level) 
                  VALUES (:item_name, :item_code, :category, :unit, :unit_price, :supplier_id, :location, :min_stock_level)";
        
        error_log("Preparing query: $query");
        $stmt = $db->prepare($query);
        $stmt->bindParam(':item_name', $item_name);
        $stmt->bindParam(':item_code', $item_code);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':unit', $unit);
        $stmt->bindParam(':unit_price', $unit_price);
        $stmt->bindParam(':supplier_id', $supplier_id);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':min_stock_level', $min_stock_level);
        
        error_log("Executing inventory item insert");
        $stmt->execute();
        $item_id = $db->lastInsertId();
        error_log("Inventory item inserted with ID: $item_id");
        
        // Initialize stock
        $stock_query = "INSERT INTO inventory_stock (item_id, beginning_stock, bodega_stock, shelves_stock, delivery_stock, total_stock, total_amount, suggested_order) 
                        VALUES (:item_id, :quantity, 0, 0, 0, :quantity, :total_amount, 0)";
        
        $total_amount = $quantity * $unit_price;
        error_log("Preparing stock insert: total_amount=$total_amount");
        $stock_stmt = $db->prepare($stock_query);
        $stock_stmt->bindParam(':item_id', $item_id);
        $stock_stmt->bindParam(':quantity', $quantity);
        $stock_stmt->bindParam(':total_amount', $total_amount);
        
        error_log("Executing stock insert");
        $stock_stmt->execute();
        error_log("Stock record created");
        
        // Create beginning transaction when the optional transaction log table exists.
        // Older installations only have inventory_items and inventory_stock.
        if (table_exists($db, 'inventory_transactions')) {
            $trans_query = "INSERT INTO inventory_transactions (item_id, transaction_type, quantity, unit_price, transaction_date, created_by) 
                            VALUES (:item_id, 'beginning', :quantity, :unit_price, CURDATE(), :user_id)";
            
            error_log("Preparing transaction insert");
            $trans_stmt = $db->prepare($trans_query);
            $trans_stmt->bindParam(':item_id', $item_id);
            $trans_stmt->bindParam(':quantity', $quantity);
            $trans_stmt->bindParam(':unit_price', $unit_price);
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $trans_stmt->bindParam(':user_id', $user_id);
            
            error_log("Executing transaction insert with user_id: $user_id");
            $trans_stmt->execute();
            error_log("Transaction record created");
        } else {
            error_log("inventory_transactions table not found; item saved without transaction log");
        }
        
        $db->commit();
        error_log("Transaction committed successfully");
        return true;
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log("Error adding inventory item: " . $e->getMessage());
        error_log("Error details: " . $e->getCode() . " - " . $e->getMessage());
        return false;
    }
}

function table_exists($db, $table_name) {
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE :table_name");
        $stmt->bindParam(':table_name', $table_name);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking table existence for $table_name: " . $e->getMessage());
        return false;
    }
}

function column_exists($db, $table_name, $column_name) {
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM `$table_name` LIKE :column_name");
        $stmt->bindParam(':column_name', $column_name);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error checking column existence for $table_name.$column_name: " . $e->getMessage());
        return false;
    }
}

// Function to get inventory items for reporting
function get_inventory_items($limit = 100, $offset = 0) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT ii.*, is_.bodega_stock, is_.shelves_stock, is_.delivery_stock, is_.total_stock, is_.total_amount, is_.suggested_order,
                     s.name as supplier_name
              FROM inventory_items ii
              LEFT JOIN inventory_stock is_ ON ii.id = is_.item_id
              LEFT JOIN suppliers s ON ii.supplier_id = s.id
              ORDER BY ii.item_name
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to generate inventory report
function generate_inventory_report($date_from = null, $date_to = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if inventory tables exist
    $check_table = $db->query("SHOW TABLES LIKE 'inventory_items'");
    if ($check_table->rowCount() == 0) {
        // Return empty result if tables don't exist
        return [];
    }
    
    $query = "SELECT ii.item_name, ii.barcode, ii.size, ii.unit, ii.unit_price,
                     is_.bodega_stock, is_.shelves_stock, is_.delivery_stock, is_.total_stock, 
                     is_.total_amount, is_.suggested_order,
                     s.name as supplier_name
              FROM inventory_items ii
              LEFT JOIN inventory_stock is_ ON ii.id = is_.item_id
              LEFT JOIN suppliers s ON ii.supplier_id = s.id
              ORDER BY ii.item_name";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get inventory summary statistics
function get_inventory_summary() {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if inventory tables exist
    $check_table = $db->query("SHOW TABLES LIKE 'inventory_items'");
    if ($check_table->rowCount() == 0) {
        // Return default values if tables don't exist
        return [
            'total_items' => 0,
            'total_quantity' => 0,
            'total_value' => 0,
            'items_to_order' => 0,
            'order_value' => 0
        ];
    }
    
    $query = "SELECT 
                COUNT(*) as total_items,
                SUM(total_stock) as total_quantity,
                SUM(total_amount) as total_value,
                COUNT(CASE WHEN suggested_order > 0 THEN 1 END) as items_to_order,
                SUM(suggested_order * unit_price) as order_value
              FROM inventory_stock is_
              JOIN inventory_items ii ON is_.item_id = ii.id";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to update stock levels
function update_inventory_stock($item_id, $bodega_qty, $shelves_qty, $delivery_qty, $transaction_type = 'adjustment', $notes = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Get current item info
        $item_query = "SELECT * FROM inventory_items WHERE id = :item_id";
        $item_stmt = $db->prepare($item_query);
        $item_stmt->bindParam(':item_id', $item_id);
        $item_stmt->execute();
        $item = $item_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            $db->rollBack();
            return false;
        }
        
        // Update stock summary
        $update_query = "UPDATE inventory_stock 
                        SET bodega_stock = :bodega_qty, 
                            shelves_stock = :shelves_qty, 
                            delivery_stock = :delivery_qty,
                            total_stock = :bodega_qty + :shelves_qty + :delivery_qty,
                            total_amount = (:bodega_qty + :shelves_qty + :delivery_qty) * :unit_price,
                            last_updated = CURRENT_TIMESTAMP
                        WHERE item_id = :item_id";
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':item_id', $item_id);
        $update_stmt->bindParam(':bodega_qty', $bodega_qty);
        $update_stmt->bindParam(':shelves_qty', $shelves_qty);
        $update_stmt->bindParam(':delivery_qty', $delivery_qty);
        $update_stmt->bindParam(':unit_price', $item['unit_price']);
        $update_stmt->execute();
        
        if (table_exists($db, 'inventory_transactions')) {
            $trans_query = "INSERT INTO inventory_transactions 
                            (item_id, transaction_type, bodega_quantity, shelves_quantity, delivery_quantity, 
                             unit_price, notes, transaction_date, created_by)
                            VALUES (:item_id, :transaction_type, :bodega_qty, :shelves_qty, :delivery_qty,
                                    :unit_price, :notes, CURDATE(), :user_id)";
            
            $trans_stmt = $db->prepare($trans_query);
            $trans_stmt->bindParam(':item_id', $item_id);
            $trans_stmt->bindParam(':transaction_type', $transaction_type);
            $trans_stmt->bindParam(':bodega_qty', $bodega_qty);
            $trans_stmt->bindParam(':shelves_qty', $shelves_qty);
            $trans_stmt->bindParam(':delivery_qty', $delivery_qty);
            $trans_stmt->bindParam(':unit_price', $item['unit_price']);
            $trans_stmt->bindParam(':notes', $notes);
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $trans_stmt->bindParam(':user_id', $user_id);
            $trans_stmt->execute();
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

// Function to get store-specific inventory report
function get_store_inventory_report($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $has_created_by = column_exists($db, 'inventory_items', 'created_by');
        $item_code_select = column_exists($db, 'inventory_items', 'item_code') ? "ii.item_code" : "ii.barcode as item_code";
        $description_select = column_exists($db, 'inventory_items', 'description') ? "ii.description" : "'' as description";
        $content_select = column_exists($db, 'inventory_items', 'content') ? "ii.content" : "1 as content";
        $created_by_select = $has_created_by ? "ii.created_by" : "NULL as created_by";
        $created_by_name_select = $has_created_by ? "u.full_name as created_by_name" : "NULL as created_by_name";
        $created_by_join = $has_created_by ? "LEFT JOIN users u ON ii.created_by = u.id" : "";
        $created_by_where = $has_created_by ? "WHERE ii.created_by = :user_id" : "";
        
        $query = "SELECT 
                    ii.id,
                    ii.item_name,
                    $item_code_select,
                    ii.barcode,
                    ii.category,
                    $description_select,
                    ii.unit,
                    ii.size,
                    $content_select,
                    ii.unit_price,
                    ii.min_stock_level,
                    ii.supplier_id,
                    ii.location,
                    $created_by_select,
                    COALESCE(ist.bodega_stock, 0) as bodega_stock,
                    COALESCE(ist.shelves_stock, 0) as shelves_stock,
                    COALESCE(ist.delivery_stock, 0) as delivery_stock,
                    COALESCE(ist.total_stock, 0) as total_stock,
                    COALESCE(ist.total_amount, 0) as total_amount,
                    GREATEST(0, ii.min_stock_level - COALESCE(ist.total_stock, 0)) as suggested_order,
                    s.name as supplier_name,
                    $created_by_name_select
                  FROM inventory_items ii
                  LEFT JOIN inventory_stock ist ON ii.id = ist.item_id
                  LEFT JOIN suppliers s ON ii.supplier_id = s.id
                  $created_by_join
                  $created_by_where
                  ORDER BY ii.item_name";
        
        $stmt = $db->prepare($query);
        if ($has_created_by) {
            $stmt->bindParam(':user_id', $user_id);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error getting store inventory report: " . $e->getMessage());
        return [];
    }
}

// Function to get store-specific inventory summary
function get_store_inventory_summary($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $has_created_by = column_exists($db, 'inventory_items', 'created_by');
        $created_by_where = $has_created_by ? "WHERE ii.created_by = :user_id" : "";
        
        $query = "SELECT 
                    COUNT(DISTINCT ii.id) as total_items,
                    COALESCE(SUM(ist.total_stock), 0) as total_quantity,
                    COALESCE(SUM(ist.total_amount), 0) as total_value,
                    COUNT(CASE WHEN ii.min_stock_level > COALESCE(ist.total_stock, 0) THEN 1 END) as items_to_order
                  FROM inventory_items ii
                  LEFT JOIN inventory_stock ist ON ii.id = ist.item_id
                  $created_by_where";
        
        $stmt = $db->prepare($query);
        if ($has_created_by) {
            $stmt->bindParam(':user_id', $user_id);
        }
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_items' => $result['total_items'] ?? 0,
            'total_quantity' => $result['total_quantity'] ?? 0,
            'total_value' => $result['total_value'] ?? 0,
            'items_to_order' => $result['items_to_order'] ?? 0
        ];
        
    } catch (PDOException $e) {
        error_log("Error getting store inventory summary: " . $e->getMessage());
        return [
            'total_items' => 0,
            'total_quantity' => 0,
            'total_value' => 0,
            'items_to_order' => 0
        ];
    }
}

// Delivery Notification Functions

function create_delivery_notification($delivery_id, $notification_type, $message) {
    $database = new Database();
    $db = $database->getConnection();

    $query = "INSERT INTO delivery_notifications (delivery_id, notification_type, message)
              VALUES (:delivery_id, :notification_type, :message)";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':delivery_id', $delivery_id, PDO::PARAM_INT);
    $stmt->bindValue(':notification_type', $notification_type);
    $stmt->bindValue(':message', $message);

    return $stmt->execute();
}

function delete_delivery_notification($delivery_id, $notification_type = null) {
    $database = new Database();
    $db = $database->getConnection();

    if ($notification_type) {
        $query = "DELETE FROM delivery_notifications WHERE delivery_id = :delivery_id AND notification_type = :notification_type";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':delivery_id', $delivery_id, PDO::PARAM_INT);
        $stmt->bindValue(':notification_type', $notification_type);
    } else {
        $query = "DELETE FROM delivery_notifications WHERE delivery_id = :delivery_id";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':delivery_id', $delivery_id, PDO::PARAM_INT);
    }

    return $stmt->execute();
}

function mark_delivery_notification_sent($delivery_id, $notification_type) {
    $database = new Database();
    $db = $database->getConnection();

    $query = "UPDATE delivery_notifications
              SET is_sent = TRUE, sent_at = CURRENT_TIMESTAMP
              WHERE delivery_id = :delivery_id AND notification_type = :notification_type";

    $stmt = $db->prepare($query);
    $stmt->bindValue(':delivery_id', $delivery_id, PDO::PARAM_INT);
    $stmt->bindValue(':notification_type', $notification_type);

    return $stmt->execute();
}

function get_delivery_notifications($delivery_id = null, $is_read = null) {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT dn.*, d.delivery_number, d.po_number, s.name as supplier_name
              FROM delivery_notifications dn
              LEFT JOIN deliveries d ON dn.delivery_id = d.id
              LEFT JOIN suppliers s ON d.supplier_id = s.id
              WHERE 1=1";

    $params = [];

    if ($delivery_id) {
        $query .= " AND dn.delivery_id = :delivery_id";
        $params[':delivery_id'] = $delivery_id;
    }

    if ($is_read !== null) {
        $query .= " AND dn.is_read = :is_read";
        $params[':is_read'] = $is_read;
    }

    $query .= " ORDER BY dn.created_at DESC";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
