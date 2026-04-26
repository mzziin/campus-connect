-- Campus Connect Default Admin Account
-- Password is: Admin@1234
-- Generate the hash using: php -r "echo password_hash('Admin@1234', PASSWORD_BCRYPT);"
-- Replace the hash below with the actual output before importing

INSERT INTO admins (username, email, password_hash) VALUES (
    'admin',
    'admin@campusconnect.local',
    '$2y$10$5HcXi9nKERAR5/DPRNUwruGjuXsn23AtwmV8exnJMJ/RjlXDAwrb6'
);