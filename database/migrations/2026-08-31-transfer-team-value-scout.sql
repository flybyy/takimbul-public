SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE team_members
    ADD COLUMN jersey_number TINYINT UNSIGNED NULL AFTER status,
    ADD COLUMN active_jersey_number TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN status='active' THEN jersey_number ELSE NULL END) STORED AFTER jersey_number,
    ADD UNIQUE KEY uq_team_jersey (team_id, active_jersey_number);

CREATE TABLE IF NOT EXISTS transfer_offers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    player_user_id BIGINT UNSIGNED NOT NULL,
    offered_by_user_id BIGINT UNSIGNED NOT NULL,
    proposed_position ENUM('Kaleci','Defans','Orta Saha','Kanat','Forvet') NULL,
    proposed_jersey_number TINYINT UNSIGNED NULL,
    message VARCHAR(500) NULL,
    status ENUM('pending','accepted','rejected','withdrawn') NOT NULL DEFAULT 'pending',
    responded_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transfer_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_transfer_player FOREIGN KEY (player_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transfer_sender FOREIGN KEY (offered_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_transfer_player_status (player_user_id,status,created_at),
    INDEX idx_transfer_team_status (team_id,status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- scout_watchlists tablosu v1'den beri bulunduğu için veri korunur; yoksa sıfırdan kurulum şeması oluşturur.
CREATE TABLE IF NOT EXISTS scout_watchlists (
    scout_account_id BIGINT UNSIGNED NOT NULL,
    player_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (scout_account_id, player_user_id),
    CONSTRAINT fk_watchlist_scout FOREIGN KEY (scout_account_id) REFERENCES scout_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_watchlist_player FOREIGN KEY (player_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
