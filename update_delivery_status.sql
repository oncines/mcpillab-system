-- Add 'approved' status to the deliveries table status ENUM
ALTER TABLE deliveries MODIFY status ENUM('pending', 'approved', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending';

-- This script updates the delivery status to include the new 'approved' status
-- Run this script in your MySQL database to add the new status option
