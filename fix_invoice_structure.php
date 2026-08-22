<?php
require_once 'config.php';

echo "Fixing invoice table structure...\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Make po_id nullable
    $query = "ALTER TABLE purchase_invoices MODIFY COLUMN po_id INT NULL";
    $db->exec($query);
    
    echo "Successfully updated purchase_invoices table to allow standalone invoices.\n";
    echo "You can now save invoices without requiring a purchase order.\n";
    
} catch (Exception $e) {
    echo "Error updating table: " . $e->getMessage() . "\n";
}
?>
