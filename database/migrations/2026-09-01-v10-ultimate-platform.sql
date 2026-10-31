SET NAMES utf8mb4;

SET @has_v10_privacy_col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='privacy_accepted_at'
);
SET @sql := IF(@has_v10_privacy_col=0,
    'ALTER TABLE users ADD COLUMN privacy_accepted_at DATETIME NULL AFTER phone',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS admin_user_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NOT NULL,
    note VARCHAR(1000) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_v10_admin_user_note_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_v10_admin_user_note_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    INDEX idx_v10_admin_user_notes_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_search_profiles (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NULL,
    primary_position ENUM('Kaleci','Defans','Orta Saha','Kanat','Forvet') NULL,
    secondary_position ENUM('Kaleci','Defans','Orta Saha','Kanat','Forvet') NULL,
    desired_level ENUM('Eğlence','Orta','Rekabetçi') NULL,
    availability_text VARCHAR(300) NULL,
    bio VARCHAR(400) NULL,
    status ENUM('active','paused') NOT NULL DEFAULT 'active',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_v10_team_search_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_v10_team_search_location (status, province, district),
    INDEX idx_v10_team_search_position (primary_position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_invites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    invited_by BIGINT UNSIGNED NOT NULL,
    message VARCHAR(300) NULL,
    status ENUM('pending','accepted','rejected','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    active_key VARCHAR(120) GENERATED ALWAYS AS (CASE WHEN status='pending' THEN CONCAT(team_id,':',user_id) ELSE NULL END) STORED,
    UNIQUE KEY uq_v10_team_invite_pending (active_key),
    CONSTRAINT fk_v10_team_invite_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_v10_team_invite_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_v10_team_invite_actor FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_v10_team_invites_user_status (user_id, status, created_at),
    INDEX idx_v10_team_invites_team_status (team_id, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE ads MODIFY placement ENUM('home_top','welcome_takeover','home_feed','home_sidebar','players_feed','opponents_feed','match_inline','team_inline','career_inline','mobile_feed') NOT NULL;

INSERT INTO site_settings (setting_key,setting_value) VALUES ('welcome_ad_enabled','1')
ON DUPLICATE KEY UPDATE setting_value=setting_value;
