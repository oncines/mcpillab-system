<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

// Add content field to inventory_items table
try {
    $alter_query = "ALTER TABLE inventory_items ADD COLUMN content INT DEFAULT 1 AFTER unit_price";
    $db->exec($alter_query);
    echo "Content field added successfully to inventory_items table.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Content field already exists in inventory_items table.\n";
    } else {
        echo "Error adding content field: " . $e->getMessage() . "\n";
    }
}

// Update existing items with sample content values
$update_query = "UPDATE inventory_items SET content = CASE 
    WHEN size LIKE '%500ml%' THEN 12
    WHEN size LIKE '%1L%' THEN 6
    WHEN size LIKE '%250ml%' THEN 24
    WHEN size LIKE '%200ml%' THEN 30
    WHEN size LIKE '%100ml%' THEN 48
    WHEN size LIKE '%50ml%' THEN 72
    ELSE 1
END WHERE content = 1";

$db->exec($update_query);
echo "Updated content values for existing items.\n";
?>
