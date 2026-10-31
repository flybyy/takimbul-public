-- TakımBul v8 Ad Manager migration. Run once when upgrading v7 -> v8.
SET NAMES utf8mb4;

ALTER TABLE ads
    MODIFY COLUMN placement ENUM('feed','city','opponent','players','match','home_feed','home_sidebar','players_feed','opponents_feed','match_inline','team_inline','career_inline','mobile_feed') NOT NULL,
    MODIFY COLUMN target_url VARCHAR(500) NULL,
    ADD COLUMN type ENUM('manual','external_code') NOT NULL DEFAULT 'manual' AFTER id,
    ADD COLUMN image_url VARCHAR(500) NULL AFTER body,
    ADD COLUMN external_code MEDIUMTEXT NULL AFTER target_url,
    ADD COLUMN device_target ENUM('all','desktop','mobile') NOT NULL DEFAULT 'all' AFTER placement,
    ADD COLUMN impressions BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER status,
    ADD COLUMN clicks BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER impressions,
    ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

UPDATE ads SET placement='home_feed' WHERE placement IN ('feed','city');
UPDATE ads SET placement='opponents_feed' WHERE placement='opponent';
UPDATE ads SET placement='players_feed' WHERE placement='players';
UPDATE ads SET placement='match_inline' WHERE placement='match';

ALTER TABLE ads
    MODIFY COLUMN placement ENUM('home_feed','home_sidebar','players_feed','opponents_feed','match_inline','team_inline','career_inline','mobile_feed') NOT NULL,
    ADD INDEX idx_ads_delivery (status, placement, device_target, starts_at, ends_at),
    ADD INDEX idx_ads_location (province, district),
    ADD INDEX idx_ads_rotation (placement, status, impressions);
