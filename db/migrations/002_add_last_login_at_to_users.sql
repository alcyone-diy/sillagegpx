-- Add last_login_at column to users table
ALTER TABLE users ADD COLUMN last_login_at DATETIME DEFAULT NULL;

-- Initialize existing users with their registration date
UPDATE users SET last_login_at = created_at WHERE last_login_at IS NULL;
