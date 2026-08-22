# Camera Attendance System Guide

## Overview
The Camera Attendance System allows employees to mark their attendance with photo verification, which administrators can then review and approve.

## Features
- 📷 **Photo Capture**: Employees take a photo when marking attendance
- 📍 **Location Tracking**: Records GPS location and address
- 🔔 **Real-time Notifications**: Instant notifications to administrators
- ✅ **Approval System**: Admins can approve/reject attendance with photos
- 📊 **Integrated Reporting**: All attendance data appears in main attendance reports

## How It Works

### For Employees
1. **Access Camera Attendance**: Go to Dashboard → Attendance → Mark Attendance with Camera
2. **Fill Details**: Select attendance type (Clock In/Out), add notes
3. **Capture Photo**: Take a clear photo of yourself
4. **Submit**: Attendance is sent to administrators for approval

### For Administrators
1. **Receive Notifications**: Get instant alerts when employees submit attendance
2. **Review Photos**: View attendance photos in:
   - Dashboard notifications
   - Photo Notifications page
   - Attendance Approval page
   - Main Attendance page
3. **Approve/Reject**: Review details and approve or reject attendance
4. **Track Status**: Monitor all attendance submissions and their status

## Pages & Functions

### Employee Pages
- **attendance_camera.php**: Camera interface for capturing attendance
- **attendance_history.php**: View personal attendance history

### Admin Pages
- **admin_attendance_approval.php**: Approve/reject pending attendance
- **admin_notifications.php**: View all attendance notifications
- **attendance.php**: Main attendance management with photo verification section
- **dashboard.php**: Quick overview with pending attendance alerts

## Database Tables
- **camera_attendance**: Stores photo attendance with location data
- **attendance_notifications**: Real-time notifications for admins
- **attendance**: Main attendance records (updated upon approval)

## Setup Instructions

### 1. Run Database Setup
```bash
Visit: setup_camera_attendance.php
```
This creates all necessary tables and directories.

### 2. Test the System
```bash
Visit: test_camera_attendance.php
```
Verify all components are working correctly.

### 3. Add Employees
Ensure employees are added to the system before they can use camera attendance.

## File Structure
```
public/
├── attendance_photos/     # Stores attendance photos
└── attendance_videos/     # Stores attendance videos (if enabled)

Key Files:
├── attendance_camera.php          # Employee camera interface
├── admin_attendance_approval.php  # Admin approval interface
├── admin_notifications.php        # Notification center
├── attendance.php                 # Main attendance management
├── functions.php                  # All camera attendance functions
└── setup_camera_attendance.php    # Database setup script
```

## Approval Workflow
1. **Employee submits attendance** → Photo captured with location
2. **Notification sent** → Admins receive instant notification
3. **Admin reviews** → Check photo, location, time, and details
4. **Decision made** → Approve or reject the attendance
5. **Record updated** → Main attendance table updated with status

## Security Features
- **Photo Verification**: Ensures correct person is marking attendance
- **Location Tracking**: Verifies attendance from legitimate locations
- **Timestamp**: Accurate time recording with device info
- **Admin Oversight**: All submissions require approval
- **Audit Trail**: Complete history of all attendance activities

## Troubleshooting

### Camera Not Working
- Check browser permissions for camera access
- Ensure HTTPS is enabled (required for camera access)
- Try a different browser

### Photos Not Saving
- Check if `public/attendance_photos/` directory exists
- Verify directory permissions (755)
- Check PHP upload limits

### Notifications Not Showing
- Verify `attendance_notifications` table exists
- Check admin user has notification permissions
- Refresh the page or check browser console for errors

### Approval Not Working
- Ensure all database tables are created
- Check if employee records exist
- Verify admin user has proper permissions

## Best Practices
1. **Clear Photos**: Ensure good lighting and clear face visibility
2. **Consistent Locations**: Use same location for clock in/out
3. **Regular Reviews**: Admins should review attendance daily
4. **Proper Training**: Train employees on proper photo capture
5. **Backup Photos**: Regular backup of attendance photos

## Support
For issues or questions:
1. Run the test script: `test_camera_attendance.php`
2. Check browser console for JavaScript errors
3. Verify database setup with: `setup_camera_attendance.php`
4. Review PHP error logs for server-side issues
