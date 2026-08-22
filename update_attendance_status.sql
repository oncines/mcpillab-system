-- Update attendance table to support break status
-- This adds 'break' to the status ENUM for proper lunch break tracking

ALTER TABLE attendance 
MODIFY COLUMN status ENUM('present', 'absent', 'late', 'half_day', 'break') DEFAULT 'present';

-- Add indexes for better performance
CREATE INDEX idx_attendance_status ON attendance(status);
CREATE INDEX idx_attendance_date ON attendance(date);
CREATE INDEX idx_attendance_employee_date ON attendance(employee_id, date);

-- Update any existing records that might need status adjustment
-- This is optional - uncomment if you want to retroactively update existing data

-- Update records around lunch time to have break status
-- UPDATE attendance 
-- SET status = 'break' 
-- WHERE check_in >= '12:00:00' AND check_in <= '12:30:00' 
-- AND status = 'present';

COMMIT;
