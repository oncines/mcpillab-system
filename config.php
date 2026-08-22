<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mcpillab');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Configuration
define('APP_NAME', 'MCPIL Pharmaceutical Laboratory Management System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/mcpillab');

// Session Configuration
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection Class
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            // First try to connect without database to create it if needed
            $pdo = new PDO("mysql:host=" . $this->host, $this->username, $this->password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name);
            
            // Now connect to the specific database
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if tables exist, if not, create them
            $this->createTablesIfNotExist();
            
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
    
    private function createTablesIfNotExist() {
        try {
            // Check if users table exists
            $result = $this->conn->query("SHOW TABLES LIKE 'users'");
            if ($result->rowCount() == 0) {
                // Read and execute the database schema
                $sql_file = __DIR__ . '/database.sql';
                if (file_exists($sql_file)) {
                    $sql = file_get_contents($sql_file);
                    
                    // Remove the CREATE DATABASE and USE statements since we're already connected
                    $sql = preg_replace('/CREATE DATABASE.*?;/s', '', $sql);
                    $sql = preg_replace('/USE.*?;/s', '', $sql);
                    
                    // Split and execute statements
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    foreach ($statements as $statement) {
                        if (!empty($statement)) {
                            $this->conn->exec($statement);
                        }
                    }
                }
            }
        } catch(PDOException $exception) {
            // Silent fail - tables might already exist
        }
    }
}

// Helper Functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function is_manager() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager';
}

function is_store() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'store';
}

function is_employee() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'employee';
}

function current_user_role() {
    return $_SESSION['user_role'] ?? null;
}

function get_role_home($role = null) {
    $role = $role ?? current_user_role();

    switch ($role) {
        case 'employee':
            return 'employee_home.php';
        case 'admin':
        case 'manager':
        case 'store':
        default:
            return 'dashboard.php';
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function require_login() {
    if (!is_logged_in()) {
        redirect('index.php');
    }
}

function require_roles(array $roles) {
    require_login();

    if (!in_array(current_user_role(), $roles, true)) {
        redirect(get_role_home());
    }
}

function generate_po_number() {
    $database = new Database();
    $db = $database->getConnection();
    
    $year = date('Y');
    $prefix = 'PO-' . $year . '-';
    
    // Get the highest PO number for this year
    $query = "SELECT po_number FROM purchase_orders WHERE po_number LIKE :prefix ORDER BY po_number DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':prefix', $prefix . '%');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Extract the number part and increment
        $last_number = (int) str_replace($prefix, '', $result['po_number']);
        $next_number = $last_number + 1;
    } else {
        // Start with 1 if no POs exist for this year
        $next_number = 1;
    }
    
    return $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
}

function generate_invoice_number() {
    $database = new Database();
    $db = $database->getConnection();
    
    $year = date('Y');
    $prefix = 'INV-' . $year . '-';
    
    // Get the highest invoice number for this year
    $query = "SELECT invoice_number FROM purchase_invoices WHERE invoice_number LIKE :prefix ORDER BY invoice_number DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':prefix', $prefix . '%');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Extract the number part and increment
        $last_number = (int) str_replace($prefix, '', $result['invoice_number']);
        $next_number = $last_number + 1;
    } else {
        // Start with 1 if no invoices exist for this year
        $next_number = 1;
    }
    
    return $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
}

function generate_delivery_number() {
    $database = new Database();
    $db = $database->getConnection();
    
    $year = date('Y');
    $prefix = 'DEL-' . $year . '-';
    
    // Get the highest delivery number for this year
    $query = "SELECT delivery_number FROM deliveries WHERE delivery_number LIKE :prefix ORDER BY delivery_number DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':prefix', $prefix . '%');
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Extract the number part and increment
        $last_number = (int) str_replace($prefix, '', $result['delivery_number']);
        $next_number = $last_number + 1;
    } else {
        // Start with 1 if no deliveries exist for this year
        $next_number = 1;
    }
    
    return $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
}

function format_currency($amount) {
    return '₱' . number_format($amount, 2);
}

function format_date($date) {
    return date('M d, Y', strtotime($date));
}

function getStatusColor($status) {
    switch($status) {
        case 'Pending':
            return 'warning';
        case 'Approved':
            return 'success';
        case 'Rejected':
            return 'danger';
        case 'Processing':
            return 'info';
        case 'Completed':
            return 'primary';
        default:
            return 'secondary';
    }
}

// Include necessary files
require_once 'functions.php';

// Check if user is logged in for protected pages
$protected_pages = [
    'dashboard.php',
    'employee_home.php',
    'purchase_order.php',
    'purchase_invoice.php',
    'employee_profile.php',
    'attendance.php',
    'attendance_camera.php',
    'attendance_history.php',
    'admin_attendance_dashboard.php',
    'admin_employee_detail.php',
    'delivery_tracking.php',
    'delivery_history.php',
    'delivery_details.php',
    'inventory.php',
    'inventory_form.php',
    'inventory_report.php',
    'invoice_list.php',
    'invoice_view.php',
    'reports.php',
    'chat_interface.php'
];

$current_page = basename($_SERVER['PHP_SELF']);

if (in_array($current_page, $protected_pages) && !is_logged_in()) {
    redirect('index.php');
}

if ($current_page === 'dashboard.php' && is_employee()) {
    redirect(get_role_home('employee'));
}
?>
