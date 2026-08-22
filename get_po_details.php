<?php
require_once 'config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid PO ID']);
    exit;
}

$po_id = $_GET['id'];

try {
    // Check database connection first
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    
    // Get PO details
    $po_details = get_po_details($po_id);
    
    if (!$po_details) {
        echo json_encode(['error' => 'Purchase Order not found']);
        exit;
    }
    
    // Get PO messages
    $messages = get_po_messages($po_id);
    
    $response = [
        'po' => $po_details,
        'messages' => $messages
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
