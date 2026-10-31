-- TakımBul v2 -> v3 match/social upgrade
-- Run once on an existing v2 database. Fresh installs should import database/schema.sql instead.

SET NAMES utf8mb4;

ALTER TABLE matches
    ADD COLUMN source_opponent_post_id BIGINT UNSIGNED NULL AFTER away_confirmed_at,
    ADD COLUMN source_match_offer_id BIGINT UNSIGNED NULL AFTER source_opponent_post_id,
    ADD UNIQUE KEY uq_match_source_offer (source_match_offer_id),
    ADD INDEX idx_match_source_post (source_opponent_post_id),
    ADD CONSTRAINT fk_match_source_post FOREIGN KEY (source_opponent_post_id) REFERENCES opponent_posts(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_match_source_offer FOREIGN KEY (source_match_offer_id) REFERENCES match_offers(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS direct_threads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_low_id BIGINT UNSIGNED NOT NULL,
    user_high_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_direct_pair (user_low_id, user_high_id),
    CONSTRAINT fk_thread_low_user FOREIGN KEY (user_low_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_thread_high_user FOREIGN KEY (user_high_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_thread_low_updated (user_low_id, updated_at),
    INDEX idx_thread_high_updated (user_high_id, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS direct_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id BIGINT UNSIGNED NOT NULL,
    sender_user_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    read_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_direct_message_thread FOREIGN KEY (thread_id) REFERENCES direct_threads(id) ON DELETE CASCADE,
    CONSTRAINT fk_direct_message_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_direct_messages_thread (thread_id, id),
    INDEX idx_direct_messages_unread (thread_id, read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
