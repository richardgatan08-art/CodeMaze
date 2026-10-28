<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

requireAdminApi();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? $_POST['action'] ?? $input['action'] ?? '';

function adminCount(PDO $pdo, string $sql): int
{
    try {
        return (int)$pdo->query($sql)->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function playerLifetimeRow(PDO $pdo, array $user): array
{
    $totals = [
        'total_score'      => 0,
        'total_games'      => 0,
        'quests_attempted' => 0,
        'quests_completed' => 0,
    ];

    try {
        $stmt = $pdo->prepare(
            "SELECT
                    COALESCE(SUM(score_gained), 0) AS total_score,
                    COALESCE(SUM(games_played), 0) AS total_games,
                    COALESCE(SUM(quests_attempted), 0) AS quests_attempted,
                    COALESCE(SUM(quests_completed), 0) AS quests_completed
               FROM daily_progress
              WHERE user_id = :uid"
        );
        $stmt->execute(['uid' => $user['id']]);
        $row = $stmt->fetch() ?: [];
        $totals = array_merge($totals, $row);
    } catch (Exception $e) {
        /* daily_progress may be empty */
    }

    $recent = [];
    try {
        $stmt = $pdo->prepare(
            "SELECT progress_date, games_played, quests_attempted, quests_completed, score_gained
               FROM daily_progress
              WHERE user_id = :uid
                AND progress_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              ORDER BY progress_date DESC"
        );
        $stmt->execute(['uid' => $user['id']]);
        $recent = $stmt->fetchAll();
    } catch (Exception $e) {
        $recent = [];
    }

    $weak = [];
    try {
        $stmt = $pdo->prepare(
            "SELECT language, question_text, wrong_count
               FROM quest_stats
              WHERE user_id = :uid AND wrong_count > 0
              ORDER BY wrong_count DESC, last_missed_at DESC
              LIMIT 7"
        );
        $stmt->execute(['uid' => $user['id']]);
        $weak = $stmt->fetchAll();
    } catch (Exception $e) {
        $weak = [];
    }

    return [
        'id'                => $user['id'],
        'email'             => $user['email'],
        'join_date'         => $user['created_at'] ?? null,
        'total_score'       => (int)$totals['total_score'],
        'total_games'       => (int)$totals['total_games'],
        'quests_attempted'  => (int)$totals['quests_attempted'],
        'quests_completed'  => (int)$totals['quests_completed'],
        'recent_days'       => $recent,
        'weak_quests'       => $weak,
    ];
}

if ($action === 'get_overview') {
    echo json_encode([
        'success'                 => true,
        'total_players'           => adminCount($pdo, 'SELECT COUNT(*) FROM users'),
        'games_today'             => adminCount($pdo, 'SELECT COALESCE(SUM(games_played), 0) FROM daily_progress WHERE progress_date = CURDATE()'),
        'quests_completed_today'  => adminCount($pdo, 'SELECT COALESCE(SUM(quests_completed), 0) FROM daily_progress WHERE progress_date = CURDATE()'),
        'total_blogs'             => adminCount($pdo, 'SELECT COUNT(*) FROM blog_entries'),
        'total_slides'            => adminCount($pdo, 'SELECT COUNT(*) FROM slides'),
        'leaderboard_players'     => adminCount($pdo, 'SELECT COUNT(*) FROM users'),
    ]);
    exit;
}
if ($action === 'get_completed_today') {
    try {
        $stmt = $pdo->query(
            "SELECT
                q.language,
                q.question_text,
                q.code_text,
                q.last_missed_at AS completed_at,
                u.email
             FROM quest_stats q
             LEFT JOIN users u ON u.id = q.user_id
             WHERE q.correct_count > 0
             ORDER BY q.last_missed_at DESC
             LIMIT 20"
        );

        echo json_encode([
            'success' => true,
            'quests' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (PDOException $e) {
        error_log('get_completed_today error: ' . $e->getMessage());

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Could not load completed quests.'
        ]);
    }
    exit;
}

if ($action === 'search_player') {
    $query = trim($_GET['query'] ?? '');
    if ($query === '') {
        echo json_encode(['success' => false, 'error' => 'Enter a user ID or email.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT id, email, created_at
               FROM users
              WHERE id = :id OR email LIKE :q
              ORDER BY id ASC
              LIMIT 10"
        );
        $stmt->execute([
            'id' => ctype_digit($query) ? (int)$query : 0,
            'q'  => '%' . $query . '%',
        ]);
        $users = $stmt->fetchAll();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Could not search players.']);
        exit;
    }

    $players = array_map(static function ($user) use ($pdo) {
        return playerLifetimeRow($pdo, $user);
    }, $users);

    echo json_encode([
        'success' => !empty($players),
        'players' => $players,
        'error'   => empty($players) ? 'No player found.' : null,
    ]);
    exit;
}

if ($action === 'get_leaderboard') {
    try {
        $stmt = $pdo->query(
            "SELECT u.id, u.email,
                    COALESCE(SUM(dp.score_gained), 0) AS total_score,
                    COALESCE(SUM(dp.quests_completed), 0) AS total_quests,
                    COALESCE(SUM(dp.games_played), 0) AS total_games
               FROM users u
               LEFT JOIN daily_progress dp ON dp.user_id = u.id
              GROUP BY u.id, u.email
              ORDER BY total_score DESC, total_quests DESC, u.id ASC"
        );
        $rows = $stmt->fetchAll();
    } catch (Exception $e) {
        $rows = [];
    }
if ($action === 'delete_quests' || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($input['action'] ?? '') === 'delete_quests')) {
    $rawInput = file_get_contents('php://input');
    $dataInput = json_decode($rawInput, true);
    $ids = $dataInput['ids'] ?? $_POST['ids'] ?? [];

    if (empty($ids)) {
        echo json_encode(['success' => false, 'error' => 'Walang napiling ID para burahin.']);
        exit;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM quest_stats WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
    echo json_encode(['success' => true, 'leaderboard' => $rows]);
    exit;
}
if ($action === 'delete_quests') {
    $ids = $input['ids'] ?? $_POST['ids'] ?? [];

    if (empty($ids)) {
        echo json_encode(['success' => false, 'error' => 'Walang napiling ID para burahin.']);
        exit;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM quest_stats WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}



echo json_encode(['success' => false, 'error' => 'Unknown action.']);
