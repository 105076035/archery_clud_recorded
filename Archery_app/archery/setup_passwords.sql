-- setup_passwords.sql
-- Run this ONCE in phpMyAdmin to set initial passwords for existing user_accounts.
-- Uses PHP's PASSWORD_BCRYPT format. After setup, users can log in with these credentials.
-- Change the passwords below before running!

-- This script sets password for any existing users.
-- If you need to add NEW users, use the INSERT at the bottom.

-- Example: set password "Archery123!" for all existing accounts (change per user as needed)
-- You must generate bcrypt hashes. Use the generate_hash.php script below, or an online bcrypt tool.

-- To generate a hash via PHP CLI:
--   php -r "echo password_hash('YourPassword123', PASSWORD_DEFAULT);"

-- EXAMPLE UPDATES (replace the hash strings with ones you generate):
-- UPDATE user_accounts SET password_hash = '$2y$12$YOURHASHHERE', is_active = 1 WHERE username = 'irene.moser';

-- ─── Quick setup: set every account to password "Archery1!" ──────────────────
-- Hash for "Archery1!" generated with PASSWORD_DEFAULT:
-- Run: php -r "echo password_hash('Archery1!', PASSWORD_DEFAULT);"
-- Then paste result below:

UPDATE user_accounts
SET password_hash = '$2y$12$examplehashreplaceme000000000000000000000000000000000u',
    is_active     = 1
WHERE is_active = 0 OR password_hash = '';

-- ─── Add a new admin user ────────────────────────────────────────────────────
-- INSERT INTO user_accounts (username, password_hash, role, is_active)
-- VALUES ('admin', '$2y$12$YOURHASHHERE', 'admin', 1);
