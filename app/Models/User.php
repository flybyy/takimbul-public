<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\TurkeyLocation;
use PDO;

final class User
{
    public static function validateRegistration(array $data): array
    {
        $errors = [];
        $name = trim((string) ($data['display_name'] ?? ''));
        $username = strtolower(trim((string) ($data['username'] ?? '')));
        $phone = self::normalizePhone((string) ($data['phone'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $passwordConfirmation = (string) ($data['password_confirmation'] ?? '');
        $province = trim((string) ($data['province'] ?? ''));
        $district = trim((string) ($data['district'] ?? ''));
        $identityTypes = FootballIdentity::normalizeTypes($data['identity_types'] ?? []);

        if (strlen($name) < 2 || strlen($name) > 100) {
            $errors['display_name'] = 'Ad soyad / görünen ad 2-100 karakter olmalı.';
        }
        if (($usernameError = self::validateUsername($username)) !== null) {
            $errors['username'] = $usernameError;
        } elseif (Admin::identifierReserved($username)) {
            $errors['username'] = 'Bu kullanıcı adı yönetim hesabına ayrılmış.';
        }
        if ($phone === null) {
            $errors['phone'] = 'Geçerli bir Türkiye cep telefonu numarası gir. Örn: 05XX XXX XX XX.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Geçerli bir e-posta adresi gir.';
        } elseif (Admin::identifierReserved($email)) {
            $errors['email'] = 'Bu e-posta adresi yönetim hesabına ayrılmış.';
        }
        if (($passwordError = self::validatePassword($password)) !== null) {
            $errors['password'] = $passwordError;
        }
        if ($passwordConfirmation === '' || !hash_equals($password, $passwordConfirmation)) {
            $errors['password_confirmation'] = 'Şifreler birbiriyle aynı olmalı.';
        }
        if ($province === '' || TurkeyLocation::canonicalProvince($province) === null) {
            $errors['province'] = 'Geçerli bir il seç.';
        } elseif ($district === '' || !TurkeyLocation::isValidPair($province, $district)) {
            $errors['district'] = 'Seçtiğin ilçenin ile ait olduğundan emin ol.';
        }
        if ($identityTypes === []) {
            $errors['identity_types'] = 'En az bir futbol kimliği seç.';
        }
        if (($data['privacy_consent'] ?? '') !== '1') {
            $errors['privacy_consent'] = 'Üyelik için gizlilik politikası ve kullanım koşullarını kabul etmelisin.';
        }

        return $errors;
    }

    public static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', trim($phone)) ?? '';
        if (str_starts_with($digits, '90') && strlen($digits) === 12) $digits = substr($digits, 2);
        if (str_starts_with($digits, '0') && strlen($digits) === 11) $digits = substr($digits, 1);
        if (!preg_match('/^5\d{9}$/', $digits)) return null;
        return '+90'.$digits;
    }

    public static function phoneDisplay(?string $phone): string
    {
        $normalized=self::normalizePhone((string)$phone);
        if($normalized===null) return '';
        $d=substr($normalized,3);
        return '+90 '.substr($d,0,3).' '.substr($d,3,3).' '.substr($d,6,2).' '.substr($d,8,2);
    }

    public static function validateUsername(string $username): ?string
    {
        $username = trim($username);
        if (strlen($username) < 3 || strlen($username) > 30) return 'Kullanıcı adı 3-30 karakter olmalı.';
        if (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9._]*[A-Za-z0-9])?$/', $username)) {
            return 'Kullanıcı adı harf/rakamla başlamalı ve bitmeli; yalnızca harf, rakam, nokta ve alt çizgi kullanılabilir.';
        }
        return null;
    }

    public static function validateProfileIdentity(array $data, int $userId): array
    {
        $errors = [];
        $name = trim((string)($data['display_name'] ?? ''));
        $username = trim((string)($data['username'] ?? ''));
        $province = trim((string)($data['province'] ?? ''));
        $district = trim((string)($data['district'] ?? ''));

        if (strlen($name) < 2 || strlen($name) > 100) $errors['display_name'] = 'Görünen ad 2-100 karakter olmalı.';
        if (($usernameError = self::validateUsername($username)) !== null) $errors['username'] = $usernameError;
        elseif (Admin::identifierReserved($username)) $errors['username'] = 'Bu kullanıcı adı yönetim hesabına ayrılmış.';
        if ($province === '' || TurkeyLocation::canonicalProvince($province) === null) $errors['province'] = 'Geçerli bir il seç.';
        elseif ($district === '' || !TurkeyLocation::isValidPair($province,$district)) $errors['district'] = 'Geçerli bir ilçe seç.';

        if (!isset($errors['username'])) {
            $stmt = Database::connection()->prepare('SELECT id FROM users WHERE LOWER(username)=LOWER(:username) AND id<>:id LIMIT 1');
            $stmt->execute(['username'=>$username,'id'=>$userId]);
            if ($stmt->fetchColumn()) $errors['username'] = 'Bu kullanıcı adı başka bir hesap tarafından kullanılıyor.';
        }
        return $errors;
    }

    public static function profileIdentity(int $userId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id,email,phone,display_name,username,province,district,role,status,email_verified_at,profile_verified_at,profile_verified_by_admin_id,privacy_accepted_at,last_seen_at,created_at FROM users WHERE id=:id LIMIT 1');
        $stmt->execute(['id'=>$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    public static function updateProfileIdentity(int $userId, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET display_name=:display_name,username=:username,province=:province,district=:district,updated_at=NOW() WHERE id=:id');
        $stmt->execute([
            'display_name'=>trim((string)$data['display_name']),
            'username'=>strtolower(trim((string)$data['username'])),
            'province'=>TurkeyLocation::canonicalProvince((string)$data['province']) ?? trim((string)$data['province']),
            'district'=>TurkeyLocation::canonicalDistrict((string)$data['province'],(string)$data['district']) ?? trim((string)$data['district']),
            'id'=>$userId,
        ]);
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $statement = $pdo->prepare(
            'INSERT INTO users (email, phone, password_hash, display_name, username, province, district, privacy_accepted_at) VALUES (:email, :phone, :password_hash, :display_name, :username, :province, :district, NOW())'
        );
        $statement->execute([
            'email' => strtolower(trim((string) $data['email'])),
            'phone' => self::normalizePhone((string)($data['phone'] ?? '')),
            'password_hash' => password_hash((string) $data['password'], PASSWORD_DEFAULT),
            'display_name' => trim((string) $data['display_name']),
            'username' => strtolower(trim((string)($data['username'] ?? ''))),
            'province' => TurkeyLocation::canonicalProvince((string)$data['province']) ?? trim((string)$data['province']),
            'district' => TurkeyLocation::canonicalDistrict((string)$data['province'],(string)$data['district']) ?? trim((string)$data['district']),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function validatePassword(string $password): ?string
    {
        if (strlen($password) < 10) return 'Şifre en az 10 karakter olmalı.';
        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) return 'Şifre en az bir harf ve bir rakam içermeli.';
        return null;
    }

    public static function markEmailVerified(int $userId): void
    {
        $stmt=Database::connection()->prepare('UPDATE users SET email_verified_at=COALESCE(email_verified_at,NOW()) WHERE id=:id');
        $stmt->execute(['id'=>$userId]);
    }

    public static function updatePassword(int $userId,string $password): void
    {
        $stmt=Database::connection()->prepare('UPDATE users SET password_hash=:hash WHERE id=:id');
        $stmt->execute(['hash'=>password_hash($password,PASSWORD_DEFAULT),'id'=>$userId]);
    }

    public static function setProfileVerified(int $userId,bool $verified,int $adminId): bool
    {
        if($userId<=0||$adminId<=0)return false;
        try{
            $sql=$verified?'UPDATE users SET profile_verified_at=COALESCE(profile_verified_at,NOW()),profile_verified_by_admin_id=:admin,updated_at=NOW() WHERE id=:id AND status<>"deleted"':'UPDATE users SET profile_verified_at=NULL,profile_verified_by_admin_id=NULL,updated_at=NOW() WHERE id=:id AND status<>"deleted"';
            $stmt=Database::connection()->prepare($sql);$stmt->execute(['admin'=>$adminId,'id'=>$userId]);
            return true;
        }catch(\Throwable $e){
            error_log('[TakimBul][verification] '.$e->getMessage());
            return false;
        }
    }

    public static function setStatus(int $userId,string $status): void
    {
        if (!in_array($status,['active','suspended'],true)) return;
        $stmt=Database::connection()->prepare('UPDATE users SET status=:status WHERE id=:id AND role<>"admin" AND status IN ("active","suspended")');
        $stmt->execute(['status'=>$status,'id'=>$userId]);
    }

    public static function findByIdentifier(string $identifier): ?array
    {
        $identifier = trim($identifier);
        $statement = Database::connection()->prepare(
            'SELECT id, email, phone, username, password_hash, display_name, province, district, role, status, email_verified_at, profile_verified_at FROM users WHERE LOWER(email) = LOWER(:identifier_email) OR LOWER(username) = LOWER(:identifier_username) LIMIT 1'
        );
        $statement->execute(['identifier_email'=>$identifier,'identifier_username'=>$identifier]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($user) ? $user : null;
    }

    public static function findByEmail(string $email): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, email, phone, username, password_hash, display_name, province, district, role, status, email_verified_at, profile_verified_at FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1'
        );
        $statement->execute(['email'=>trim($email)]);
        $user=$statement->fetch(PDO::FETCH_ASSOC);
        return is_array($user)?$user:null;
    }


    public static function touchActivity(int $userId): void
    {
        if ($userId <= 0) return;
        try {
            $stmt=Database::connection()->prepare('UPDATE users SET last_seen_at=NOW() WHERE id=:id AND status="active"');
            $stmt->execute(['id'=>$userId]);
        } catch (\Throwable) {}
    }

    public static function onlineCount(int $minutes=5): int
    {
        $minutes=max(1,min(60,$minutes));
        try {
            $sql='SELECT COUNT(*) FROM users WHERE status="active" AND last_seen_at>=DATE_SUB(NOW(),INTERVAL '.$minutes.' MINUTE)';
            return (int)Database::connection()->query($sql)->fetchColumn();
        } catch (\Throwable) { return 0; }
    }

    public static function adminStats(): array
    {
        $sql='SELECT
            (SELECT COUNT(*) FROM users WHERE status<>"deleted") AS total_users,
            (SELECT COUNT(*) FROM users WHERE status="active") AS active_users,
            (SELECT COUNT(*) FROM users WHERE status="suspended") AS suspended_users,
            (SELECT COUNT(*) FROM teams WHERE status="active") AS active_teams,
            (SELECT COUNT(*) FROM player_wanted_posts WHERE status="open") + (SELECT COUNT(*) FROM opponent_posts WHERE status="open") AS open_posts,
            (SELECT COUNT(*) FROM reports WHERE status IN ("open","reviewing")) AS open_reports,
            (SELECT COUNT(*) FROM scout_accounts WHERE status="pending") AS pending_scouts,
            (SELECT COUNT(*) FROM ads WHERE status="active" AND starts_at<=NOW() AND ends_at>=NOW()) AS active_ads,
            (SELECT COUNT(*) FROM users WHERE status="active" AND last_seen_at>=DATE_SUB(NOW(),INTERVAL 5 MINUTE)) AS online_users,
            (SELECT COUNT(*) FROM matches WHERE DATE(match_at)=CURDATE() AND status<>"cancelled") AS today_matches,
            (SELECT COUNT(*) FROM matches WHERE status="score_pending") AS pending_results,
            (SELECT COUNT(*) FROM player_availabilities WHERE status="active" AND ends_at>=NOW()) AS active_availabilities,
            (SELECT COUNT(*) FROM availability_invites WHERE status="pending") AS pending_availability_invites,
            (SELECT COUNT(*) FROM users WHERE status<>"deleted" AND DATE(created_at)=CURDATE()) AS today_users,
            (SELECT COUNT(*) FROM users WHERE status<>"deleted" AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)) AS users_7d,
            (SELECT COUNT(*) FROM direct_messages WHERE created_at>=CURDATE()) AS messages_today';
        $row=Database::connection()->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];
        $row['active_team_seekers']=self::optionalAdminCount('team_search_profiles','status="active"');
        $row['pending_team_invites']=self::optionalAdminCount('team_invites','status="pending"');
        return array_map('intval',$row);
    }

    private static function optionalAdminCount(string $table, string $where='1=1'): int
    {
        $allowed=['team_search_profiles','team_invites'];
        if(!in_array($table,$allowed,true)) return 0;
        try {
            return (int)Database::connection()->query('SELECT COUNT(*) FROM '.$table.' WHERE '.$where)->fetchColumn();
        } catch (\Throwable) {
            return 0;
        }
    }

    public static function recentForAdmin(int $limit=8): array
    {
        $limit=max(1,min(30,$limit));
        $sql='SELECT id,email,phone,display_name,username,province,district,role,status,email_verified_at,last_seen_at,created_at FROM users WHERE status<>"deleted" ORDER BY created_at DESC LIMIT '.$limit;
        return Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function adminFilteredSearch(array $filters=[], int $limit=100): array
    {
        $limit=max(1,min(200,$limit));$sql='SELECT id,email,phone,display_name,username,province,district,role,status,email_verified_at,last_seen_at,created_at FROM users WHERE status<>"deleted"';$params=[];
        $q=trim((string)($filters['q']??''));if($q!==''){$like='%'.$q.'%';$sql.=' AND (display_name LIKE :q1 OR email LIKE :q2 OR phone LIKE :q3 OR username LIKE :q4 OR province LIKE :q5 OR district LIKE :q6)';$params+=['q1'=>$like,'q2'=>$like,'q3'=>$like,'q4'=>$like,'q5'=>$like,'q6'=>$like];}
        $status=trim((string)($filters['status']??''));if(in_array($status,['active','suspended'],true)){$sql.=' AND status=:status';$params['status']=$status;}
        $province=trim((string)($filters['province']??''));if($province!==''){$sql.=' AND province=:province';$params['province']=$province;}
        $sql.=' ORDER BY created_at DESC LIMIT '.$limit;$stmt=Database::connection()->prepare($sql);$stmt->execute($params);return$stmt->fetchAll(PDO::FETCH_ASSOC)?:[];
    }

    public static function adminSearch(string $query='', int $limit=60): array
    {
        $limit=max(1,min(100,$limit));$query=trim($query);
        $sql='SELECT id,email,phone,display_name,username,province,district,role,status,email_verified_at,last_seen_at,created_at FROM users';$params=[];
        if($query!==''){
            $like='%'.$query.'%';
            $sql.=' WHERE display_name LIKE :q1 OR email LIKE :q2 OR username LIKE :q3 OR province LIKE :q4 OR district LIKE :q5';
            $params=['q1'=>$like,'q2'=>$like,'q3'=>$like,'q4'=>$like,'q5'=>$like];
        }
        $sql.=' ORDER BY created_at DESC LIMIT '.$limit;
        $stmt=Database::connection()->prepare($sql);$stmt->execute($params);return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findPublic(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT id, display_name, username, province, district, profile_verified_at, created_at FROM users WHERE id = :id AND status = "active" LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($user) ? $user : null;
    }

    public static function publicPlayers(array $filters = [], int $limit = 24): array
    {
        $sql = 'SELECT u.id, u.display_name, u.username, u.province, u.district, u.profile_verified_at, p.primary_position, p.secondary_position, p.dominant_foot, p.birth_year, p.height_cm, p.bio, p.avatar_path
                FROM users u
                INNER JOIN player_profiles p ON p.user_id = u.id
                INNER JOIN user_football_identities ufi ON ufi.user_id=u.id AND ufi.identity_type="recreational_player" AND ufi.status="active"
                WHERE u.status = "active" AND p.visibility = "public"';
        $params = [];

        if (!empty($filters['province'])) {
            $sql .= ' AND u.province = :province';
            $params['province'] = trim((string) $filters['province']);
        }
        if (!empty($filters['district'])) {
            $sql .= ' AND u.district = :district';
            $params['district'] = trim((string) $filters['district']);
        }
        if (!empty($filters['position'])) {
            $sql .= ' AND p.primary_position = :position';
            $params['position'] = trim((string) $filters['position']);
        }

        $sql .= ' ORDER BY u.created_at DESC LIMIT ' . max(1, min(100, $limit));
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function accountDetails(int $userId): ?array
    {
        $stmt=Database::connection()->prepare('SELECT u.id,u.email,u.phone,u.display_name,u.username,u.province,u.district,u.status,u.email_verified_at,u.profile_verified_at,u.profile_verified_by_admin_id,u.privacy_accepted_at,u.last_seen_at,u.created_at,p.primary_position,p.secondary_position,p.dominant_foot,p.birth_year,p.height_cm,p.avatar_path,p.visibility FROM users u LEFT JOIN player_profiles p ON p.user_id=u.id WHERE u.id=:id AND u.status<>"deleted" LIMIT 1');
        $stmt->execute(['id'=>$userId]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
    }

    public static function validateContact(array $data, int $userId): array
    {
        $errors=[];$email=strtolower(trim((string)($data['email']??'')));$phone=self::normalizePhone((string)($data['phone']??''));
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors['email']='Geçerli bir e-posta adresi gir.';
        elseif(Admin::identifierReserved($email)) $errors['email']='Bu e-posta adresi yönetim hesabına ayrılmış.';
        if($phone===null) $errors['phone']='Geçerli bir Türkiye cep telefonu numarası gir.';
        if($errors!==[]) return $errors;
        $stmt=Database::connection()->prepare('SELECT id,email,phone FROM users WHERE id<>:id AND (LOWER(email)=LOWER(:email) OR phone=:phone) LIMIT 1');
        $stmt->execute(['id'=>$userId,'email'=>$email,'phone'=>$phone]);$row=$stmt->fetch(PDO::FETCH_ASSOC);
        if($row){if(strtolower((string)$row['email'])===$email)$errors['email']='Bu e-posta başka bir hesapta kullanılıyor.';if((string)$row['phone']===$phone)$errors['phone']='Bu telefon numarası başka bir hesapta kullanılıyor.';}
        return $errors;
    }

    public static function updateOwnContact(int $userId,array $data): void
    {
        $stmt=Database::connection()->prepare('UPDATE users SET email=:email,phone=:phone,updated_at=NOW() WHERE id=:id AND status="active"');
        $stmt->execute(['email'=>strtolower(trim((string)$data['email'])),'phone'=>self::normalizePhone((string)$data['phone']),'id'=>$userId]);
    }

    public static function adminUpdateContact(int $userId,array $data): array
    {
        $errors=self::validateContact($data,$userId);if($errors!==[]) return $errors;
        $stmt=Database::connection()->prepare('UPDATE users SET email=:email,phone=:phone,updated_at=NOW() WHERE id=:id AND status<>"deleted"');
        $stmt->execute(['email'=>strtolower(trim((string)$data['email'])),'phone'=>self::normalizePhone((string)$data['phone']),'id'=>$userId]);return [];
    }

    public static function adminDetail(int $userId): ?array
    {
        $stmt=Database::connection()->prepare('SELECT u.*,p.primary_position,p.secondary_position,p.dominant_foot,p.birth_year,p.height_cm,p.bio,p.avatar_path,p.visibility,(SELECT COUNT(*) FROM team_members tm WHERE tm.user_id=u.id AND tm.status="active") team_count,(SELECT COUNT(*) FROM player_wanted_applications a WHERE a.user_id=u.id) application_count,(SELECT COUNT(*) FROM reports r WHERE r.reporter_user_id=u.id OR (r.target_type="user" AND r.target_id=u.id)) report_count,(SELECT COUNT(*) FROM player_match_stats s WHERE s.user_id=u.id) stat_rows,(SELECT COALESCE(SUM(s.goals),0) FROM player_match_stats s WHERE s.user_id=u.id) goals,(SELECT COALESCE(SUM(s.assists),0) FROM player_match_stats s WHERE s.user_id=u.id) assists FROM users u LEFT JOIN player_profiles p ON p.user_id=u.id WHERE u.id=:id LIMIT 1');
        $stmt->execute(['id'=>$userId]);$row=$stmt->fetch(PDO::FETCH_ASSOC);if(!is_array($row)) return null;
        $teams=Database::connection()->prepare('SELECT t.id,t.name,t.province,t.district,tm.role,tm.joined_at FROM team_members tm INNER JOIN teams t ON t.id=tm.team_id WHERE tm.user_id=:id AND tm.status="active" ORDER BY tm.joined_at DESC');$teams->execute(['id'=>$userId]);$row['teams']=$teams->fetchAll(PDO::FETCH_ASSOC)?:[];
        $posts=Database::connection()->prepare('SELECT id,position,match_at,status,province,district FROM player_wanted_posts WHERE created_by=:id ORDER BY created_at DESC LIMIT 10');$posts->execute(['id'=>$userId]);$row['posts']=$posts->fetchAll(PDO::FETCH_ASSOC)?:[];
        return $row;
    }

}
