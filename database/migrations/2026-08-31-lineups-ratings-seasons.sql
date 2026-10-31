SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS seasons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(40) NOT NULL,
    slug VARCHAR(40) NOT NULL UNIQUE,
    starts_at DATE NOT NULL,
    ends_at DATE NOT NULL,
    status ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_season_status_dates (status, starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO seasons (name,slug,starts_at,ends_at,status)
VALUES ('2026/27 Sezonu','2026-27','2026-07-01','2027-06-30','active')
ON DUPLICATE KEY UPDATE name=VALUES(name),starts_at=VALUES(starts_at),ends_at=VALUES(ends_at),status=VALUES(status);

ALTER TABLE matches
    ADD COLUMN season_id BIGINT UNSIGNED NULL AFTER away_team_id,
    ADD COLUMN home_ratings_completed_at DATETIME NULL AFTER dispute_reason,
    ADD COLUMN away_ratings_completed_at DATETIME NULL AFTER home_ratings_completed_at,
    ADD COLUMN ratings_finalized_at DATETIME NULL AFTER away_ratings_completed_at,
    ADD INDEX idx_match_season (season_id,status,match_at),
    ADD CONSTRAINT fk_match_season FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE SET NULL;

UPDATE matches SET season_id=(SELECT id FROM seasons WHERE slug='2026-27' LIMIT 1) WHERE season_id IS NULL;

CREATE TABLE IF NOT EXISTS match_lineups (
    match_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    role ENUM('starter','substitute') NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 1,
    selected_by_user_id BIGINT UNSIGNED NOT NULL,
    selected_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (match_id,user_id),
    CONSTRAINT fk_lineup_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_lineup_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_lineup_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_lineup_selector FOREIGN KEY (selected_by_user_id) REFERENCES users(id),
    INDEX idx_lineup_team_role (match_id,team_id,role,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS match_player_ratings (
    match_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    captain_score DECIMAL(3,1) NOT NULL,
    contribution_score DECIMAL(3,1) NOT NULL,
    final_rating DECIMAL(3,1) NOT NULL,
    is_mvp TINYINT(1) NOT NULL DEFAULT 0,
    is_finalized TINYINT(1) NOT NULL DEFAULT 0,
    rated_by_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (match_id,user_id),
    CONSTRAINT fk_rating_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_rating_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_rating_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_rating_rater FOREIGN KEY (rated_by_user_id) REFERENCES users(id),
    INDEX idx_rating_finalized (user_id,is_finalized,final_rating),
    INDEX idx_rating_mvp (match_id,is_mvp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
