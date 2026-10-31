-- TakımBul V10.6 — Club & Squad Experience
-- Idempotent and non-destructive: preserves all existing teams and memberships.
SET NAMES utf8mb4;

SET @has_short_name := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='teams' AND COLUMN_NAME='short_name');
SET @sql := IF(@has_short_name=0,'ALTER TABLE teams ADD COLUMN short_name VARCHAR(24) NULL AFTER logo_path','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_primary_color := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='teams' AND COLUMN_NAME='primary_color');
SET @sql := IF(@has_primary_color=0,'ALTER TABLE teams ADD COLUMN primary_color CHAR(7) NULL AFTER short_name','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_secondary_color := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='teams' AND COLUMN_NAME='secondary_color');
SET @sql := IF(@has_secondary_color=0,'ALTER TABLE teams ADD COLUMN secondary_color CHAR(7) NULL AFTER primary_color','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_home_venue := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='teams' AND COLUMN_NAME='home_venue');
SET @sql := IF(@has_home_venue=0,'ALTER TABLE teams ADD COLUMN home_venue VARCHAR(160) NULL AFTER secondary_color','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_instagram_url := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='teams' AND COLUMN_NAME='instagram_url');
SET @sql := IF(@has_instagram_url=0,'ALTER TABLE teams ADD COLUMN instagram_url VARCHAR(255) NULL AFTER home_venue','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @has_team_position := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='team_members' AND COLUMN_NAME='team_position');
SET @sql := IF(@has_team_position=0,'ALTER TABLE team_members ADD COLUMN team_position ENUM(\'Kaleci\',\'Defans\',\'Orta Saha\',\'Kanat\',\'Forvet\') NULL AFTER jersey_number','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @has_squad_status := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='team_members' AND COLUMN_NAME='squad_status');
SET @sql := IF(@has_squad_status=0,'ALTER TABLE team_members ADD COLUMN squad_status ENUM(\'starter\',\'substitute\',\'reserve\') NOT NULL DEFAULT \'reserve\' AFTER team_position','SELECT 1'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS team_formations (
    team_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    match_format ENUM('5v5','6v6','7v7','8v8','11v11') NOT NULL DEFAULT '7v7',
    formation_code VARCHAR(24) NOT NULL,
    slots_json LONGTEXT NOT NULL,
    updated_by_user_id BIGINT UNSIGNED NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_team_formation_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_formation_updater FOREIGN KEY (updated_by_user_id) REFERENCES users(id),
    INDEX idx_team_formation_format (match_format, formation_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
