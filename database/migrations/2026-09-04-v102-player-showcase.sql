-- TakımBul v10.2 — Oyuncu Vitrini / Profil Amacı
-- Existing amateur ads remain team-search profiles for backward compatibility.
SET NAMES utf8mb4;

SET @has_profile_intent := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='amateur_player_ads' AND COLUMN_NAME='profile_intent'
);
SET @sql := IF(@has_profile_intent=0,
    'ALTER TABLE amateur_player_ads ADD COLUMN profile_intent ENUM(\'team_search\',\'showcase\',\'both\') NOT NULL DEFAULT \'team_search\' AFTER license_status',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
