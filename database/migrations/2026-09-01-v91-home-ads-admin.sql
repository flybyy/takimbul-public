-- TakımBul v9.1 Professional Home / Reliable Ads / Isolated Admin Auth
-- Existing v9 data is preserved. Run once on an existing v9 database.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admins_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The administration identity is independent from public users.
INSERT INTO admins (username,email,password_hash,display_name,status)
VALUES ('veribayt','admin@takimbul.com.tr','!','Veribayt Yönetim','active')
ON DUPLICATE KEY UPDATE email=VALUES(email),password_hash=VALUES(password_hash),display_name=VALUES(display_name),status='active';

-- Preserve referential history from old releases without leaving an account that can log in as a user.
-- The row becomes a non-login tombstone; fresh v9.1 installs never create it in users.
UPDATE users
SET username=NULL,
    email=CONCAT('legacy-admin-',id,'@invalid.local'),
    password_hash='!',
    display_name='Eski Yönetim Kaydı',
    role='player',
    status='deleted',
    email_verified_at=NULL
WHERE username='veribayt' OR email='admin@takimbul.com.tr' OR role='admin';

-- Site-setting writes now reference admins, not public users.
SET @has_setting_admin_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='site_settings' AND COLUMN_NAME='updated_by_admin_id'
);
SET @sql := IF(@has_setting_admin_col=0,
    'ALTER TABLE site_settings ADD COLUMN updated_by_admin_id BIGINT UNSIGNED NULL AFTER updated_by_user_id',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_setting_admin_fk := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='site_settings' AND CONSTRAINT_NAME='fk_site_setting_admin_v91'
);
SET @sql := IF(@has_setting_admin_fk=0,
    'ALTER TABLE site_settings ADD CONSTRAINT fk_site_setting_admin_v91 FOREIGN KEY (updated_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Scout moderation now records the independent administrator id.
SET @has_scout_admin_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='scout_accounts' AND COLUMN_NAME='reviewed_by_admin_id'
);
SET @sql := IF(@has_scout_admin_col=0,
    'ALTER TABLE scout_accounts ADD COLUMN reviewed_by_admin_id BIGINT UNSIGNED NULL AFTER reviewed_by',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_scout_admin_fk := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='scout_accounts' AND CONSTRAINT_NAME='fk_scout_reviewer_admin_v91'
);
SET @sql := IF(@has_scout_admin_fk=0,
    'ALTER TABLE scout_accounts ADD CONSTRAINT fk_scout_reviewer_admin_v91 FOREIGN KEY (reviewed_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Email verification is intentionally optional for this launch phase.
INSERT INTO site_settings (setting_key,setting_value)
VALUES ('require_email_verification','0')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
