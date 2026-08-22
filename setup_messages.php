<?php
// Messages Table Setup Script for MCPIL Laboratory Management System

require_once 'config.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Create messages table
    $create_table_sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        recipient_id INT NOT NULL,
        subject VARCHAR(255) DEFAULT '',
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_recipient_read (recipient_id, is_read),
        INDEX idx_created_at (created_at)
    )";
    
    $db->exec($create_table_sql);
    echo "Messages table created successfully!<br>";
    
    echo "Setup completed! You can now use the messaging system.<br>";
    echo "<br><a href='messages.php'>Go to Messages</a> | <a href='dashboard.php'>Go to Dashboard</a>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
