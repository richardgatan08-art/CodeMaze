<?php
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;

/* Automatically ensure required tracking tables exist */
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `daily_progress` (
        `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id`         INT UNSIGNED NOT NULL,
        `progress_date`   DATE         NOT NULL,
        `games_played`    INT          NOT NULL DEFAULT 0,
        `quests_attempted` INT         NOT NULL DEFAULT 0,
        `quests_completed` INT         NOT NULL DEFAULT 0,
        `score_gained`    INT          NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_user_day` (`user_id`,`progress_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `quest_stats` (
        `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id`         INT UNSIGNED NOT NULL,
        `question_key`    VARCHAR(190) NOT NULL,
        `language`        VARCHAR(80)  NOT NULL DEFAULT '',
        `question_text`   TEXT         NULL,
        `code_text`       TEXT         NULL,
        `hint_text`       TEXT         NULL,
        `attempts`        INT          NOT NULL DEFAULT 0,
        `wrong_count`     INT          NOT NULL DEFAULT 0,
        `correct_count`   INT          NOT NULL DEFAULT 0,
        `last_missed_at`  DATETIME     NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_user_question` (`user_id`,`question_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `quest_completion_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `language` VARCHAR(80) NOT NULL DEFAULT '',
    `question_text` TEXT NULL,
    `code_text` TEXT NULL,
    `hint_text` TEXT NULL,
    `completed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_completed_at` (`completed_at`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    /* Tables are optional if DB permissions block DDL – continue gracefully */
}

$method = $_SERVER['REQUEST_METHOD'];

/* ------------------------------------------------------------------ */
/*  Build safe question key + row data                                 */
/* ------------------------------------------------------------------ */
function readableKey($language, $text) {
    return substr(trim($language . '::' . trim($text)), 0, 190);
}

/* ------------------------------------------------------------------ */
/*  GET – dashboard summary                                            */
/* ------------------------------------------------------------------ */
if ($method === 'GET') {
    $target       = 500;
    $checklist    = [];
    $today        = null;
    $todayScore   = 0;
    $weakQuests   = [];
    $leaderboard  = [];
    $myRank       = null;
    $loggedIn     = (bool)$userId;

    if ($loggedIn) {
        try {
            $stmt = $pdo->prepare(
                "SELECT progress_date, games_played, quests_attempted, quests_completed, score_gained
                   FROM daily_progress
                  WHERE user_id = :uid
                    AND progress_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                  ORDER BY progress_date ASC"
            );
            $stmt->execute(['uid' => $userId]);
            $checklist = $stmt->fetchAll();

            $todayStmt = $pdo->prepare(
                "SELECT progress_date, games_played, quests_attempted, quests_completed, score_gained
                   FROM daily_progress
                  WHERE user_id = :uid AND progress_date = CURDATE() LIMIT 1"
            );
            $todayStmt->execute(['uid' => $userId]);
            $today = $todayStmt->fetch() ?: null;

            if ($today) {
                $todayScore = (int)$today['score_gained'];
            }

            $weakStmt = $pdo->prepare(
                "SELECT question_key, language, question_text, code_text, hint_text,
                        attempts, wrong_count
                   FROM quest_stats
                  WHERE user_id = :uid AND wrong_count > 0
                  ORDER BY wrong_count DESC, last_missed_at DESC
                  LIMIT 7"
            );
            $weakStmt->execute(['uid' => $userId]);
            $weakQuests = $weakStmt->fetchAll();

            /* Leaderboard – rank all players by total lifetime score */
            $lbStmt = $pdo->query(
                "SELECT u.id, u.email,
                        COALESCE(SUM(dp.score_gained), 0) AS total_score,
                        COALESCE(SUM(dp.quests_completed), 0) AS total_quests,
                        COALESCE(SUM(dp.games_played), 0) AS total_games
                   FROM users u
                   LEFT JOIN daily_progress dp ON dp.user_id = u.id
                  GROUP BY u.id, u.email
                  ORDER BY total_score DESC, total_quests DESC"
            );
            $allRanked = $lbStmt->fetchAll();
            $leaderboard = array_slice($allRanked, 0, 10);

            foreach ($allRanked as $idx => $row) {
                if ((int)$row['id'] === $userId) {
                    $myRank = $idx + 1;
                    break;
                }
            }
        } catch (Exception $e) {
            /* No tables / no data yet */
        }
    }

    echo json_encode([
        'success'      => true,
        'logged_in'    => $loggedIn,
        'target_score' => $target,
        'today'        => $today,
        'today_score'  => $todayScore,
        'checklist'    => $checklist,
        'weak_quests'  => $weakQuests,
        'leaderboard'  => $leaderboard,
        'my_rank'      => $myRank
    ]);
    exit;
}

/* ------------------------------------------------------------------ */
/*  All record-type calls require a logged in user                     */
/* ------------------------------------------------------------------ */
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$raw    = file_get_contents('php://input');
$input  = json_decode($raw, true);
$action = isset($input['action']) ? (string)$input['action'] : '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bad request']);
    exit;
}

try {
    switch ($action) {

        case 'game_start':
            $stmt = $pdo->prepare(
                "INSERT INTO daily_progress (user_id, progress_date, games_played)
                 VALUES (:uid, CURDATE(), 1)
                 ON DUPLICATE KEY UPDATE games_played = games_played + 1"
            );
            $stmt->execute(['uid' => $userId]);
            break;

        case 'quest_complete':
            $language = trim($input['language'] ?? '');
            $question = trim($input['question'] ?? '');
            $codeText = trim($input['code'] ?? '');
            $hintText = trim($input['hint'] ?? '');

            if ($question !== '') {
                $key   = readableKey($language, $question);
                $stats = $pdo->prepare(
                    "INSERT INTO quest_stats
                        (user_id, question_key, language, question_text, code_text,
                         hint_text, correct_count)
                     VALUES (:uid, :qk, :lang, :qt, :ct, :ht, 1)
                     ON DUPLICATE KEY UPDATE
                        correct_count = correct_count + 1,
                        language      = VALUES(language),
                        question_text = VALUES(question_text),
                        code_text     = VALUES(code_text),
                        hint_text     = VALUES(hint_text)"
                );
                $stats->execute([
                    'uid'  => $userId,
                    'qk'   => $key,
                    'lang' => $language,
                    'qt'   => mb_substr($question, 0, 600),
                    'ct'   => mb_substr($codeText, 0, 400),
                    'ht'   => mb_substr($hintText, 0, 500)
                ]);
            }

            $daily = $pdo->prepare(
                "INSERT INTO daily_progress
                    (user_id, progress_date, quests_attempted, quests_completed, score_gained)
                 VALUES (:uid, CURDATE(), 1, 1, 100)
                 ON DUPLICATE KEY UPDATE
                    quests_attempted = quests_attempted + 1,
                    quests_completed = quests_completed + 1,
                    score_gained     = score_gained + 100"
            );
                            $daily->execute(['uid' => $userId]);
                            $log = $pdo->prepare(
                    "INSERT INTO quest_completion_log
                        (user_id, language, question_text, code_text, hint_text, completed_at)
                    VALUES (:uid, :lang, :question, :code, :hint, NOW())"
                );

                $log->execute([
                    'uid'      => $userId,
                    'lang'     => $language,
                    'question' => $question,
                    'code'     => $codeText,
                    'hint'     => $hintText
                ]);
            break;

        case 'quest_wrong':
            $language = trim($input['language'] ?? '');
            $question = trim($input['question'] ?? '');
            $codeText = trim($input['code'] ?? '');
            $hintText = trim($input['hint'] ?? '');

            if ($question !== '') {
                $key   = readableKey($language, $question);
                $stats = $pdo->prepare(
                    "INSERT INTO quest_stats
                        (user_id, question_key, language, question_text, code_text,
                         hint_text, attempts, wrong_count, last_missed_at)
                     VALUES (:uid, :qk, :lang, :qt, :ct, :ht, 1, 1, NOW())
                     ON DUPLICATE KEY UPDATE
                        attempts       = attempts + 1,
                        wrong_count    = wrong_count + 1,
                        last_missed_at = NOW(),
                        language       = VALUES(language),
                        question_text  = VALUES(question_text),
                        code_text      = VALUES(code_text),
                        hint_text      = VALUES(hint_text)"
                );
                $stats->execute([
                    'uid'  => $userId,
                    'qk'   => $key,
                    'lang' => $language,
                    'qt'   => mb_substr($question, 0, 600),
                    'ct'   => mb_substr($codeText, 0, 400),
                    'ht'   => mb_substr($hintText, 0, 500)
                ]);
            }

            $daily = $pdo->prepare(
                "INSERT INTO daily_progress
                    (user_id, progress_date, quests_attempted, score_gained)
                 VALUES (:uid, CURDATE(), 1, -50)
                 ON DUPLICATE KEY UPDATE
                    quests_attempted = quests_attempted + 1,
                    score_gained     = score_gained - 50"
            );
            $daily->execute(['uid' => $userId]);
            break;

        case 'check_day':
            $date = trim($input['date'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid date']);
                exit;
            }
            $stmt = $pdo->prepare(
                "INSERT INTO daily_progress (user_id, progress_date, quests_completed)
                 VALUES (:uid, :date, 1)
                 ON DUPLICATE KEY UPDATE quests_completed = GREATEST(quests_completed, 1)"
            );
            $stmt->execute(['uid' => $userId, 'date' => $date]);
            break;

        case 'delete_day':
            $date = trim($input['date'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid date']);
                exit;
            }
            $stmt = $pdo->prepare(
                "DELETE FROM daily_progress WHERE user_id = :uid AND progress_date = :date"
            );
            $stmt->execute(['uid' => $userId, 'date' => $date]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            exit;
            case 'get_completed_today':
    $stmt = $pdo->prepare(
        "SELECT q.language,
                q.question_text,
                q.code_text,
                q.completed_at,
                u.email
         FROM quest_completion_log q
         LEFT JOIN users u ON u.id = q.user_id
         WHERE DATE(q.completed_at) = CURDATE()
         ORDER BY q.completed_at DESC"
    );

    $stmt->execute();

    echo json_encode([
        'success' => true,
        'quests'  => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
    exit;
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}