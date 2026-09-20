-- Add last_login_at column to users table
ALTER TABLE users ADD COLUMN last_login_at DATETIME DEFAULT NULL;

-- Initialize existing users with their most recent activity (passkey, API token, or created_at)
UPDATE users 
SET last_login_at = COALESCE(
    (SELECT MAX(last_used_at) FROM (
        SELECT last_used_at FROM user_passkeys WHERE user_id = users.id
        UNION ALL
        SELECT last_used_at FROM api_tokens WHERE user_id = users.id
    )),
    created_at
)
WHERE last_login_at IS NULL;
