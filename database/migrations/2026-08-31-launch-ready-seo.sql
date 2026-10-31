SET NAMES utf8mb4;

ALTER TABLE users
    ADD COLUMN email_verified_at DATETIME NULL AFTER status;

UPDATE users SET email_verified_at=NOW() WHERE email_verified_at IS NULL;

ALTER TABLE reports
    MODIFY target_type ENUM('user','team','post','player_post','opponent_post','match') NOT NULL;

CREATE TABLE IF NOT EXISTS user_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('email_verification','password_reset') NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_token_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_token_lookup (type, token_hash, expires_at),
    INDEX idx_user_token_user (user_id, type, used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auth_rate_limits (
    key_hash CHAR(64) PRIMARY KEY,
    hits INT UNSIGNED NOT NULL DEFAULT 1,
    window_started_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_auth_rate_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
