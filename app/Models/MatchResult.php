<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

final class MatchResult
{
    /**
     * @param array<string,mixed> $payload
     * @param array<int,array<string,mixed>> $players
     * @return array<string,string>
     */
    public static function validatePayload(array $payload, array $players): array
    {
        $errors = [];
        $homeTeamId = (int) ($payload['home_team_id'] ?? 0);
        $awayTeamId = (int) ($payload['away_team_id'] ?? 0);
        $homeScore = self::nonNegativeInt($payload['home_score'] ?? null);
        $awayScore = self::nonNegativeInt($payload['away_score'] ?? null);

        if ($homeTeamId <= 0 || $awayTeamId <= 0 || $homeTeamId === $awayTeamId) {
            $errors['teams'] = 'Maç takımları geçersiz.';
        }
        if ($homeScore === null || $homeScore > 99) {
            $errors['home_score'] = 'Ev sahibi skoru 0-99 arasında olmalı.';
        }
        if ($awayScore === null || $awayScore > 99) {
            $errors['away_score'] = 'Deplasman skoru 0-99 arasında olmalı.';
        }

        $rosterByUser = [];
        foreach ($players as $player) {
            $userId = (int) ($player['user_id'] ?? 0);
            $teamId = (int) ($player['team_id'] ?? 0);
            if ($userId > 0 && in_array($teamId, [$homeTeamId, $awayTeamId], true)) {
                $rosterByUser[$userId] = $teamId;
            }
        }

        $stats = $payload['stats'] ?? [];
        if (!is_array($stats)) {
            return $errors + ['stats' => 'Oyuncu istatistikleri geçersiz.'];
        }

        $teamTotals = [
            $homeTeamId => ['goals' => 0, 'assists' => 0],
            $awayTeamId => ['goals' => 0, 'assists' => 0],
        ];

        foreach ($stats as $rawUserId => $row) {
            $userId = (int) $rawUserId;
            if (!isset($rosterByUser[$userId])) {
                $errors['stats'] = 'Maç kadrosunda olmayan oyuncuya istatistik girilemez.';
                continue;
            }
            if (!is_array($row)) {
                $errors['stats'] = 'Oyuncu istatistikleri geçersiz.';
                continue;
            }
            $goals = self::nonNegativeInt($row['goals'] ?? 0);
            $assists = self::nonNegativeInt($row['assists'] ?? 0);
            if ($goals === null || $goals > 99 || $assists === null || $assists > 99) {
                $errors['stats'] = 'Gol ve asist değerleri 0-99 arasında olmalı.';
                continue;
            }
            $teamId = $rosterByUser[$userId];
            $teamTotals[$teamId]['goals'] += $goals;
            $teamTotals[$teamId]['assists'] += $assists;
        }

        if ($homeScore !== null && isset($teamTotals[$homeTeamId])) {
            if ($teamTotals[$homeTeamId]['goals'] > $homeScore) {
                $errors['home_stats'] = 'Ev sahibi oyuncu golleri takım skorunu geçemez.';
            } elseif ($teamTotals[$homeTeamId]['assists'] > $teamTotals[$homeTeamId]['goals']) {
                $errors['home_stats'] = 'Ev sahibi toplam asisti toplam golü geçemez.';
            }
        }
        if ($awayScore !== null && isset($teamTotals[$awayTeamId])) {
            if ($teamTotals[$awayTeamId]['goals'] > $awayScore) {
                $errors['away_stats'] = 'Deplasman oyuncu golleri takım skorunu geçemez.';
            } elseif ($teamTotals[$awayTeamId]['assists'] > $teamTotals[$awayTeamId]['goals']) {
                $errors['away_stats'] = 'Deplasman toplam asisti toplam golü geçemez.';
            }
        }

        return $errors;
    }

    public static function canSubmit(string $status, bool $isCaptain, int $matchTimestamp, int $now): bool
    {
        return $isCaptain
            && $now >= $matchTimestamp
            && in_array($status, ['confirmed', 'disputed'], true);
    }

    public static function nextVerificationStatus(?string $homeConfirmedAt, ?string $awayConfirmedAt): string
    {
        return $homeConfirmedAt !== null && $awayConfirmedAt !== null ? 'verified' : 'score_pending';
    }

    public static function canConfirm(string $status, bool $isProposer, bool $isCaptain): bool
    {
        return $isCaptain && !$isProposer && $status === 'score_pending';
    }

    /** @param array<string,mixed> $payload */
    public static function submit(int $matchId, int $captainUserId, array $payload): ?array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $match = self::lockedMatch($pdo, $matchId);
            if ($match === null) {
                $pdo->rollBack();
                return null;
            }

            $side = FootballMatch::managerSide($match, $captainUserId);
            $matchAt = strtotime((string) $match['match_at']) ?: PHP_INT_MAX;
            if (!self::canSubmit((string) $match['status'], $side !== null, $matchAt, time())) {
                $pdo->rollBack();
                return null;
            }

            if (!MatchLineup::isMatchComplete($matchId, (int) $match['home_team_id'], (int) $match['away_team_id'], (string) $match['match_format'])) {
                throw new RuntimeException('İki takım da maç kadrosunu tamamlamadan sonuç girilemez.');
            }
            $players = self::playersForMatch($pdo, $matchId);
            if ($players === []) {
                throw new RuntimeException('Maç kadrosu boş. Önce takım kadrolarını tamamlayın.');
            }

            $payload['home_team_id'] = (int) $match['home_team_id'];
            $payload['away_team_id'] = (int) $match['away_team_id'];
            $errors = self::validatePayload($payload, $players);
            if ($errors !== []) {
                throw new RuntimeException((string) reset($errors));
            }

            $deleteStats = $pdo->prepare('DELETE FROM player_match_stats WHERE match_id=:match_id');
            $deleteStats->execute(['match_id' => $matchId]);
            $insertStats = $pdo->prepare(
                'INSERT INTO player_match_stats (match_id,user_id,goals,assists,verified)
                 VALUES (:match_id,:user_id,:goals,:assists,0)'
            );
            $statsInput = is_array($payload['stats'] ?? null) ? $payload['stats'] : [];
            foreach ($players as $player) {
                $userId = (int) $player['user_id'];
                $row = is_array($statsInput[$userId] ?? null) ? $statsInput[$userId] : [];
                $insertStats->execute([
                    'match_id' => $matchId,
                    'user_id' => $userId,
                    'goals' => self::nonNegativeInt($row['goals'] ?? 0) ?? 0,
                    'assists' => self::nonNegativeInt($row['assists'] ?? 0) ?? 0,
                ]);
            }

            $homeConfirmedAt = $side === 'home' ? date('Y-m-d H:i:s') : null;
            $awayConfirmedAt = $side === 'away' ? date('Y-m-d H:i:s') : null;
            $update = $pdo->prepare(
                'UPDATE matches
                 SET home_score=:home_score, away_score=:away_score, status="score_pending",
                     home_confirmed_at=:home_confirmed_at, away_confirmed_at=:away_confirmed_at,
                     result_submitted_by_user_id=:submitted_by, result_submitted_at=NOW(),
                     disputed_by_user_id=NULL, dispute_reason=NULL
                 WHERE id=:match_id'
            );
            $update->execute([
                'home_score' => (int) $payload['home_score'],
                'away_score' => (int) $payload['away_score'],
                'home_confirmed_at' => $homeConfirmedAt,
                'away_confirmed_at' => $awayConfirmedAt,
                'submitted_by' => $captainUserId,
                'match_id' => $matchId,
            ]);

            $pdo->commit();
            return self::transitionPayload($match, $matchId, $captainUserId, false);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function confirm(int $matchId, int $captainUserId): ?array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $match = self::lockedMatch($pdo, $matchId);
            if ($match === null) {
                $pdo->rollBack();
                return null;
            }
            $side = FootballMatch::managerSide($match, $captainUserId);
            $isProposer = (int) ($match['result_submitted_by_user_id'] ?? 0) === $captainUserId;
            if (!self::canConfirm((string) $match['status'], $isProposer, $side !== null)) {
                $pdo->rollBack();
                return null;
            }

            $confirmedAt = date('Y-m-d H:i:s');
            $homeConfirmedAt = $match['home_confirmed_at'] !== null ? (string) $match['home_confirmed_at'] : null;
            $awayConfirmedAt = $match['away_confirmed_at'] !== null ? (string) $match['away_confirmed_at'] : null;
            if ($side === 'home') {
                $homeConfirmedAt = $confirmedAt;
            } else {
                $awayConfirmedAt = $confirmedAt;
            }
            $status = self::nextVerificationStatus($homeConfirmedAt, $awayConfirmedAt);

            $update = $pdo->prepare(
                'UPDATE matches SET home_confirmed_at=:home_confirmed_at, away_confirmed_at=:away_confirmed_at, status=:status WHERE id=:match_id'
            );
            $update->execute([
                'home_confirmed_at' => $homeConfirmedAt,
                'away_confirmed_at' => $awayConfirmedAt,
                'status' => $status,
                'match_id' => $matchId,
            ]);

            if ($status === 'verified') {
                $verifyStats = $pdo->prepare('UPDATE player_match_stats SET verified=1 WHERE match_id=:match_id');
                $verifyStats->execute(['match_id' => $matchId]);
            }

            $pdo->commit();
            return self::transitionPayload($match, $matchId, $captainUserId, $status === 'verified');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function dispute(int $matchId, int $captainUserId, string $reason): ?array
    {
        $reason = trim($reason);
        if (strlen($reason) < 5 || strlen($reason) > 500) {
            throw new RuntimeException('İtiraz nedeni 5-500 karakter olmalı.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $match = self::lockedMatch($pdo, $matchId);
            if ($match === null) {
                $pdo->rollBack();
                return null;
            }
            $side = FootballMatch::managerSide($match, $captainUserId);
            $isProposer = (int) ($match['result_submitted_by_user_id'] ?? 0) === $captainUserId;
            if ($side === null || $isProposer || (string) $match['status'] !== 'score_pending') {
                $pdo->rollBack();
                return null;
            }

            $update = $pdo->prepare(
                'UPDATE matches
                 SET status="disputed", disputed_by_user_id=:disputed_by, dispute_reason=:reason,
                     home_confirmed_at=NULL, away_confirmed_at=NULL
                 WHERE id=:match_id'
            );
            $update->execute(['disputed_by' => $captainUserId, 'reason' => $reason, 'match_id' => $matchId]);
            $unverifyStats = $pdo->prepare('UPDATE player_match_stats SET verified=0 WHERE match_id=:match_id');
            $unverifyStats->execute(['match_id' => $matchId]);

            $pdo->commit();
            return self::transitionPayload($match, $matchId, $captainUserId, false) + [
                'result_submitted_by_user_id' => (int) ($match['result_submitted_by_user_id'] ?? 0),
                'reason' => $reason,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function stateForMatch(int $matchId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT m.*, home.name AS home_team_name, home.captain_user_id AS home_captain_user_id,
                    away.name AS away_team_name, away.captain_user_id AS away_captain_user_id
             FROM matches m
             INNER JOIN teams home ON home.id=m.home_team_id
             INNER JOIN teams away ON away.id=m.away_team_id
             WHERE m.id=:match_id LIMIT 1'
        );
        $statement->execute(['match_id' => $matchId]);
        $match = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($match)) {
            return [];
        }

        $stats = Database::connection()->prepare(
            'SELECT mp.team_id, u.id AS user_id, u.display_name, pp.primary_position, ml.role, ml.sort_order,
                    COALESCE(s.goals,0) AS goals, COALESCE(s.assists,0) AS assists, COALESCE(s.verified,0) AS verified
             FROM match_players mp
             INNER JOIN users u ON u.id=mp.user_id
             LEFT JOIN player_profiles pp ON pp.user_id=u.id
             LEFT JOIN match_lineups ml ON ml.match_id=mp.match_id AND ml.user_id=mp.user_id
             LEFT JOIN player_match_stats s ON s.match_id=mp.match_id AND s.user_id=mp.user_id
             WHERE mp.match_id=:match_id
             ORDER BY mp.team_id, FIELD(ml.role,"starter","substitute"), ml.sort_order, u.display_name'
        );
        $stats->execute(['match_id' => $matchId]);
        $match['players'] = $stats->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $match;
    }

    private static function lockedMatch(PDO $pdo, int $matchId): ?array
    {
        $statement = $pdo->prepare(
            'SELECT m.*, home.name AS home_team_name, home.captain_user_id AS home_captain_user_id,
                    away.name AS away_team_name, away.captain_user_id AS away_captain_user_id
             FROM matches m
             INNER JOIN teams home ON home.id=m.home_team_id
             INNER JOIN teams away ON away.id=m.away_team_id
             WHERE m.id=:match_id LIMIT 1 FOR UPDATE'
        );
        $statement->execute(['match_id' => $matchId]);
        $match = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($match) ? $match : null;
    }

    /** @return array<int,array<string,mixed>> */
    private static function playersForMatch(PDO $pdo, int $matchId): array
    {
        $statement = $pdo->prepare('SELECT match_id,user_id,team_id FROM match_players WHERE match_id=:match_id ORDER BY team_id,user_id');
        $statement->execute(['match_id' => $matchId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @param array<string,mixed> $match */
    private static function transitionPayload(array $match, int $matchId, int $actorUserId, bool $verified): array
    {
        $side = FootballMatch::managerSide($match, $actorUserId);
        $opponentCaptainId = $side === 'home'
            ? (int) $match['away_captain_user_id']
            : (int) $match['home_captain_user_id'];
        return [
            'match_id' => $matchId,
            'actor_user_id' => $actorUserId,
            'opponent_captain_user_id' => $opponentCaptainId,
            'home_captain_user_id' => (int) $match['home_captain_user_id'],
            'away_captain_user_id' => (int) $match['away_captain_user_id'],
            'home_team_name' => (string) $match['home_team_name'],
            'away_team_name' => (string) $match['away_team_name'],
            'verified' => $verified,
        ];
    }

    private static function nonNegativeInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value >= 0 ? $value : null;
        }
        if (!is_string($value) || $value === '' || !ctype_digit($value)) {
            return null;
        }
        return (int) $value;
    }
}
