-- Delivery Notifications Table
-- This table stores inbox notifications for delivery tracking

CREATE TABLE IF NOT EXISTS delivery_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT NOT NULL,
    notification_type ENUM('inbox', 'email', 'sms') DEFAULT 'inbox',
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    FOREIGN KEY (delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE,
    INDEX idx_delivery_notifications (delivery_id, is_read, is_sent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
