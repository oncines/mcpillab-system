# Attendance Status System Update

## Overview
Updated the attendance system to properly handle shift-based attendance with automatic status determination based on check-in times.

## New Status Logic

### Shift Schedule
- **Morning Shift**: 8:00 AM - 12:00 PM
- **Lunch Break**: 12:00 PM - 1:00 PM  
- **Afternoon Shift**: 1:00 PM - 5:00 PM

### Status Determination Rules

| Check-in Time | Status | Description |
|---------------|--------|-------------|
| 7:30 AM - 7:59 AM | Present | Early but acceptable |
| 8:00 AM - 8:30 AM | Present | On time for morning shift |
| 8:30 AM - 12:00 PM | Late | Late for morning shift |
| 12:00 PM - 12:30 PM | Break | Lunch break (clock out time) |
| 12:30 PM - 12:59 PM | Present | Early for afternoon shift |
| 1:00 PM - 1:30 PM | Present | On time for afternoon shift |
| 1:30 PM - 5:00 PM | Late | Late for afternoon shift |
| After 5:00 PM | Present | Overtime or late departure |
| Before 7:30 AM | Present | Very early |

## Database Changes

### New Status Type
- Added `'break'` status to the attendance table ENUM
- Run `update_attendance_status.sql` to update your database

### Updated Table Structure
```sql
ALTER TABLE attendance 
MODIFY COLUMN status ENUM('present', 'absent', 'late', 'half_day', 'break') DEFAULT 'present';
```

## New Functions Added

### `determine_attendance_status($check_in_time, $check_out_time = null)`
Automatically determines attendance status based on check-in time and shift schedule.

### `get_attendance_records_with_shifts($employee_id, $date_from, $date_to, $limit, $offset)`
Enhanced version of `get_attendance_records()` that includes:
- Shift information (Morning/Afternoon/Lunch Break/Overtime)
- Shift time periods
- Formatted status display with CSS classes

### `determine_shift_info($check_in, $check_out)`
Returns shift information based on check-in time:
- Shift name
- Shift time period

### `get_status_display($status)`
Returns formatted status display with:
- Human-readable text
- CSS class for styling (success/warning/danger/info/secondary)

## Frontend Updates

### Attendance Camera (`attendance_camera.php`)
- **Automatic Status Detection**: Uses `determine_attendance_status()` to set correct status
- **Time-Based Type Selection**: Automatically selects Clock In/Out based on time
- **Visual Indicators**: Shows current time and shift schedule

### Admin Attendance (`attendance.php`)
- **Shift Column**: New column showing shift information
- **Enhanced Status Display**: Color-coded badges with proper status text
- **Better Time Display**: Shows both Clock In and Clock Out times clearly

## Status Display Colors

| Status | Color | Badge Class |
|--------|-------|-------------|
| Present | Green | `bg-success` |
| Late | Yellow | `bg-warning` |
| Absent | Red | `bg-danger` |
| Half Day | Blue | `bg-info` |
| Break | Gray | `bg-secondary` |

## Usage Examples

### Before 8:00 AM
- **Status**: Present
- **Display**: Green badge "Present"
- **Reason**: Early arrival but acceptable

### 11:00 AM
- **Status**: Late  
- **Display**: Yellow badge "Late"
- **Reason**: Late for morning shift

### 12:00 PM
- **Status**: Break
- **Display**: Gray badge "Break"
- **Reason**: Lunch break time

### 1:30 PM
- **Status**: Late
- **Display**: Yellow badge "Late"  
- **Reason**: Late for afternoon shift

## Implementation Notes

1. **Automatic Processing**: Status is determined automatically when attendance is recorded
2. **Manual Override**: Admin can still manually change status if needed
3. **Shift Awareness**: System understands morning vs afternoon shifts
4. **Break Handling**: Properly handles lunch break as separate status
5. **Backward Compatible**: Existing records continue to work

## Files Modified

- `functions.php`: Added new status determination functions
- `attendance_camera.php`: Updated to use automatic status detection
- `attendance.php`: Enhanced admin display with shift information
- `update_attendance_status.sql`: Database update script

## Testing

Use `test_time_logic.html` to verify:
- Time-based attendance type selection
- Status determination logic
- Shift identification

The system now properly handles your shift schedule and provides accurate attendance status tracking!
