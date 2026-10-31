SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL UNIQUE,
    privacy_accepted_at DATETIME NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    username VARCHAR(60) NULL UNIQUE,
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NOT NULL,
    role ENUM('player') NOT NULL DEFAULT 'player',
    status ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
    email_verified_at DATETIME NULL,
    profile_verified_at DATETIME NULL,
    profile_verified_by_admin_id BIGINT UNSIGNED NULL,
    last_seen_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_location (province, district),
    INDEX idx_users_status (status),
    INDEX idx_users_last_seen (last_seen_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin','authorized','writer') NOT NULL DEFAULT 'writer',
    public_slug VARCHAR(150) NULL UNIQUE,
    public_title VARCHAR(100) NULL,
    public_bio VARCHAR(500) NULL,
    avatar_path VARCHAR(255) NULL,
    instagram_url VARCHAR(255) NULL,
    x_url VARCHAR(255) NULL,
    public_profile_enabled TINYINT(1) NOT NULL DEFAULT 1,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_admins_status (status),
    INDEX idx_admins_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS player_profiles (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    primary_position ENUM('Kaleci','Defans','Orta Saha','Kanat','Forvet') NULL,
    secondary_position ENUM('Kaleci','Defans','Orta Saha','Kanat','Forvet') NULL,
    dominant_foot ENUM('Sağ','Sol','İki') NULL,
    birth_year SMALLINT UNSIGNED NULL,
    show_age TINYINT(1) NOT NULL DEFAULT 1,
    height_cm SMALLINT UNSIGNED NULL,
    bio VARCHAR(500) NULL,
    avatar_path VARCHAR(255) NULL,
    visibility ENUM('public','members','private') NOT NULL DEFAULT 'public',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_profile_position (primary_position),
    INDEX idx_profile_visibility (visibility)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS teams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    logo_path VARCHAR(255) NULL,
    short_name VARCHAR(24) NULL,
    primary_color CHAR(7) NULL,
    secondary_color CHAR(7) NULL,
    home_venue VARCHAR(160) NULL,
    instagram_url VARCHAR(255) NULL,
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NOT NULL,
    founded_year SMALLINT UNSIGNED NULL,
    description VARCHAR(700) NULL,
    captain_user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_team_captain FOREIGN KEY (captain_user_id) REFERENCES users(id),
    INDEX idx_teams_location (province, district),
    INDEX idx_teams_captain (captain_user_id),
    INDEX idx_teams_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_members (
    team_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('captain','vice_captain','player') NOT NULL DEFAULT 'player',
    status ENUM('active','invited','requested','left') NOT NULL DEFAULT 'active',
    jersey_number TINYINT UNSIGNED NULL,
    team_position ENUM('Kaleci','Defans','Orta Saha','Kanat','Forvet') NULL,
    squad_status ENUM('starter','substitute','reserve') NOT NULL DEFAULT 'reserve',
    active_jersey_number TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN status='active' THEN jersey_number ELSE NULL END) STORED,
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (team_id, user_id),
    CONSTRAINT fk_member_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_member_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_member_user_status (user_id, status),
    UNIQUE KEY uq_team_jersey (team_id, active_jersey_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS player_wanted_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    match_at DATETIME NOT NULL,
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NOT NULL,
    venue VARCHAR(160) NULL,
    position ENUM('Kaleci','Defans','Orta Saha','Kanat','Forvet') NOT NULL,
    players_needed TINYINT UNSIGNED NOT NULL DEFAULT 1,
    level ENUM('Eğlence','Orta','Rekabetçi') NOT NULL,
    fee_per_person DECIMAL(10,2) NULL,
    description VARCHAR(500) NULL,
    status ENUM('open','closed','cancelled') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wanted_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_wanted_creator FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_wanted_search (status, province, district, match_at),
    INDEX idx_wanted_position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS player_wanted_applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','accepted','rejected','withdrawn') NOT NULL DEFAULT 'pending',
    message VARCHAR(300) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_application (post_id, user_id),
    CONSTRAINT fk_application_post FOREIGN KEY (post_id) REFERENCES player_wanted_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_application_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opponent_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    match_at DATETIME NOT NULL,
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NOT NULL,
    venue VARCHAR(160) NULL,
    level ENUM('Eğlence','Orta','Rekabetçi') NOT NULL,
    match_format ENUM('5v5','6v6','7v7','8v8') NOT NULL,
    description VARCHAR(500) NULL,
    status ENUM('open','matched','closed','cancelled') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_opponent_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_opponent_creator FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_opponent_search (status, province, district, match_at),
    INDEX idx_opponent_format (match_format)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS match_offers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opponent_post_id BIGINT UNSIGNED NOT NULL,
    offered_by_team_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','accepted','rejected','withdrawn') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_match_offer (opponent_post_id, offered_by_team_id),
    CONSTRAINT fk_offer_post FOREIGN KEY (opponent_post_id) REFERENCES opponent_posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_offer_team FOREIGN KEY (offered_by_team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    home_team_id BIGINT UNSIGNED NOT NULL,
    away_team_id BIGINT UNSIGNED NOT NULL,
    season_id BIGINT UNSIGNED NULL,
    match_at DATETIME NOT NULL,
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NOT NULL,
    venue VARCHAR(160) NULL,
    match_format ENUM('5v5','6v6','7v7','8v8') NOT NULL,
    status ENUM('confirmed','score_pending','verified','cancelled','disputed') NOT NULL DEFAULT 'confirmed',
    home_score TINYINT UNSIGNED NULL,
    away_score TINYINT UNSIGNED NULL,
    home_confirmed_at DATETIME NULL,
    away_confirmed_at DATETIME NULL,
    result_submitted_by_user_id BIGINT UNSIGNED NULL,
    result_submitted_at DATETIME NULL,
    disputed_by_user_id BIGINT UNSIGNED NULL,
    dispute_reason VARCHAR(500) NULL,
    home_ratings_completed_at DATETIME NULL,
    away_ratings_completed_at DATETIME NULL,
    ratings_finalized_at DATETIME NULL,
    source_opponent_post_id BIGINT UNSIGNED NULL,
    source_match_offer_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_match_home FOREIGN KEY (home_team_id) REFERENCES teams(id),
    CONSTRAINT fk_match_away FOREIGN KEY (away_team_id) REFERENCES teams(id),
    CONSTRAINT fk_match_season FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE SET NULL,
    CONSTRAINT fk_match_result_submitter FOREIGN KEY (result_submitted_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_match_disputed_by FOREIGN KEY (disputed_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_match_source_post FOREIGN KEY (source_opponent_post_id) REFERENCES opponent_posts(id) ON DELETE SET NULL,
    CONSTRAINT fk_match_source_offer FOREIGN KEY (source_match_offer_id) REFERENCES match_offers(id) ON DELETE SET NULL,
    UNIQUE KEY uq_match_source_offer (source_match_offer_id),
    INDEX idx_match_source_post (source_opponent_post_id),
    INDEX idx_match_date (match_at),
    INDEX idx_match_season (season_id, status, match_at),
    INDEX idx_match_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS match_players (
    match_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (match_id, user_id),
    CONSTRAINT fk_match_player_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_player_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_player_team FOREIGN KEY (team_id) REFERENCES teams(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS match_lineups (
    match_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    role ENUM('starter','substitute') NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 1,
    selected_by_user_id BIGINT UNSIGNED NOT NULL,
    selected_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (match_id, user_id),
    CONSTRAINT fk_lineup_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_lineup_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_lineup_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_lineup_selector FOREIGN KEY (selected_by_user_id) REFERENCES users(id),
    INDEX idx_lineup_team_role (match_id, team_id, role, sort_order)
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
    PRIMARY KEY (match_id, user_id),
    CONSTRAINT fk_rating_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_rating_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_rating_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_rating_rater FOREIGN KEY (rated_by_user_id) REFERENCES users(id),
    INDEX idx_rating_finalized (user_id, is_finalized, final_rating),
    INDEX idx_rating_mvp (match_id, is_mvp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS player_match_stats (
    match_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    goals TINYINT UNSIGNED NOT NULL DEFAULT 0,
    assists TINYINT UNSIGNED NOT NULL DEFAULT 0,
    verified TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (match_id, user_id),
    CONSTRAINT fk_stats_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_stats_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_stats_verified (user_id, verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS career_highlights (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type ENUM('achievement','video') NOT NULL,
    title VARCHAR(160) NOT NULL,
    url VARCHAR(500) NULL,
    description VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_highlight_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS scout_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    account_type ENUM('scout','club') NOT NULL,
    organization_name VARCHAR(180) NOT NULL,
    website VARCHAR(255) NULL,
    verification_note VARCHAR(700) NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by_admin_id BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_scout_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_scout_reviewer_admin FOREIGN KEY (reviewed_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_scout_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scout_watchlists (
    scout_account_id BIGINT UNSIGNED NOT NULL,
    player_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (scout_account_id, player_user_id),
    CONSTRAINT fk_watchlist_scout FOREIGN KEY (scout_account_id) REFERENCES scout_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_watchlist_player FOREIGN KEY (player_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scout_invites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    scout_account_id BIGINT UNSIGNED NOT NULL,
    player_user_id BIGINT UNSIGNED NOT NULL,
    invite_type ENUM('contact','trial') NOT NULL,
    message VARCHAR(700) NOT NULL,
    status ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_invite_scout FOREIGN KEY (scout_account_id) REFERENCES scout_accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_invite_player FOREIGN KEY (player_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_invite_player_status (player_user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_user_id BIGINT UNSIGNED NOT NULL,
    target_type ENUM('user','team','post','player_post','opponent_post','match','amateur_ad') NOT NULL,
    target_id BIGINT UNSIGNED NOT NULL,
    reason ENUM('fake_profile','abuse','no_show','misleading','fake_stats','other') NOT NULL,
    details VARCHAR(700) NULL,
    status ENUM('open','reviewing','resolved','dismissed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reporter FOREIGN KEY (reporter_user_id) REFERENCES users(id),
    INDEX idx_report_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(80) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body VARCHAR(500) NULL,
    link VARCHAR(255) NULL,
    read_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notification_unread (user_id, read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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


CREATE TABLE IF NOT EXISTS player_availabilities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NOT NULL,
    position ENUM('Kaleci','Defans','Orta Saha','Kanat','Forvet') NOT NULL,
    radius_km SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    note VARCHAR(280) NULL,
    status ENUM('active','cancelled','expired','matched') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_availability_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_availability_search (status, province, district, starts_at, ends_at),
    INDEX idx_availability_user (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS availability_invites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    availability_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    invited_by_user_id BIGINT UNSIGNED NOT NULL,
    message VARCHAR(300) NULL,
    status ENUM('pending','accepted','rejected','withdrawn') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    CONSTRAINT fk_availability_invite_availability FOREIGN KEY (availability_id) REFERENCES player_availabilities(id) ON DELETE CASCADE,
    CONSTRAINT fk_availability_invite_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_availability_invite_sender FOREIGN KEY (invited_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_availability_team (availability_id, team_id),
    INDEX idx_availability_invite_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS match_attendance (
    match_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    team_id BIGINT UNSIGNED NOT NULL,
    response ENUM('yes','maybe','no') NOT NULL,
    responded_at DATETIME NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (match_id, user_id),
    CONSTRAINT fk_attendance_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    INDEX idx_attendance_team (match_id, team_id, response)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS player_videos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL,
    url VARCHAR(500) NOT NULL,
    provider ENUM('youtube','instagram','tiktok') NOT NULL,
    status ENUM('active','hidden') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_player_video_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_player_video_user (user_id, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_user_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NOT NULL,
    note VARCHAR(1000) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_user_note_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_admin_user_note_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    INDEX idx_admin_user_notes_user (user_id, created_at)
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
    CONSTRAINT fk_team_search_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_team_search_location (status, province, district),
    INDEX idx_team_search_position (primary_position)
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
    UNIQUE KEY uq_team_invite_pending (active_key),
    CONSTRAINT fk_team_invite_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_invite_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_invite_actor FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_team_invites_user_status (user_id, status, created_at),
    INDEX idx_team_invites_team_status (team_id, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(80) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_by_admin_id BIGINT UNSIGNED NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_site_setting_admin FOREIGN KEY (updated_by_admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_settings (setting_key,setting_value) VALUES
('announcement_enabled','0'),('announcement_text',''),('support_email','destek@takimbul.com.tr'),('home_feature_availability','1'),('home_feature_rival_match','1'),('require_email_verification','0'),('maintenance_message',''),('welcome_ad_enabled','1')
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);


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
    profile_intent ENUM('team_search','showcase','both') NOT NULL DEFAULT 'team_search',
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

CREATE TABLE IF NOT EXISTS ads (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('manual','external_code') NOT NULL DEFAULT 'manual',
    advertiser_name VARCHAR(180) NOT NULL,
    title VARCHAR(180) NOT NULL,
    body VARCHAR(400) NULL,
    image_url VARCHAR(500) NULL,
    target_url VARCHAR(500) NULL,
    external_code MEDIUMTEXT NULL,
    placement ENUM('home_top','welcome_takeover','home_feed','home_sidebar','players_feed','opponents_feed','match_inline','team_inline','career_inline','mobile_feed','amateur_list_top','amateur_list_inline','amateur_detail_inline') NOT NULL,
    device_target ENUM('all','desktop','mobile') NOT NULL DEFAULT 'all',
    province VARCHAR(80) NULL,
    district VARCHAR(80) NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('draft','active','paused','ended') NOT NULL DEFAULT 'draft',
    impressions BIGINT UNSIGNED NOT NULL DEFAULT 0,
    clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ads_delivery (status, placement, device_target, starts_at, ends_at),
    INDEX idx_ads_location (province, district),
    INDEX idx_ads_rotation (placement, status, impressions)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS news_sponsors (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL,
    logo_url VARCHAR(500) NULL,
    website_url VARCHAR(500) NULL,
    description VARCHAR(500) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by_admin_id BIGINT UNSIGNED NULL,
    updated_by_admin_id BIGINT UNSIGNED NULL,
    sponsor_id BIGINT UNSIGNED NULL,
    sponsor_cta_text VARCHAR(80) NULL,
    sponsor_cta_url VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_news_sponsors_slug (slug),
    KEY idx_news_sponsors_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_admin_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(60) NOT NULL,
    target_id BIGINT UNSIGNED NULL,
    metadata_json LONGTEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_admin_audit_actor (actor_admin_id, created_at),
    KEY idx_admin_audit_target (target_type, target_id, created_at),
    KEY idx_admin_audit_action (action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS news (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(190) NOT NULL,
    summary VARCHAR(500) NULL,
    body LONGTEXT NOT NULL,
    category VARCHAR(60) NOT NULL DEFAULT 'TakımBul',
    cover_image_url VARCHAR(500) NULL,
    seo_title VARCHAR(180) NULL,
    seo_description VARCHAR(300) NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    view_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_by_admin_id BIGINT UNSIGNED NULL,
    updated_by_admin_id BIGINT UNSIGNED NULL,
    sponsor_id BIGINT UNSIGNED NULL,
    sponsor_cta_text VARCHAR(80) NULL,
    sponsor_cta_url VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_news_slug (slug),
    KEY idx_news_public (status, published_at),
    KEY idx_news_featured (is_featured, status, published_at),
    KEY idx_news_category (category, status, published_at),
    KEY idx_news_sponsor (sponsor_id, status, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- V10.8 FOOTBALL IDENTITIES SCHEMA
-- TakımBul V10.8 Football Identities - additive/idempotent

CREATE TABLE IF NOT EXISTS user_football_identities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    identity_type ENUM('recreational_player','amateur_player','referee','coach','club_official') NOT NULL,
    status ENUM('draft','pending','active','suspended') NOT NULL DEFAULT 'draft',
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_football_identity (user_id, identity_type),
    KEY idx_football_identity_type_status (identity_type, status),
    CONSTRAINT fk_football_identity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referee_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    slug VARCHAR(170) NOT NULL UNIQUE,
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NOT NULL,
    classification VARCHAR(100) NULL,
    started_year SMALLINT UNSIGNED NULL,
    license_name VARCHAR(160) NULL,
    license_status ENUM('unverified','verified','rejected') NOT NULL DEFAULT 'unverified',
    license_verified_by_admin_id BIGINT UNSIGNED NULL,
    license_verified_at DATETIME NULL,
    availability_status ENUM('showcase','available','unavailable') NOT NULL DEFAULT 'showcase',
    fee_visibility TINYINT(1) NOT NULL DEFAULT 0,
    fee_min DECIMAL(10,2) NULL,
    fee_max DECIMAL(10,2) NULL,
    competitions_text VARCHAR(700) NULL,
    bio VARCHAR(1200) NULL,
    matches_count INT UNSIGNED NOT NULL DEFAULT 0,
    yellow_cards_count INT UNSIGNED NOT NULL DEFAULT 0,
    red_cards_count INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('draft','pending','active','suspended','rejected') NOT NULL DEFAULT 'draft',
    moderation_note VARCHAR(500) NULL,
    published_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_referee_public (status, province, district, availability_status),
    KEY idx_referee_license (license_status, classification),
    CONSTRAINT fk_referee_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coach_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    slug VARCHAR(170) NOT NULL UNIQUE,
    public_title VARCHAR(100) NOT NULL DEFAULT 'Teknik Direktör',
    province VARCHAR(80) NOT NULL,
    district VARCHAR(80) NOT NULL,
    license_level VARCHAR(120) NULL,
    license_status ENUM('unverified','verified','rejected') NOT NULL DEFAULT 'unverified',
    license_verified_by_admin_id BIGINT UNSIGNED NULL,
    license_verified_at DATETIME NULL,
    intent ENUM('showcase','looking_for_team','open_to_offers') NOT NULL DEFAULT 'showcase',
    specialties_json LONGTEXT NULL,
    formations_json LONGTEXT NULL,
    bio VARCHAR(1600) NULL,
    matches_count INT UNSIGNED NOT NULL DEFAULT 0,
    wins_count INT UNSIGNED NOT NULL DEFAULT 0,
    draws_count INT UNSIGNED NOT NULL DEFAULT 0,
    losses_count INT UNSIGNED NOT NULL DEFAULT 0,
    achievements_text VARCHAR(1200) NULL,
    status ENUM('draft','pending','active','suspended','rejected') NOT NULL DEFAULT 'draft',
    moderation_note VARCHAR(500) NULL,
    published_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_coach_public (status, province, district, intent),
    KEY idx_coach_license (license_status, public_title),
    CONSTRAINT fk_coach_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coach_career_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coach_profile_id BIGINT UNSIGNED NOT NULL,
    season VARCHAR(30) NOT NULL,
    team_id BIGINT UNSIGNED NULL,
    team_name VARCHAR(160) NOT NULL,
    role VARCHAR(100) NOT NULL,
    competition VARCHAR(120) NULL,
    matches INT UNSIGNED NOT NULL DEFAULT 0,
    wins INT UNSIGNED NOT NULL DEFAULT 0,
    draws INT UNSIGNED NOT NULL DEFAULT 0,
    losses INT UNSIGNED NOT NULL DEFAULT 0,
    notes VARCHAR(500) NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_coach_career_profile (coach_profile_id, sort_order),
    CONSTRAINT fk_coach_career_profile FOREIGN KEY (coach_profile_id) REFERENCES coach_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_staff_memberships (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    coach_profile_id BIGINT UNSIGNED NULL,
    staff_role ENUM('head_coach','assistant_coach','goalkeeper_coach','fitness_coach','analyst','other') NOT NULL DEFAULT 'other',
    status ENUM('invited','active','declined','left') NOT NULL DEFAULT 'invited',
    joined_at DATETIME NULL,
    left_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_team_staff_identity (team_id, user_id, staff_role, status),
    KEY idx_team_staff_team_status (team_id, status),
    KEY idx_team_staff_user_status (user_id, status),
    CONSTRAINT fk_team_staff_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_staff_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_staff_coach FOREIGN KEY (coach_profile_id) REFERENCES coach_profiles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referee_match_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    referee_profile_id BIGINT UNSIGNED NOT NULL,
    invited_by_user_id BIGINT UNSIGNED NOT NULL,
    status ENUM('invited','accepted','declined','completed','cancelled') NOT NULL DEFAULT 'invited',
    message VARCHAR(500) NULL,
    responded_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_referee_match (match_id, referee_profile_id),
    KEY idx_referee_assignment_status (referee_profile_id, status),
    CONSTRAINT fk_ref_assignment_match FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    CONSTRAINT fk_ref_assignment_profile FOREIGN KEY (referee_profile_id) REFERENCES referee_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_ref_assignment_inviter FOREIGN KEY (invited_by_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO user_football_identities (user_id, identity_type, status, is_primary, completed_at)
SELECT a.user_id, 'amateur_player', CASE WHEN a.status='active' THEN 'active' WHEN a.status='pending' THEN 'pending' ELSE 'draft' END, 0,
       CASE WHEN a.status IN ('active','pending') THEN COALESCE(a.updated_at,NOW()) ELSE NULL END
FROM amateur_player_ads a;

INSERT IGNORE INTO user_football_identities (user_id, identity_type, status, is_primary, completed_at)
SELECT p.user_id, 'recreational_player', 'active',
       CASE WHEN NOT EXISTS (SELECT 1 FROM amateur_player_ads a WHERE a.user_id=p.user_id) THEN 1 ELSE 0 END,
       p.updated_at
FROM player_profiles p;


-- Default administration identity lives only in the isolated admins table.
-- Password is stored only as a one-way password_hash.
INSERT INTO admins (username,email,password_hash,display_name,role,status)
VALUES ('veribayt','admin@takimbul.com.tr','!','Veribayt Yönetim','super_admin','active')
ON DUPLICATE KEY UPDATE email=VALUES(email),password_hash=VALUES(password_hash),display_name=VALUES(display_name),status='active';

SET FOREIGN_KEY_CHECKS = 1;
