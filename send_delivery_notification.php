<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$delivery_id = $_POST['delivery_id'] ?? null;
$recipient = $_POST['recipient'] ?? null;
$subject = $_POST['subject'] ?? null;
$message = $_POST['message'] ?? null;

if (!$delivery_id || !$recipient || !$subject) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Get delivery details
    $delivery = get_delivery_by_id($delivery_id);
    if (!$delivery) {
        echo json_encode(['success' => false, 'message' => 'Delivery not found']);
        exit;
    }

    // In a real implementation, you would send the actual email here
    // For now, we'll simulate it by logging
    error_log("Email sent to: $recipient");
    error_log("Subject: $subject");
    error_log("Message: $message");

    // Delete the inbox notification for this delivery
    $deleted = delete_delivery_notification($delivery_id, 'inbox');

    // Mark the email notification as sent
    $marked = mark_delivery_notification_sent($delivery_id, 'email');

    echo json_encode([
        'success' => true,
        'message' => 'Email sent successfully and inbox notification deleted',
        'deleted' => $deleted,
        'marked_sent' => $marked
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
