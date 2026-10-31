-- TakımBul v8.1 Professional Stabilization
-- Idempotent admin identity bootstrap. Password is stored only as a password_hash.
SET NAMES utf8mb4;

UPDATE users
SET email='admin@takimbul.com.tr', username='veribayt', password_hash='!', display_name='Veribayt Yönetim',
    role='admin', status='active', email_verified_at=COALESCE(email_verified_at,NOW())
WHERE username='veribayt' OR email='admin@takimbul.com.tr'
LIMIT 1;

INSERT INTO users (email,password_hash,display_name,username,province,district,role,status,email_verified_at)
SELECT 'admin@takimbul.com.tr','!','Veribayt Yönetim','veribayt','Antalya','Alanya','admin','active',NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username='veribayt' OR email='admin@takimbul.com.tr');

INSERT INTO player_profiles (user_id,visibility)
SELECT id,'private' FROM users WHERE username='veribayt'
ON DUPLICATE KEY UPDATE visibility=VALUES(visibility);
