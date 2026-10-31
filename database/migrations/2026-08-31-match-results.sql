-- TakımBul v4: iki kaptan doğrulamalı maç sonucu + kariyer istatistikleri
-- Bu dosya v3 veritabanında bir kez çalıştırılmalıdır.

ALTER TABLE matches
    ADD COLUMN result_submitted_by_user_id BIGINT UNSIGNED NULL AFTER away_confirmed_at,
    ADD COLUMN result_submitted_at DATETIME NULL AFTER result_submitted_by_user_id,
    ADD COLUMN disputed_by_user_id BIGINT UNSIGNED NULL AFTER result_submitted_at,
    ADD COLUMN dispute_reason VARCHAR(500) NULL AFTER disputed_by_user_id,
    ADD CONSTRAINT fk_match_result_submitter FOREIGN KEY (result_submitted_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_match_disputed_by FOREIGN KEY (disputed_by_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- v3'te oluşturulmuş maçlarda kadro snapshot'ı yoksa aktif takım kadrosundan doldur.
INSERT IGNORE INTO match_players (match_id, user_id, team_id)
SELECT m.id, tm.user_id, tm.team_id
FROM matches m
INNER JOIN team_members tm
    ON tm.team_id IN (m.home_team_id, m.away_team_id)
   AND tm.status = 'active';
