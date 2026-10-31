SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS amateur_player_ads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    slug VARCHAR(160) NOT NULL UNIQUE,
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NULL,
    primary_position ENUM('Kaleci','Defans','Orta Saha','Kanat','Forvet') NOT NULL,
    secondary_position ENUM('Kaleci','Defans','Orta Saha','Kanat','Forvet') NULL,
    birth_year SMALLINT UNSIGNED NOT NULL,
    height_cm SMALLINT UNSIGNED NULL,
    dominant_foot ENUM('Sağ','Sol','İki') NULL,
    license_status ENUM('active','free','unlicensed','unknown') NOT NULL DEFAULT 'unknown',
    desired_levels JSON NOT NULL,
    last_club VARCHAR(140) NULL,
    football_story VARCHAR(1200) NULL,
    preferred_cities JSON NULL,
    availability_text VARCHAR(500) NULL,
    contact_preference ENUM('takimbul','whatsapp','both') NOT NULL DEFAULT 'takimbul',
    whatsapp_public TINYINT(1) NOT NULL DEFAULT 0,
    video_url VARCHAR(500) NULL,
    status ENUM('draft','pending','active','paused','found_team','rejected','expired') NOT NULL DEFAULT 'draft',
    moderation_note VARCHAR(700) NULL,
    featured_until DATETIME NULL,
    expires_at DATETIME NULL,
    published_at DATETIME NULL,
    found_team_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_amateur_ad_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_amateur_ads_public (status, province, district, primary_position, published_at),
    INDEX idx_amateur_ads_license (status, license_status, published_at),
    INDEX idx_amateur_ads_expiry (status, expires_at),
    INDEX idx_amateur_ads_featured (featured_until, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS amateur_player_career_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad_id BIGINT UNSIGNED NOT NULL,
    season VARCHAR(20) NOT NULL,
    club_name VARCHAR(140) NOT NULL,
    level ENUM('BAL','Süper Amatör','1. Amatör','2. Amatör','U19','U18','U17','Akademi','Yerel Lig','Diğer') NULL,
    position ENUM('Kaleci','Defans','Orta Saha','Kanat','Forvet') NULL,
    jersey_number TINYINT UNSIGNED NULL,
    matches SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    starts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    goals SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    assists SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    yellow_cards SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    red_cards SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    note VARCHAR(300) NULL,
    verification_status ENUM('self_declared') NOT NULL DEFAULT 'self_declared',
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_amateur_career_ad FOREIGN KEY (ad_id) REFERENCES amateur_player_ads(id) ON DELETE CASCADE,
    INDEX idx_amateur_career_ad_sort (ad_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS amateur_player_ad_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    actor_type ENUM('system','user','admin') NOT NULL DEFAULT 'system',
    actor_id BIGINT UNSIGNED NULL,
    meta_text VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_amateur_event_ad FOREIGN KEY (ad_id) REFERENCES amateur_player_ads(id) ON DELETE CASCADE,
    INDEX idx_amateur_event_ad (ad_id, created_at),
    INDEX idx_amateur_event_type (event_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS amateur_player_favorites (
    user_id BIGINT UNSIGNED NOT NULL,
    ad_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, ad_id),
    CONSTRAINT fk_amateur_favorite_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_amateur_favorite_ad FOREIGN KEY (ad_id) REFERENCES amateur_player_ads(id) ON DELETE CASCADE,
    INDEX idx_amateur_favorite_ad (ad_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE ads MODIFY placement ENUM('home_top','welcome_takeover','home_feed','home_sidebar','players_feed','opponents_feed','match_inline','team_inline','career_inline','mobile_feed','amateur_list_top','amateur_list_inline','amateur_detail_inline') NOT NULL;
ALTER TABLE reports MODIFY target_type ENUM('user','team','post','player_post','opponent_post','match','amateur_ad') NOT NULL;
