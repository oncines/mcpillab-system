<?php
echo "<h2>Recent Error Logs</h2>";
echo "<p>Showing last 50 lines from PHP error log...</p>";

// Try to find the error log file
$error_log_paths = [
    'C:/xampp/apache/logs/error.log',
    'C:/xampp/php/logs/php_error_log',
    'C:/xampp/tmp/php_error_log',
    ini_get('error_log'),
    '/var/log/apache2/error.log',
    '/var/log/php_errors.log'
];

$found_log = false;
foreach ($error_log_paths as $log_path) {
    if (file_exists($log_path) && is_readable($log_path)) {
        echo "<h3>Error Log: $log_path</h3>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc; max-height: 400px; overflow-y: auto;'>";
        
        $lines = file($log_path);
        $recent_lines = array_slice($lines, -50);
        
        foreach ($recent_lines as $line) {
            // Highlight inventory-related errors
            if (strpos($line, 'inventory') !== false || strpos($line, 'Inventory') !== false) {
                echo "<span style='background: yellow; font-weight: bold;'>" . htmlspecialchars($line) . "</span>";
            } else {
                echo htmlspecialchars($line);
            }
        }
        
        echo "</pre>";
        $found_log = true;
        break;
    }
}

if (!$found_log) {
    echo "<p style='color: red;'>Could not find readable error log file.</p>";
    echo "<p>Checking common locations:</p><ul>";
    foreach ($error_log_paths as $log_path) {
        echo "<li>" . htmlspecialchars($log_path) . " - " . (file_exists($log_path) ? "EXISTS" : "NOT FOUND") . "</li>";
    }
    echo "</ul>";
}

// Also try to show recent PHP errors from the current directory
echo "<h3>Current Directory Error Files:</h3>";
$files = glob('*.log');
if (empty($files)) {
    echo "<p>No log files found in current directory.</p>";
} else {
    foreach ($files as $file) {
        echo "<h4>$file</h4>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars(file_get_contents($file));
        echo "</pre>";
    }
}
?>
