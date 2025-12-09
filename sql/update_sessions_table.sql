-- Add attendance code columns to sessions table
ALTER TABLE sessions 
ADD COLUMN attendance_code VARCHAR(10) UNIQUE AFTER location,
ADD COLUMN code_expires_at DATETIME AFTER attendance_code,
ADD COLUMN allow_self_checkin BOOLEAN DEFAULT TRUE AFTER code_expires_at;

-- Update existing sessions with random codes (if any exist)
UPDATE sessions 
SET attendance_code = LPAD(FLOOR(RAND() * 1000000), 6, '0'),
    code_expires_at = DATE_ADD(CONCAT(session_date, ' ', end_time), INTERVAL 15 MINUTE),
    allow_self_checkin = 1
WHERE attendance_code IS NULL;
