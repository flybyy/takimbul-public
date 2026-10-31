-- TakımBul V10.8 Football Identities - additive/idempotent
SET NAMES utf8mb4;

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
