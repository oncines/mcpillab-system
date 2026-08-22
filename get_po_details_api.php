<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!is_logged_in() || (!is_admin() && !is_manager() && !is_store())) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid PO ID']);
    exit;
}

$po_id = $_GET['id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get PO details
    $query = "SELECT po.*, u.full_name as created_by_name, u.role as created_by_role, s.name as supplier_name 
              FROM purchase_orders po 
              LEFT JOIN users u ON po.created_by = u.id 
              LEFT JOIN suppliers s ON po.supplier_id = s.id 
              WHERE po.id = :po_id";
    if (is_store()) {
        $query .= " AND po.created_by = :user_id";
    }
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':po_id', $po_id, PDO::PARAM_INT);
    if (is_store()) {
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    }
    $stmt->execute();
    $po_details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$po_details) {
        echo json_encode(['error' => 'Purchase Order not found']);
        exit;
    }
    
    // Get PO items
    $items_query = "SELECT * FROM purchase_order_items WHERE po_id = :po_id";
    $items_stmt = $pdo->prepare($items_query);
    $items_stmt->bindParam(':po_id', $po_id);
    $items_stmt->execute();
    $po_details['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get PO messages (check if table exists first)
    $messages = [];
    try {
        $messages_query = "SELECT pom.*, u.full_name, u.role 
                           FROM purchase_order_messages pom 
                           LEFT JOIN users u ON pom.user_id = u.id 
                           WHERE pom.po_id = :po_id 
                           ORDER BY pom.created_at ASC";
        $messages_stmt = $pdo->prepare($messages_query);
        $messages_stmt->bindParam(':po_id', $po_id);
        $messages_stmt->execute();
        $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Table doesn't exist, continue without messages
        $messages = [];
    }
    
    $response = [
        'po' => $po_details,
        'messages' => $messages
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
