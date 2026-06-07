<?php
// includes/helpers.php

require_once __DIR__ . '/config.php';

// ── Score utilities ───────────────────────────────────────────────────────────

const VALID_SCORES = ['X','10','9','8','7','6','5','4','3','2','1','M'];

function scoreValue(string $s): int {
    if ($s === 'X') return 10;
    if ($s === 'M') return 0;
    return (int)$s;
}

/** For enforcing descending entry order: X=12, 10=10 … 1=1, M=0 */
function scoreOrder(string $s): int {
    if ($s === 'X') return 12;
    if ($s === 'M') return 0;
    return (int)$s;
}

function isValidScore(string $s): bool {
    return in_array($s, VALID_SCORES, true);
}

// ── Clubs ─────────────────────────────────────────────────────────────────────

function getAllClubs(): array {
    return getDB()->query('SELECT club_id, club_name FROM clubs ORDER BY club_name')->fetchAll();
}

// ── Archers ───────────────────────────────────────────────────────────────────

function getAllArchers(): array {
    $stmt = getDB()->query(
        'SELECT a.archer_id, a.first_name, a.last_name, a.gender, a.dob, a.username,
                c.club_name
         FROM archers a
         JOIN clubs c ON c.club_id = a.club_id
         ORDER BY a.last_name, a.first_name'
    );
    return $stmt->fetchAll();
}

function getArcherById(int $id): ?array {
    $stmt = getDB()->prepare(
        'SELECT a.archer_id, a.first_name, a.last_name, a.gender, a.dob, a.username,
                a.club_id, c.club_name
         FROM archers a
         JOIN clubs c ON c.club_id = a.club_id
         WHERE a.archer_id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ── Categories ────────────────────────────────────────────────────────────────

function getAllCategories(): array {
    return getDB()->query(
        'SELECT category_id, bow_type, gender, age FROM categories ORDER BY gender, bow_type, age'
    )->fetchAll();
}

/**
 * Categories matching a given gender (for filtering after archer is chosen).
 */
function getCategoriesForGender(string $gender): array {
    $stmt = getDB()->prepare(
        'SELECT category_id, bow_type, gender, age
         FROM categories
         WHERE gender = ?
         ORDER BY bow_type, age'
    );
    $stmt->execute([$gender]);
    return $stmt->fetchAll();
}

// ── Competitions ──────────────────────────────────────────────────────────────

function getAllCompetitions(): array {
    $stmt = getDB()->query(
        'SELECT co.competition_id, co.name, co.start_date, co.end_date,
                co.state, co.format, co.number_of_rounds, co.ends_per_round,
                cl.club_name
         FROM competitions co
         JOIN clubs cl ON cl.club_id = co.club_id
         ORDER BY co.start_date DESC'
    );
    return $stmt->fetchAll();
}

function getCompetitionById(int $id): ?array {
    $stmt = getDB()->prepare(
        'SELECT co.competition_id, co.name, co.start_date, co.end_date,
                co.state, co.format, co.number_of_rounds, co.ends_per_round,
                cl.club_name
         FROM competitions co
         JOIN clubs cl ON cl.club_id = co.club_id
         WHERE co.competition_id = ?'
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// ── Ends (arrow scores) ───────────────────────────────────────────────────────

/**
 * Save one end's six arrows.
 * Uses INSERT ... ON DUPLICATE KEY UPDATE (upsert) so re-scoring is safe.
 *
 * @param array $arrows  Exactly 6 score strings in descending order.
 */
function saveEnd(
    int    $competitionId,
    int    $archerId,
    int    $categoryId,
    int    $roundNumber,
    int    $endNumber,
    array  $arrows
): void {
    if (count($arrows) !== 6) {
        throw new InvalidArgumentException('Exactly 6 arrows required');
    }
    foreach ($arrows as $s) {
        if (!isValidScore($s)) throw new InvalidArgumentException("Invalid score: $s");
    }

    $sql = '
        INSERT INTO ends
            (category_id, competition_id, archer_id, round_number, end_number,
             time, arrow_1, arrow_2, arrow_3, arrow_4, arrow_5, arrow_6)
        VALUES (?, ?, ?, ?, ?, CURTIME(), ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            time     = CURTIME(),
            arrow_1  = VALUES(arrow_1), arrow_2 = VALUES(arrow_2),
            arrow_3  = VALUES(arrow_3), arrow_4 = VALUES(arrow_4),
            arrow_5  = VALUES(arrow_5), arrow_6 = VALUES(arrow_6)
    ';
    $stmt = getDB()->prepare($sql);
    $stmt->execute([
        $categoryId, $competitionId, $archerId, $roundNumber, $endNumber,
        $arrows[0], $arrows[1], $arrows[2], $arrows[3], $arrows[4], $arrows[5],
    ]);
}

/**
 * All saved ends for a (competition, archer, category) session.
 * Returns [ round_number => [ end_number => ['X','9',…], … ], … ]
 */
function getSavedEnds(int $competitionId, int $archerId, int $categoryId): array {
    $stmt = getDB()->prepare(
        'SELECT round_number, end_number,
                arrow_1, arrow_2, arrow_3, arrow_4, arrow_5, arrow_6
         FROM ends
         WHERE competition_id = ? AND archer_id = ? AND category_id = ?
         ORDER BY round_number, end_number'
    );
    $stmt->execute([$competitionId, $archerId, $categoryId]);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[$row['round_number']][$row['end_number']] = [
            $row['arrow_1'], $row['arrow_2'], $row['arrow_3'],
            $row['arrow_4'], $row['arrow_5'], $row['arrow_6'],
        ];
    }
    return $result;
}

// ── Rounds (totals) ───────────────────────────────────────────────────────────

/**
 * Recompute and upsert the rounds totals row for one round.
 * Called automatically after every end save.
 */
function recalcRound(
    int $competitionId,
    int $archerId,
    int $categoryId,
    int $roundNumber,
    int $endsPerRound,
    int $distance
): void {
    // Fetch all saved ends for this round
    $stmt = getDB()->prepare(
        'SELECT arrow_1,arrow_2,arrow_3,arrow_4,arrow_5,arrow_6
         FROM ends
         WHERE competition_id=? AND archer_id=? AND category_id=? AND round_number=?'
    );
    $stmt->execute([$competitionId, $archerId, $categoryId, $roundNumber]);
    $rows = $stmt->fetchAll();

    if (count($rows) < $endsPerRound) return; // round not yet complete

    $total = 0;
    $xs    = 0;
    foreach ($rows as $row) {
        for ($i = 1; $i <= 6; $i++) {
            $s = $row["arrow_$i"];
            $total += scoreValue($s);
            if ($s === 'X') $xs++;
        }
    }

    $sql = '
        INSERT INTO rounds (category_id, competition_id, archer_id, round_number, total_score, x_number, distance)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE total_score=VALUES(total_score), x_number=VALUES(x_number), distance=VALUES(distance)
    ';
    getDB()->prepare($sql)->execute([
        $categoryId, $competitionId, $archerId, $roundNumber, $total, $xs, $distance,
    ]);
}

/**
 * Saved round totals for a session.
 * Returns [ round_number => ['total_score'=>…,'x_number'=>…,'distance'=>…], … ]
 */
function getSavedRounds(int $competitionId, int $archerId, int $categoryId): array {
    $stmt = getDB()->prepare(
        'SELECT round_number, total_score, x_number, distance
         FROM rounds
         WHERE competition_id=? AND archer_id=? AND category_id=?
         ORDER BY round_number'
    );
    $stmt->execute([$competitionId, $archerId, $categoryId]);
    $result = [];
    foreach ($stmt->fetchAll() as $row) {
        $result[(int)$row['round_number']] = $row;
    }
    return $result;
}

// ── History ───────────────────────────────────────────────────────────────────

/**
 * Completed round totals for an archer across all competitions.
 */
function getArcherHistory(int $archerId): array {
    $stmt = getDB()->prepare(
        'SELECT r.round_number, r.total_score, r.x_number, r.distance,
                co.name AS competition_name, co.start_date,
                cat.bow_type, cat.age
         FROM rounds r
         JOIN competitions co  ON co.competition_id  = r.competition_id
         JOIN categories   cat ON cat.category_id    = r.category_id
         WHERE r.archer_id = ?
         ORDER BY co.start_date DESC, r.round_number'
    );
    $stmt->execute([$archerId]);
    return $stmt->fetchAll();
}

// ── JSON helper ───────────────────────────────────────────────────────────────

function jsonResponse(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
