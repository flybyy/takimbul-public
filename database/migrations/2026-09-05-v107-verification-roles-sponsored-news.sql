-- TakımBul V10.7 — Verification, Staff Roles & Sponsored News
-- Idempotent and non-destructive.
SET NAMES utf8mb4;

SET @v107_admin_role_was_missing := (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admins' AND COLUMN_NAME='role');
SET @sql := IF(@v107_admin_role_was_missing=1,'ALTER TABLE admins ADD COLUMN role ENUM(\'super_admin\',\'authorized\',\'writer\') NOT NULL DEFAULT \'writer\' AFTER display_name','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_public_slug := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admins' AND COLUMN_NAME='public_slug');
SET @sql := IF(@has_public_slug=0,'ALTER TABLE admins ADD COLUMN public_slug VARCHAR(150) NULL AFTER role','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_public_title := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admins' AND COLUMN_NAME='public_title');
SET @sql := IF(@has_public_title=0,'ALTER TABLE admins ADD COLUMN public_title VARCHAR(100) NULL AFTER public_slug','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_public_bio := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admins' AND COLUMN_NAME='public_bio');
SET @sql := IF(@has_public_bio=0,'ALTER TABLE admins ADD COLUMN public_bio VARCHAR(500) NULL AFTER public_title','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_avatar_path := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admins' AND COLUMN_NAME='avatar_path');
SET @sql := IF(@has_avatar_path=0,'ALTER TABLE admins ADD COLUMN avatar_path VARCHAR(255) NULL AFTER public_bio','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_instagram_url := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admins' AND COLUMN_NAME='instagram_url');
SET @sql := IF(@has_instagram_url=0,'ALTER TABLE admins ADD COLUMN instagram_url VARCHAR(255) NULL AFTER avatar_path','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_x_url := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admins' AND COLUMN_NAME='x_url');
SET @sql := IF(@has_x_url=0,'ALTER TABLE admins ADD COLUMN x_url VARCHAR(255) NULL AFTER instagram_url','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_public_profile_enabled := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admins' AND COLUMN_NAME='public_profile_enabled');
SET @sql := IF(@has_public_profile_enabled=0,'ALTER TABLE admins ADD COLUMN public_profile_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER x_url','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF(@v107_admin_role_was_missing=1,"UPDATE admins SET role='super_admin'",'SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_public_slug_uq := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admins' AND INDEX_NAME='uq_admins_public_slug');
SET @sql := IF(@has_public_slug_uq=0,'ALTER TABLE admins ADD UNIQUE KEY uq_admins_public_slug (public_slug)','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_admin_role_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admins' AND INDEX_NAME='idx_admins_role_status');
SET @sql := IF(@has_admin_role_idx=0,'ALTER TABLE admins ADD INDEX idx_admins_role_status (role,status)','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_profile_verified_at := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='profile_verified_at');
SET @sql := IF(@has_profile_verified_at=0,'ALTER TABLE users ADD COLUMN profile_verified_at DATETIME NULL AFTER email_verified_at','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_profile_verified_by := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='profile_verified_by_admin_id');
SET @sql := IF(@has_profile_verified_by=0,'ALTER TABLE users ADD COLUMN profile_verified_by_admin_id BIGINT UNSIGNED NULL AFTER profile_verified_at','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS news_sponsors (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    logo_url VARCHAR(500) NULL,
    website_url VARCHAR(500) NULL,
    description VARCHAR(500) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by_admin_id BIGINT UNSIGNED NULL,
    updated_by_admin_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_news_sponsors_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    actor_admin_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(60) NOT NULL,
    target_id BIGINT UNSIGNED NULL,
    metadata_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_audit_actor (actor_admin_id,created_at),
    INDEX idx_admin_audit_target (target_type,target_id,created_at),
    INDEX idx_admin_audit_action (action,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_sponsor_id := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='news' AND COLUMN_NAME='sponsor_id');
SET @sql := IF(@has_sponsor_id=0,'ALTER TABLE news ADD COLUMN sponsor_id BIGINT UNSIGNED NULL AFTER updated_by_admin_id','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_sponsor_cta_text := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='news' AND COLUMN_NAME='sponsor_cta_text');
SET @sql := IF(@has_sponsor_cta_text=0,'ALTER TABLE news ADD COLUMN sponsor_cta_text VARCHAR(80) NULL AFTER sponsor_id','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_sponsor_cta_url := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='news' AND COLUMN_NAME='sponsor_cta_url');
SET @sql := IF(@has_sponsor_cta_url=0,'ALTER TABLE news ADD COLUMN sponsor_cta_url VARCHAR(500) NULL AFTER sponsor_cta_text','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_sponsor_idx := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='news' AND INDEX_NAME='idx_news_sponsor');
SET @sql := IF(@has_sponsor_idx=0,'ALTER TABLE news ADD INDEX idx_news_sponsor (sponsor_id,status,published_at)','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
