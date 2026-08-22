<?php
require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['full_name'] = 'Test User';
$_SESSION['user_role'] = 'store';

echo "Testing inventory item addition...\n";

// Test the function
$result = add_inventory_item(
    'Test Item',
    'TEST001',
    'chemicals',
    'Test Description',
    'bottle',
    10.5,
    150.00,
    1,
    'Test Location',
    5
);

echo "Result: " . ($result ? "SUCCESS" : "FAILED") . "\n";

if (!$result) {
    echo "Check error logs for details\n";
}
?>
