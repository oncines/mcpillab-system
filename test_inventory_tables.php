<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

echo "Database connection: " . ($db ? "OK" : "FAILED") . PHP_EOL;
echo "Checking inventory tables..." . PHP_EOL;

$tables = ['inventory_items', 'inventory_stock', 'inventory_transactions'];
foreach ($tables as $table) {
    try {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        echo $table . ": " . ($result->rowCount() > 0 ? "EXISTS" : "MISSING") . PHP_EOL;
        
        if ($result->rowCount() > 0) {
            // Show table structure
            $structure = $db->query("DESCRIBE $table");
            echo "  Structure:" . PHP_EOL;
            while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
                echo "    - " . $row['Field'] . " (" . $row['Type'] . ")" . PHP_EOL;
            }
        }
    } catch (Exception $e) {
        echo $table . ": ERROR - " . $e->getMessage() . PHP_EOL;
    }
}
?>
