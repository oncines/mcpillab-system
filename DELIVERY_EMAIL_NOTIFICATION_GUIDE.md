# Delivery Email Notification Implementation

## Overview
This implementation adds email notification functionality to the delivery tracking system. When an email notification is clicked, a "Notify Email" modal appears pre-filled with recipient and subject information. After sending the email, the inbox notification is automatically deleted.

## Files Created/Modified

### New Files
1. **delivery_notifications.sql** - SQL file to create the delivery_notifications table
2. **send_delivery_notification.php** - Backend endpoint to handle email sending and notification deletion
3. **setup_delivery_notifications.php** - Setup script to create the delivery_notifications table
4. **DELIVERY_EMAIL_NOTIFICATION_GUIDE.md** - This documentation file

### Modified Files
1. **functions.php** - Added delivery notification functions:
   - `create_delivery_notification()` - Create a new notification
   - `delete_delivery_notification()` - Delete notifications
   - `mark_delivery_notification_sent()` - Mark notification as sent
   - `get_delivery_notifications()` - Retrieve notifications

2. **delivery_tracking.php** - Updated to:
   - Include supplier_email in delivery data
   - Add "Notify Email" modal
   - Update JavaScript to handle email notification flow
   - Add AJAX call to send email and delete inbox notification

## Database Schema

### delivery_notifications Table
```sql
CREATE TABLE delivery_notifications (
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
)
```

## Setup Instructions

1. **Run the setup script** to create the delivery_notifications table:
   ```
   Open in browser: http://localhost/mcpillab/setup_delivery_notifications.php
   ```

2. **Verify the table was created** by checking your database.

## How It Works

### User Flow
1. User clicks "Notify Customer" button in delivery tracking panel
2. "Shipment Notification" modal appears with options:
   - Inbox Notification (WhatsApp)
   - Email Notification
   - SMS Notification
3. User selects "Email Notification" and clicks "Continue"
4. "Notify Email" modal appears with pre-filled fields:
   - **Recipient**: Supplier's email address
   - **Subject**: "Quick Update on Your Order #[PO Number] - [Supplier Name]"
   - **Message**: Empty textarea for user to enter message
5. User enters message and clicks "Send"
6. System sends email (simulated) and deletes the inbox notification
7. Success message is displayed

### Technical Flow
1. JavaScript detects email notification selection
2. Opens email modal with pre-filled data from delivery object
3. On send, AJAX POST to `send_delivery_notification.php`
4. Backend:
   - Validates input
   - Logs email details (simulated email sending)
   - Deletes inbox notification from database
   - Marks email notification as sent
5. Returns JSON response
6. JavaScript handles response and shows success/error message

## Customization

### Email Sending
Currently, email sending is simulated by logging to error_log. To implement actual email sending, modify `send_delivery_notification.php`:

```php
// Replace the logging section with actual email sending
require 'PHPMailer/PHPMailerAutoload.php';

$mail = new PHPMailer;
$mail->isSMTP();
$mail->Host = 'smtp.example.com';
$mail->SMTPAuth = true;
$mail->Username = 'your@email.com';
$mail->Password = 'password';
$mail->setFrom('your@email.com', 'McPIL Pharmaceutical Laboratory');
$mail->addAddress($recipient);
$mail->Subject = $subject;
$mail->Body = $message;

if (!$mail->send()) {
    throw new Exception('Email could not be sent.');
}
```

### Notification Types
The system supports three notification types:
- `inbox` - WhatsApp/inbox notifications
- `email` - Email notifications
- `sms` - SMS notifications

You can extend this by adding more types to the ENUM in the database schema.

## Testing

1. Navigate to delivery_tracking.php
2. Click on a delivery to open the detail panel
3. Click "Notify Customer"
4. Select "Email Notification"
5. Click "Continue"
6. Verify the email modal appears with correct pre-filled data
7. Enter a message and click "Send"
8. Verify success message appears
9. Check database to confirm inbox notification was deleted

## Notes

- The supplier_email field is now included in the delivery data query
- If a supplier has no email address, the recipient field will be empty
- The system logs email details for debugging purposes
- In production, implement proper email sending functionality
- Consider adding email templates for consistent formatting
