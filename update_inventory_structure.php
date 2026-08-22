<!DOCTYPE html>
<html>
<head>
    <title>Update Inventory Structure</title>
</head>
<body>
    <h1>Updating Inventory Database Structure...</h1>
    <?php
    require_once 'config.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h3>1. Adding content field to inventory_items table...</h3>";
    try {
        $alter_query = "ALTER TABLE inventory_items ADD COLUMN content INT DEFAULT 1 AFTER unit_price";
        $db->exec($alter_query);
        echo "<p style='color: green;'>✓ Content field added successfully.</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>ℹ Content field already exists.</p>";
        } else {
            echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>2. Updating content values for existing items...</h3>";
    $update_query = "UPDATE inventory_items SET content = CASE 
        WHEN size LIKE '%500ml%' THEN 12
        WHEN size LIKE '%1L%' THEN 6
        WHEN size LIKE '%250ml%' THEN 24
        WHEN size LIKE '%200ml%' THEN 30
        WHEN size LIKE '%100ml%' THEN 48
        WHEN size LIKE '%50ml%' THEN 72
        ELSE 1
    END";
    
    try {
        $result = $db->exec($update_query);
        echo "<p style='color: green;'>✓ Updated content values for $result items.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Error updating content: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>3. Verification - Sample data:</h3>";
    $check_query = "SELECT item_name, size, content FROM inventory_items LIMIT 5";
    $stmt = $db->prepare($check_query);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Item Name</th><th>Size</th><th>Content</th></tr>";
    foreach ($items as $item) {
        echo "<tr>";
        echo "<td>" . $item['item_name'] . "</td>";
        echo "<td>" . $item['size'] . "</td>";
        echo "<td>" . $item['content'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✓ Update Complete!</h3>";
    echo "<p><a href='reports.php'>Go to Reports Page</a></p>";
    ?>
</body>
</html>
