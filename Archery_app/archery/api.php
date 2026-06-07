<?php
// api.php — JSON REST API for Archery Score Recording
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Parse JSON body
$body = [];
if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = $raw ? (json_decode($raw, true) ?? []) : $_POST;
}

try {
    // ── Public endpoints (no auth required) ───────────────────────────────────

    if ($action === 'login') {
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';
        if (!$username || !$password) jsonResponse(['error' => 'Username and password required'], 400);

        $user = attemptLogin($username, $password);
        if (!$user) jsonResponse(['error' => 'Invalid credentials or account inactive'], 401);

        loginUser($user);
        jsonResponse(['user' => [
            'username'   => $user['username'],
            'role'       => $user['role'],
            'archer_id'  => $user['archer_id'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
        ]]);
    }

    if ($action === 'logout') {
        logoutUser();
        jsonResponse(['ok' => true]);
    }

    // ── All other endpoints require auth ──────────────────────────────────────
    $user = requireAuth();

    switch ($action) {

        // GET — currently logged-in user
        case 'me':
            jsonResponse(['user' => $user]);

        // GET — all competitions
        case 'competitions':
            jsonResponse(['competitions' => getAllCompetitions()]);

        // GET ?competition_id=N — single competition
        case 'competition':
            $id = (int)($_GET['competition_id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'competition_id required'], 400);
            $comp = getCompetitionById($id);
            if (!$comp) jsonResponse(['error' => 'Competition not found'], 404);
            jsonResponse(['competition' => $comp]);

        // GET — all archers with club name
        case 'archers':
            jsonResponse(['archers' => getAllArchers()]);

        // GET — all categories  |  GET ?gender=male/female — filtered
        case 'categories':
            $gender = $_GET['gender'] ?? '';
            $cats   = $gender
                ? getCategoriesForGender($gender)
                : getAllCategories();
            jsonResponse(['categories' => $cats]);

        // GET — all clubs
        case 'clubs':
            jsonResponse(['clubs' => getAllClubs()]);

        // GET ?competition_id=&archer_id=&category_id=
        // Returns saved ends + round totals for a scoring session
        case 'session_data':
            $compId   = (int)($_GET['competition_id'] ?? 0);
            $archerId = (int)($_GET['archer_id']      ?? 0);
            $catId    = (int)($_GET['category_id']    ?? 0);
            if (!$compId || !$archerId || !$catId)
                jsonResponse(['error' => 'competition_id, archer_id, category_id required'], 400);

            jsonResponse([
                'ends'   => getSavedEnds($compId, $archerId, $catId),
                'rounds' => getSavedRounds($compId, $archerId, $catId),
            ]);

        // POST — save one end's 6 arrows
        // body: { competition_id, archer_id, category_id, round_number, end_number,
        //         arrows: ['X','9','8','7','7','6'], distance }
        case 'save_end':
            $compId      = (int)($body['competition_id'] ?? 0);
            $archerId    = (int)($body['archer_id']      ?? 0);
            $catId       = (int)($body['category_id']    ?? 0);
            $roundNum    = (int)($body['round_number']   ?? 0);
            $endNum      = (int)($body['end_number']     ?? 0);
            $arrows      = $body['arrows']   ?? [];
            $distance    = (int)($body['distance']       ?? 0);
            $endsPerRound= (int)($body['ends_per_round'] ?? 0);

            if (!$compId || !$archerId || !$catId || !$roundNum || !$endNum || !$endsPerRound)
                jsonResponse(['error' => 'competition_id, archer_id, category_id, round_number, end_number, ends_per_round required'], 400);
            if (!is_array($arrows) || count($arrows) !== 6)
                jsonResponse(['error' => 'Exactly 6 arrows required'], 422);

            // Validate each score
            foreach ($arrows as $s) {
                if (!isValidScore((string)$s))
                    jsonResponse(['error' => "Invalid score: $s"], 422);
            }
            // Validate descending order
            $prev = 13;
            foreach ($arrows as $s) {
                $ord = scoreOrder((string)$s);
                if ($ord > $prev) jsonResponse(['error' => 'Scores must be entered largest first'], 422);
                $prev = $ord;
            }

            saveEnd($compId, $archerId, $catId, $roundNum, $endNum, $arrows);
            recalcRound($compId, $archerId, $catId, $roundNum, $endsPerRound, $distance);

            // Check if full competition complete
            $comp        = getCompetitionById($compId);
            $savedRounds = getSavedRounds($compId, $archerId, $catId);
            $allComplete = count($savedRounds) === (int)$comp['number_of_rounds'];
            $grandTotal  = $allComplete
                ? array_sum(array_column($savedRounds, 'total_score'))
                : null;
            $totalXs     = $allComplete
                ? array_sum(array_column($savedRounds, 'x_number'))
                : null;

            jsonResponse([
                'saved'       => true,
                'all_complete'=> $allComplete,
                'grand_total' => $grandTotal,
                'total_xs'    => $totalXs,
                'rounds'      => $savedRounds,
            ]);

        // GET ?archer_id=N — history for an archer
        case 'history':
            $archerId = isset($_GET['archer_id'])
            ? (int)$_GET['archer_id']
            : (isset($user['archer_id']) ? (int)$user['archer_id'] : 0);
            if (!$archerId) jsonResponse(array('error' => 'archer_id required'), 400);
            $data = getArcherHistory($archerId);
            jsonResponse(array('history' => $data));
            break;
        default:
            jsonResponse(['error' => 'Unknown action'], 400);
    }

} catch (PDOException $e) {
    jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (InvalidArgumentException $e) {
    jsonResponse(['error' => $e->getMessage()], 422);
}
