<?php
require_once 'config.php';

$database = new Database();
$db = $database->getConnection();

echo "<h3>Inventory Items Table Structure:</h3>";
$result = $db->query("DESCRIBE inventory_items");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
while($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
}
echo "</table>";
?>
