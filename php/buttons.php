<?php
session_start();
// Protect this page – if not logged in send back to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: ../User/index.php');
    exit;
}

require_once __DIR__ . '/db.php';

$userEmail = $_SESSION['user_email'] ?? 'Player';
$loggedIn  = true;

/* Helper: fetch today's progress row */
function fetchToday($pdo, $uid) {
    try {
        $stmt = $pdo->prepare(
            "SELECT progress_date, games_played, quests_attempted, quests_completed, score_gained
               FROM daily_progress
              WHERE user_id = :uid AND progress_date = CURDATE() LIMIT 1"
        );
        $stmt->execute(['uid' => $uid]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/* Helper: fetch last 7 days of progress */
function fetchChecklist($pdo, $uid) {
    try {
        $stmt = $pdo->prepare(
            "SELECT progress_date, games_played, quests_attempted, quests_completed, score_gained
               FROM daily_progress
              WHERE user_id = :uid
                AND progress_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              ORDER BY progress_date ASC"
        );
        $stmt->execute(['uid' => $uid]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/* Helper: fetch frequently missed quests */
function fetchWeakQuests($pdo, $uid) {
    try {
        $stmt = $pdo->prepare(
            "SELECT language, question_text, code_text, hint_text, attempts, wrong_count
               FROM quest_stats
              WHERE user_id = :uid AND wrong_count > 0
              ORDER BY wrong_count DESC, last_missed_at DESC
              LIMIT 7"
        );
        $stmt->execute(['uid' => $uid]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/* Helper: fetch top players ranked by lifetime score */
function fetchLeaderboard($pdo, $uid) {
    try {
        $stmt = $pdo->query(
            "SELECT u.id, u.email,
                    COALESCE(SUM(dp.score_gained), 0) AS total_score,
                    COALESCE(SUM(dp.quests_completed), 0) AS total_quests,
                    COALESCE(SUM(dp.games_played), 0) AS total_games
               FROM users u
               LEFT JOIN daily_progress dp ON dp.user_id = u.id
              GROUP BY u.id, u.email
              ORDER BY total_score DESC, total_quests DESC"
        );
        $rows = $stmt->fetchAll();

        $top10 = array_slice($rows, 0, 10);
        $myRank = null;
        foreach ($rows as $idx => $row) {
            if ((int)$row['id'] === $uid) {
                $myRank = $idx + 1;
                break;
            }
        }
        return ['top' => $top10, 'my_rank' => $myRank];
    } catch (Exception $e) {
        return ['top' => [], 'my_rank' => null];
    }
}

/* Helper: fetch current user's total lifetime score */
function fetchMyTotalScore($pdo, $uid) {
    try {
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(score_gained), 0) AS total_score
               FROM daily_progress
              WHERE user_id = :uid"
        );
        $stmt->execute(['uid' => $uid]);
        $row = $stmt->fetch();
        return $row ? (int)$row['total_score'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

$uid         = (int)$_SESSION['user_id'];
$todayRow    = fetchToday($pdo, $uid);
$checklist   = fetchChecklist($pdo, $uid);
$weakList    = fetchWeakQuests($pdo, $uid);
$lbData      = fetchLeaderboard($pdo, $uid);
$myTotalScore = fetchMyTotalScore($pdo, $uid);
$targetScore = 500;

/* Top-ranking player from the leaderboard */
$topPlayer = $lbData['top'][0] ?? null;
$topPlayerName = '—';
$topPlayerScore = 0;
if ($topPlayer) {
    $topEmail = $topPlayer['email'] ?? 'Player';
    $topLocal = explode('@', $topEmail)[0];
    $topPlayerName = strlen($topLocal) > 10 ? substr($topLocal, 0, 10) . '…' : $topLocal;
    $topPlayerScore = (int)($topPlayer['total_score'] ?? 0);
}

$todayScore    = $todayRow ? (int)$todayRow['score_gained'] : 0;
$todayPlayed   = $todayRow ? (int)$todayRow['games_played'] : 0;
$todayAttempts = $todayRow ? (int)$todayRow['quests_attempted'] : 0;
$todayDone     = $todayRow ? (int)$todayRow['quests_completed'] : 0;

/* Build a date => row map so the last 7 days can be rendered even when no play happened */
$byDate = [];
foreach ($checklist as $row) {
    $byDate[$row['progress_date']] = $row;
}

$week = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $week[] = [
        'date'  => $d,
        'label' => date('D, M j', strtotime($d)),
        'data'  => $byDate[$d] ?? null
    ];
}

$dashboard = [
    'today'       => $todayRow,
    'week'        => $week,
    'weak'        => $weakList,
    'leaderboard' => $lbData['top'],
    'my_rank'     => $lbData['my_rank']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Code Maze — Main Menu</title>
  <link rel="icon" href="../image/log.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "JetBrains Mono", "Fira Code", Consolas, monospace;
    }
    body {
      min-height: 100vh;
      width: 100vw;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
      background: #000;
      position: relative;
    }

    /* ---------- Background layers ---------- */
    #bg-video {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: 1;
      filter: brightness(0.6) contrast(1.1);
    }

    #matrix-canvas {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 2;
      opacity: 0.35;
      pointer-events: none;
    }

    .glow-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle, rgba(0,255,128,0.1) 0%, rgba(0,0,0,0.8) 80%);
      z-index: 3;
      pointer-events: none;
    }

    .logo {
      position: absolute;
      top: 10px;
      left: 10px;
      width: 200px;
      height: 200px;
      border-radius: 10px;
      box-shadow: 0 5px 10px rgba(0,0,0,0.3);
      z-index: 5;
    }
    .menu {
      position: relative;
      z-index: 4;
      width: 90%;
      max-width: 420px;
      padding: 40px 30px;
      background: rgba(10, 20, 30, 0.75);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-radius: 16px;
      border: 1px solid rgba(0, 255, 200, 0.25);
      box-shadow: 0 0 30px rgba(0, 230, 255, 0.2),
                  inset 0 0 15px rgba(0, 255, 128, 0.1);
      text-align: center;
      color: #fff;
    }

    .title {
      font-size: clamp(30px, 6vw, 38px);
      margin-bottom: 12px;
      letter-spacing: 2px;
      text-transform: uppercase;
      background: linear-gradient(90deg, #00d2ff, #00ff66);
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 0 0 10px rgba(0, 210, 255, 0.3);
    }

    .menu-email {
      display: block;
      font-size: 12px;
      color: #00ffcc;
      margin-top: 18px;
      opacity: 0.8;
      word-break: break-all;
    }

    .menu-score {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 14px;
      padding: 8px 18px;
      background: rgba(255, 215, 0, 0.12);
      border: 1px solid rgba(255, 215, 0, 0.45);
      border-radius: 30px;
      color: #ffd700;
      font-size: 16px;
      font-weight: bold;
      letter-spacing: 1px;
      box-shadow: 0 0 14px rgba(255, 215, 0, 0.25);
    }

    .menu-score i {
      color: #ffd700;
      font-size: 15px;
    }

    .menu-score span {
      color: #fff;
      font-weight: normal;
      font-size: 12px;
      margin-right: 2px;
    }
    .menu-top-player {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 10px;
      padding: 8px 18px;
      background: rgba(0, 230, 255, 0.10);
      border: 1px solid rgba(0, 230, 255, 0.40);
      border-radius: 30px;
      color: #7ce3ff;
      font-size: 13px;
      letter-spacing: 0.5px;
      box-shadow: 0 0 12px rgba(0, 230, 255, 0.20);
    }

    .menu-top-player i {
      color: #ffd700;
      font-size: 14px;
    }

    .menu-top-player b {
      color: #fff;
      font-weight: bold;
    }

    .menu-top-player .top-score {
      color: #ffd700;
      font-weight: bold;
      font-size: 14px;
    }

    /* ---------- Settings overlay (shared) ---------- */
    .settings-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.7);
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }

    .settings-overlay.active {
      display: flex;
    }

    .settings-panel {
      background: rgba(10, 20, 30, 0.92);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      padding: 30px;
      border-radius: 16px;
      width: 90%;
      max-width: 350px;
      border: 1px solid rgba(0, 255, 200, 0.25);
      box-shadow: 0 0 30px rgba(0, 230, 255, 0.2),
                  inset 0 0 15px rgba(0, 255, 128, 0.1);
      text-align: center;
    }

    .settings-title {
      color: #fff;
      font-size: 26px;
      margin-bottom: 25px;
      letter-spacing: 2px;
      text-transform: uppercase;
      background: linear-gradient(90deg, #00d2ff, #00ff66);
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .setting-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: rgba(255,255,255,0.1);
      padding: 15px 20px;
      border-radius: 12px;
      margin: 15px 0;
    }

    .setting-label {
      color: #fff;
      font-size: 17px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .toggle-switch {
      position: relative;
      width: 60px;
      height: 30px;
    }

    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .toggle-slider {
      position: absolute;
      cursor: pointer;
      inset: 0;
      background-color: #ccc;
      transition: 0.4s;
      border-radius: 30px;
    }

    .toggle-slider:before {
      position: absolute;
      content: "";
      height: 22px;
      width: 22px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: 0.4s;
      border-radius: 50%;
    }

    input:checked + .toggle-slider {
      background-color: #2ee6c6;
    }

    input:checked + .toggle-slider:before {
      transform: translateX(30px);
    }

    .back-btn {
      background: #ff6b6b;
      color: #fff;
      margin-top: 20px;
    }

    /* ---------- Story Selection Modal Styles ---------- */
    .story-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: linear-gradient(135deg, rgba(8, 12, 18, 0.72), rgba(0, 0, 0, 0.88)) center/cover no-repeat;
  background-size: cover;
  background-position: center;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  justify-content: center;
  align-items: center;
  z-index: 1000;
  transition: background-image 0.4s ease;
}

    .story-overlay.active {
      display: flex;
    }

    .story-modal {
      background: rgba(10, 20, 30, 0.95);
      border: 1px solid rgba(0, 255, 200, 0.3);
      box-shadow: 0 0 35px rgba(0, 230, 255, 0.25);
      border-radius: 16px;
      width: 90%;
      max-width: 600px;
      padding: 25px;
      text-align: center;
    }

    .story-modal-title {
      font-size: 24px;
      color: #00ffcc;
      margin-bottom: 5px;
      letter-spacing: 1.5px;
    }

    .story-modal-sub {
      color: #8fa9ab;
      font-size: 13px;
      margin-bottom: 20px;
    }

    .story-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      flex: 1 1 auto;
      min-height: 0;
      max-height: min(58vh, 520px);
      overflow-y: auto;
      overflow-x: hidden;
      padding-right: 5px;
      -webkit-overflow-scrolling: touch;
    }

    .story-card {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 18px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(0, 255, 200, 0.2);
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    .story-card.unlocked {
      border-color: rgba(0, 255, 200, 0.5);
      cursor: pointer;
    }

    .story-card.unlocked:hover {
      background: rgba(0, 255, 200, 0.12);
      transform: translateX(4px);
    }

    .story-card.locked {
      opacity: 0.5;
      filter: grayscale(100%);
      cursor: not-allowed;
    }

    .story-info {
      text-align: left;
    }

    .story-title-text {
      font-size: 15px;
      font-weight: bold;
      color: #fff;
    }

    .story-diff {
      display: inline-block;
      font-size: 10px;
      padding: 2px 8px;
      border-radius: 12px;
      font-weight: bold;
      margin-top: 4px;
      text-transform: uppercase;
    }
    /* Story Modal Container Updates */
.story-modal {
  background: rgba(8, 18, 28, 0.92);
  border: 1px solid rgba(0, 255, 200, 0.4);
  box-shadow: 0 0 45px rgba(0, 230, 255, 0.3),
              inset 0 0 20px rgba(0, 255, 128, 0.08);
  border-radius: 20px;
  width: 90%;
  max-width: 620px;
  max-height: 90vh;
  padding: 30px;
  text-align: center;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transform: translateY(0);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Interactive Story Card Elements */
.story-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  min-height: 132px;
  box-sizing: border-box;
  flex-shrink: 0;
  background: rgba(255, 255, 255, 0.03);
  background-size: cover;
  background-position: center;
  border: 1px solid rgba(0, 255, 200, 0.18);
  border-radius: 12px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.story-card.unlocked:hover {
  background-color: rgba(0, 255, 200, 0.12);
  border-color: rgba(0, 255, 200, 0.6);
  transform: translateY(-2px) translateX(4px);
  box-shadow: 0 8px 20px rgba(0, 255, 200, 0.15);
}

.story-card.story-card-current {
  border-color: rgba(0, 255, 200, 0.55);
}

.story-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, rgba(6, 14, 22, 0.9) 0%, rgba(6, 14, 22, 0.55) 100%);
  pointer-events: none;
}

.story-card > * {
  position: relative;
  z-index: 1;
}

/* Enhanced Play Button Styling */
.btn-play-story {
  padding: 10px 22px;
  font-size: 13px;
  background: linear-gradient(90deg, #00ff66, #00c6ff);
  color: #020d08;
  border: none;
  border-radius: 8px;
  font-weight: 800;
  letter-spacing: 0.8px;
  cursor: pointer;
  box-shadow: 0 0 15px rgba(0, 255, 102, 0.4);
  transition: all 0.25s ease;
}

.btn-play-story:hover {
  transform: scale(1.05);
  box-shadow: 0 0 25px rgba(0, 255, 102, 0.7);
  background: linear-gradient(90deg, #00ff88, #00d2ff);
}
.story-list::-webkit-scrollbar {
  width: 6px;
}
.story-list::-webkit-scrollbar-thumb {
  background: rgba(0, 255, 200, 0.4);
  border-radius: 8px;
}

    .diff-veryeasy { background: rgba(76, 175, 80, 0.2); color: #4caf50; border: 1px solid #4caf50; }
    .diff-easy     { background: rgba(33, 150, 243, 0.2); color: #2196f3; border: 1px solid #2196f3; }
    .diff-normal   { background: rgba(255, 152, 0, 0.2); color: #ff9800; border: 1px solid #ff9800; }
    .diff-hard     { background: rgba(244, 67, 54, 0.2); color: #f44336; border: 1px solid #f44336; }
    .diff-veryhard { background: rgba(156, 39, 176, 0.2); color: #9c27b0; border: 1px solid #9c27b0; }

    .btn-play-story {
      padding: 8px 16px;
      font-size: 12px;
      background: #00ff66;
      color: #000;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
    }

    .btn-play-story:hover {
      background: #00cc52;
    }

    .btn-locked {
      background: rgba(255, 255, 255, 0.1);
      color: #777;
      border: 1px solid #444;
      padding: 8px 16px;
      font-size: 12px;
      border-radius: 6px;
    }

    /* ---------- Slide-out drawer ---------- */
    .drawer-trigger {
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      width: 30px;
      z-index: 20;
      cursor: pointer;
    }

    .drawer-tab {
      position: fixed;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 38px;
      height: 90px;
      background: linear-gradient(180deg, rgba(0, 198, 255, 0.9), rgba(0, 255, 102, 0.9));
      border-radius: 0 14px 14px 0;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #000;
      font-size: 18px;
      z-index: 21;
      cursor: pointer;
      box-shadow: 0 0 18px rgba(0, 230, 255, 0.7);
      transition: left 0.4s ease;
    }

    .drawer-tab i {
      transition: transform 0.4s ease;
      animation: pulseArrow 1.5s ease-in-out infinite;
    }

    @keyframes pulseArrow {
      0%, 100% { transform: translateX(0); }
      50%      { transform: translateX(3px); }
    }

    .drawer {
      position: fixed;
      left: 0;
      top: 0;
      bottom: 0;
      width: 360px;
      max-width: 92vw;
      background: rgba(6, 14, 22, 0.94);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-right: 1px solid rgba(0, 255, 200, 0.3);
      box-shadow: 8px 0 40px rgba(0, 0, 0, 0.8), inset -1px 0 0 rgba(0, 255, 200, 0.2);
      z-index: 200;
      transform: translateX(-102%);
      transition: transform 0.38s cubic-bezier(0.25, 0.8, 0.3, 1);
      display: flex;
      flex-direction: column;
    }

    .drawer.open {
      transform: translateX(0);
      box-shadow: 22px 0 70px rgba(0, 0, 0, 0.9), inset -1px 0 0 rgba(0, 255, 200, 0.2);
    }

    .drawer-header {
      padding: 22px 20px 14px;
      border-bottom: 1px solid rgba(0, 255, 200, 0.18);
      background: linear-gradient(90deg, rgba(0, 198, 255, 0.10), rgba(0, 255, 102, 0.06));
    }

    .drawer-greeting {
      font-size: 12px;
      color: #00ffcc;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 6px;
    }

    .drawer-user {
      color: #fff;
      font-size: 17px;
      word-break: break-all;
      font-weight: bold;
    }

    .drawer-user i {
      color: #00ff66;
      margin-right: 8px;
    }

    /* Nav buttons */
    .drawer-nav, .menu-nav {
      padding: 12px 0;
      margin-top: 15px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .btn {
      width: 100%;
      padding: 13px 16px;
      font-size: 16px;
      border: 1px solid rgba(0, 210, 255, 0.4);
      border-radius: 8px;
      cursor: pointer;
      background: linear-gradient(90deg, #00c6ff, #0072ff);
      color: #fff;
      font-weight: bold;
      letter-spacing: 1px;
      text-transform: uppercase;
      text-align: left;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 0 12px rgba(0, 198, 255, 0.25);
      transition: all 0.3s ease;
    }

    .btn i {
      width: 22px;
      text-align: center;
    }

    .btn:hover {
      background: linear-gradient(90deg, #00ff66, #00c6ff);
      color: #000;
      transform: translateX(6px);
      box-shadow: 0 0 22px rgba(0, 255, 102, 0.6);
    }

    .btn-logout {
      margin-top: 4px;
      background: linear-gradient(90deg, #ff6b6b, #c0392b);
      border-color: rgba(255, 107, 107, 0.5);
      box-shadow: 0 0 12px rgba(255, 107, 107, 0.25);
      justify-content: center;
      margin-bottom: 10px;
    }

    .btn-logout:hover {
      background: linear-gradient(90deg, #ff5252, #e74c3c);
      color: #fff;
      transform: translateX(6px);
    }

    /* Scrollable stats area */
    .drawer-scroll {
      flex: 1;
      overflow-y: auto;
      padding: 14px 14px 20px;
    }

    .drawer-scroll::-webkit-scrollbar {
      width: 7px;
    }
    .drawer-scroll::-webkit-scrollbar-track {
      background: rgba(0,0,0,0.3);
      border-radius: 10px;
    }
    .drawer-scroll::-webkit-scrollbar-thumb {
      background: rgba(0, 230, 255, 0.4);
      border-radius: 10px;
    }
    .drawer-scroll::-webkit-scrollbar-thumb:hover {
      background: rgba(0, 255, 100, 0.5);
    }

    /* Daily Score progress widget */
    .score-widget {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(0, 255, 200, 0.2);
      border-radius: 14px;
      padding: 16px;
      margin-bottom: 14px;
    }

    .score-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-bottom: 10px;
    }

    .score-label {
      color: #7ce3ff;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 1.5px;
    }

    .score-value {
      font-size: 20px;
      color: #fff;
      font-weight: bold;
    }

    .score-value span {
      color: #00ff66;
    }

    .progress-bar {
      width: 100%;
      height: 16px;
      background: rgba(0,0,0,0.45);
      border-radius: 20px;
      overflow: hidden;
      border: 1px solid rgba(0, 255, 200, 0.2);
      position: relative;
    }

    .progress-fill {
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, #00d2ff, #00ff66);
      border-radius: 20px;
      transition: width 0.9s cubic-bezier(0.25, 0.8, 0.3, 1);
      box-shadow: 0 0 14px rgba(0, 255, 150, 0.7);
    }

    .score-stats {
      display: flex;
      justify-content: space-between;
      gap: 6px;
      margin-top: 14px;
    }

    .mini-stat {
      flex: 1;
      text-align: center;
      background: rgba(0, 0, 0, 0.35);
      border-radius: 8px;
      padding: 8px 4px;
    }

    .mini-stat b {
      display: block;
      color: #00ff66;
      font-size: 16px;
    }

    .mini-stat small {
      color: #9bb;
      font-size: 9.5px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Section shells inside drawer */
    .panel-title {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #fff;
      font-size: 13px;
      margin: 16px 0 10px;
      letter-spacing: 1.2px;
      text-transform: uppercase;
    }

    .panel-title i {
      color: #00ff66;
    }

    .checklist {
      display: flex;
      flex-direction: column;
      gap: 7px;
    }

    .day-row {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255,255,255,0.045);
      border: 1px solid rgba(255,255,255,0.06);
      border-radius: 10px;
      padding: 8px 10px;
    }

    .day-check {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      border: 1px solid rgba(255,255,255,0.15);
      background: rgba(255,255,255,0.06);
      color: rgba(255,255,255,0.35);
    }

    .day-row.done .day-check {
      background: #00ff66;
      border-color: #00ff66;
      color: #001108;
      box-shadow: 0 0 10px rgba(0,255,100,0.55);
    }

    .day-row.today {
      border-color: rgba(0, 230, 255, 0.5);
      background: rgba(0, 230, 255, 0.07);
    }

    .day-info {
      flex: 1;
      min-width: 0;
    }

    .day-name {
      color: #eaf6f5;
      font-size: 12px;
      font-weight: bold;
    }

    .day-meta {
      color: #8fa9ab;
      font-size: 10.5px;
      margin-top: 2px;
    }

    .day-badge {
      font-size: 10px;
      background: rgba(0, 255, 102, 0.15);
      color: #00ff66;
      padding: 3px 8px;
      border-radius: 20px;
      white-space: nowrap;
    }

    .day-badge.gray {
      background: rgba(255,255,255,0.08);
      color: #9bb;
    }

    .day-actions {
      display: flex;
      gap: 6px;
      flex-shrink: 0;
    }

    .day-action-btn {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      border: 1px solid rgba(255,255,255,0.15);
      background: rgba(255,255,255,0.06);
      color: #9bb;
      font-size: 11px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.25s ease;
    }

    .day-action-btn:hover {
      transform: scale(1.1);
    }

    .day-action-btn.check-btn {
      color: #00ff66;
      border-color: rgba(0,255,102,0.4);
      background: rgba(0,255,102,0.1);
    }

    .day-action-btn.check-btn:hover {
      background: rgba(0,255,102,0.25);
      box-shadow: 0 0 10px rgba(0,255,102,0.4);
    }

    .day-action-btn.delete-btn {
      color: #ff6b6b;
      border-color: rgba(255,107,107,0.4);
      background: rgba(255,107,107,0.1);
    }

    .day-action-btn.delete-btn:hover {
      background: rgba(255,107,107,0.25);
      box-shadow: 0 0 10px rgba(255,107,107,0.4);
    }

    .day-row.done .day-action-btn.check-btn {
      background: rgba(0,255,102,0.25);
      color: #001108;
      border-color: #00ff66;
    }

    /* Weak quest list  */
    .weak-list {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .weak-row {
      background: rgba(255, 82, 82, 0.07);
      border: 1px solid rgba(255, 90, 90, 0.25);
      border-radius: 10px;
      padding: 9px 11px;
    }

    .weak-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 8px;
    }

    .lang-chip {
      font-size: 9.5px;
      font-weight: bold;
      padding: 2px 7px;
      border-radius: 20px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      background: rgba(255,255,255,0.1);
      color: #ccc;
      white-space: nowrap;
    }

    .lang-chip.python      { background: #3776ab33; color: #8db9e8; }
    .lang-chip.javascript  { background: #f7df1e22; color: #f7df1e; }
    .lang-chip.html        { background: #e34c2633; color: #ff9d7e; }
    .lang-chip.php         { background: #777bb433; color: #c0c3e8; }

    .lang-chip i {
      margin-right: 4px;
    }

    .wrong-count {
      color: #ff6b6b;
      font-size: 11px;
      font-weight: bold;
      white-space: nowrap;
    }

    .weak-question {
      color: #e6f4f3;
      font-size: 12px;
      margin-top: 6px;
      line-height: 1.5;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      line-clamp: 2;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }

    .weak-code {
      margin-top: 6px;
      font-size: 10.5px;
      color: #79d8ff;
      background: rgba(0, 0, 0, 0.35);
      border-left: 2px solid #ff6b6b;
      padding: 6px 8px;
      border-radius: 0 6px 6px 0;
      white-space: pre-wrap;
      word-break: break-word;
      font-family: Consolas, monospace;
      max-height: 44px;
      overflow: hidden;
    }

    .weak-hint {
      margin-top: 6px;
      font-size: 10.5px;
      color: #ffd57e;
    }

    .weak-hint i {
      margin-right: 5px;
    }

    .empty-state {
      background: rgba(255,255,255,0.04);
      border: 1px dashed rgba(255,255,255,0.15);
      border-radius: 10px;
      padding: 14px;
      text-align: center;
      color: #9bb;
      font-size: 12px;
      line-height: 1.6;
    }

    .empty-state i {
      color: #00ff66;
      display: block;
      font-size: 19px;
      margin-bottom: 6px;
    }

    .footer-note {
      text-align: center;
      color: #557;
      font-size: 10px;
      padding: 6px 0 2px;
    }

    /* Leaderboard */
    .leaderboard {
      display: flex;
      flex-direction: column;
      gap: 7px;
    }

    .lb-row {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 10px;
      padding: 8px 10px;
      transition: all 0.3s ease;
    }

    .lb-row.top-1 {
      background: linear-gradient(90deg, rgba(255, 215, 0, 0.16), rgba(255, 165, 0, 0.06));
      border-color: rgba(255, 215, 0, 0.55);
      box-shadow: 0 0 14px rgba(255, 215, 0, 0.25);
    }

    .lb-rank {
      width: 26px;
      height: 26px;
      border-radius: 50%;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: bold;
      background: rgba(255,255,255,0.08);
      color: #9bb;
      border: 1px solid rgba(255,255,255,0.12);
    }

    .lb-rank i {
      font-size: 13px;
    }

    .top-1 .lb-rank {
      background: linear-gradient(135deg, #ffd700, #ff9800);
      color: #1a1200;
      border-color: #ffd700;
      box-shadow: 0 0 10px rgba(255, 215, 0, 0.7);
    }

    .lb-email {
      flex: 1;
      min-width: 0;
      color: #eaf6f5;
      font-size: 12px;
      font-weight: bold;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .lb-email.me {
      color: #00ff66;
    }

    .lb-email.me::after {
      content: " (you)";
      font-size: 10px;
      color: #00ff66;
    }

    .lb-quests {
      color: #79d8ff;
      font-size: 10.5px;
      white-space: nowrap;
    }

    .lb-score {
      color: #ffd57e;
      font-size: 12px;
      font-weight: bold;
      white-space: nowrap;
      min-width: 62px;
      text-align: right;
    }

    .lb-my-rank {
      margin-top: 8px;
      text-align: center;
      font-size: 11px;
      color: #7ce3ff;
    }

    .lb-my-rank b {
      color: #00ff66;
    }

    @media (max-height: 500px) {
      body { align-items: flex-start; padding-top: 20px; }
    }
  </style>
</head>
<body>

  <!-- Video Background Loop -->
  <video autoplay muted loop id="bg-video">
      <source src="../videos/ahightek.mp4" type="video/mp4">
  </video>

  <!-- Matrix Code Animation Overlay -->
  <canvas id="matrix-canvas"></canvas>

  <!-- Visual Glow Overlay -->
  <div class="glow-overlay"></div>

  <img src="../image/codemaze.png" alt="Code Maze Logo" class="logo">

  <!-- Center welcome card -->
  <div class="menu">
    <h1 class="title">CODE MAZE</h1>
    <p style="color:#b9eef7; font-size:15px; letter-spacing:1px;">Main Menu</p>

    <div class="menu-score">
      <i class="fas fa-trophy"></i>
      <span>Score:</span> <?php echo (int)$myTotalScore; ?>
    </div>

    <div class="menu-top-player">
      <i class="fas fa-crown"></i>
      <span>Top:</span> <b><?php echo htmlspecialchars($topPlayerName); ?></b>
      <span class="top-score"><?php echo (int)$topPlayerScore; ?></span>
    </div>

    <!-- Navigation buttons -->
    <nav class="menu-nav">
      <button class="btn" onclick="playGame()"><i class="fas fa-play"></i> Play Game</button>
      <button class="btn" onclick="showAbout()"><i class="fas fa-info-circle"></i> About</button>
      <button class="btn" onclick="showCredits()"><i class="fas fa-users"></i> Credits</button>
      <button class="btn" onclick="openSettings()"><i class="fas fa-cog"></i> Settings</button>
    </nav>

    <span class="menu-email">
      <i class="fas fa-user-astronaut"></i> <?php echo htmlspecialchars($userEmail); ?>
    </span>
  </div>

  <!-- Story Mode Selection Overlay -->
  <div class="story-overlay" id="storyModal">
    <div class="story-modal">
      <h2 class="story-modal-title"><i class="fas fa-book-open"></i> SELECT STORY / LEVEL</h2>
      <p class="story-modal-sub">All levels are listed below. Your current level is highlighted. Locked levels stay locked until the previous level is completed.</p>

      <div class="story-list" id="storyListContainer">
        <!-- Story Cards dynamically populated by JS -->
      </div>

      <button class="btn back-btn" onclick="closeStoryModal()" style="margin-top: 20px;">
        <i class="fas fa-times"></i> CLOSE
      </button>
    </div>
  </div>

  <!-- Hot trigger strip -->
  <div class="drawer-trigger" id="drawerTrigger" title="Slide out menu"></div>

  <!-- Visible peek tab -->
  <div class="drawer-tab" id="drawerTab">
    <i class="fas fa-chevron-right"></i>
  </div>

  <!-- ==================== SLIDE-OUT DRAWER ==================== -->
  <aside class="drawer" id="drawer">
    <!-- Header -->
    <div class="drawer-header">
      <div class="drawer-greeting">Welcome Back, Adventurer</div>
      <div class="drawer-user">
        <i class="fas fa-user-circle"></i>
        <?php echo htmlspecialchars($userEmail); ?>
      </div>
    </div>

    <!-- Scrollable progress area -->
    <div class="drawer-scroll">

      <!-- Daily Score progress bar widget -->
      <div class="score-widget">
        <div class="score-head">
          <div class="score-label">Today's Score</div>
          <div class="score-value">
            <span id="todayScoreNum">0</span> / <?php echo (int)$targetScore; ?>
          </div>
        </div>
        <div class="progress-bar">
          <div class="progress-fill" id="scoreFill"></div>
        </div>

        <div class="score-stats">
          <div class="mini-stat">
            <b id="playedNum">0</b>
            <small>Games Played</small>
          </div>
          <div class="mini-stat">
            <b id="doneNum">0</b>
            <small>Quests Done</small>
          </div>
          <div class="mini-stat">
            <b id="attemptNum">0</b>
            <small>Quests Tried</small>
          </div>
        </div>
      </div>

      

      <!-- Frequently missed quests -->
      <h3 class="panel-title"><i class="fas fa-exclamation-triangle"></i> Quests to Improve</h3>
      <div class="weak-list" id="weakListContainer">
        <?php if (empty($weakList)): ?>
          <div class="empty-state">
            <i class="fas fa-shield-alt"></i>
            No frequently missed quests yet — keep coding!
          </div>
        <?php else: ?>
          <?php foreach ($weakList as $w):
              $lang = htmlspecialchars($w['language'] ?? '');
              $langLower = strtolower($lang);
          ?>
            <div class="weak-row">
              <div class="weak-top">
                <span class="lang-chip <?php echo $langLower; ?>">
                  <i class="fas fa-code"></i> <?php echo $lang; ?>
                </span>
                <span class="wrong-count">
                  <i class="fas fa-times-circle"></i> Missed <?php echo (int)$w['wrong_count']; ?> time<?php echo (int)$w['wrong_count'] === 1 ? '' : 's'; ?>
                </span>
              </div>
              <div class="weak-question"><?php echo htmlspecialchars($w['question_text'] ?? ''); ?></div>
              <?php if (!empty($w['code_text'])): ?>
                <div class="weak-code"><?php echo htmlspecialchars($w['code_text'] ?? ''); ?></div>
              <?php endif; ?>
              <?php if (!empty($w['hint_text'])): ?>
                <div class="weak-hint">
                  <i class="fas fa-lightbulb"></i> <?php echo htmlspecialchars($w['hint_text'] ?? ''); ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Leaderboard -->
      <h3 class="panel-title"><i class="fas fa-trophy"></i> Leaderboard</h3>
      <div class="leaderboard" id="leaderboardContainer">
        <?php if (empty($lbData['top'])): ?>
          <div class="empty-state">
            <i class="fas fa-trophy"></i>
            No scores yet — be the first champion!
          </div>
        <?php else: ?>
          <?php foreach ($lbData['top'] as $idx => $player):
              $rank       = $idx + 1;
              $email      = htmlspecialchars($player['email'] ?? 'Player');
              $localPart  = explode('@', $email)[0];
              $shownName  = strlen($localPart) > 10 ? substr($localPart, 0, 10) . '…' : $localPart;
              $totalScore = (int)($player['total_score'] ?? 0);
              $totalQuest = (int)($player['total_quests'] ?? 0);
              $isMe       = (int)($player['id'] ?? 0) === $uid;
              $isTop      = $rank === 1;
          ?>
            <div class="lb-row <?php echo $isTop ? 'top-1' : ''; ?>">
              <div class="lb-rank">
                <?php if ($isTop): ?><i class="fas fa-crown"></i>
                <?php elseif ($rank === 2): ?><i class="fas fa-medal"></i>
                <?php elseif ($rank === 3): ?><i class="fas fa-medal"></i>
                <?php else: echo $rank; endif; ?>
              </div>
              <div class="lb-email <?php echo $isMe ? 'me' : ''; ?>"><?php echo $shownName; ?></div>
              <div class="lb-quests"><i class="fas fa-tasks"></i> <?php echo $totalQuest; ?></div>
              <div class="lb-score"><i class="fas fa-star"></i> <?php echo $totalScore; ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($lbData['my_rank'])): ?>
          <div class="lb-my-rank">
            Your global rank: <b>#<?php echo (int)$lbData['my_rank']; ?></b>
          </div>
        <?php endif; ?>
      </div>

      <div class="footer-note">Stats refresh automatically each time the drawer opens.</div>
    </div>

    <!-- Logout button -->
    <div style="padding:10px 14px 14px; background:rgba(0,0,0,0.2); border-top:1px solid rgba(0,255,200,0.12);">
      <button class="btn btn-logout" onclick="logoutUser()">
        <i class="fas fa-sign-out-alt"></i> Logout
      </button>
    </div>
  </aside>

  <!-- Settings Panel -->
  <div class="settings-overlay" id="settingsOverlay">
    <div class="settings-panel">
      <h2 class="settings-title">⚙ SETTINGS</h2>

      <div class="setting-item">
        <span class="setting-label">
          <i class="fas fa-volume-up"></i> Sound
        </span>
        <label class="toggle-switch">
          <input type="checkbox" id="soundToggle" checked>
          <span class="toggle-slider"></span>
        </label>
      </div>

      <div class="setting-item">
        <span class="setting-label">
          <i class="fas fa-music"></i> Music
        </span>
        <label class="toggle-switch">
          <input type="checkbox" id="musicToggle">
          <span class="toggle-slider"></span>
        </label>
      </div>

      <button class="btn back-btn" onclick="closeSettings()">← Back to Menu</button>
    </div>
  </div>

  <script>
    /* ---------- Server-provided dashboard snapshot ---------- */
    const DASHBOARD = <?php echo json_encode($dashboard); ?>;
    DASHBOARD.myRankUserId = <?php echo (int)$uid; ?>;
    const TARGET_SCORE = <?php echo (int)$targetScore; ?>;

    /* ---------- Story Selection Logic ---------- */
    const storiesData = [
      { id: 1, title: "Level 1: The Overworked Employee", diff: "Very Easy", class: "diff-veryeasy", desc: "3 quests. An overworked office worker seeks a mountain break.", theme: "office", image: "../image/problema.jpg" },
      { id: 2, title: "Level 2: The Mysterious Cave", diff: "Easy", class: "diff-easy", desc: "3 quests. A hidden cave behind the vines.", theme: "cave", image: "../image/town.jpg" },
      { id: 3, title: "Level 3: The Trap", diff: "Normal", class: "diff-normal", desc: "3 quests. The cave seals and the maze begins.", theme: "trap", image: "../image/excited.jpg" },
      { id: 4, title: "Level 4: The Ancient Maze", diff: "Hard", class: "diff-hard", desc: "3 quests. Bugs, puzzles, and the locked exit.", theme: "maze", image: "../image/4.jpg" },
      { id: 5, title: "Level 5: The Goal", diff: "Very Hard", class: "diff-veryhard", desc: "3 quests. Survive, solve, and escape.", theme: "core", image: "../image/box.jpg" }
    ];

    function playGame() {
      document.getElementById('storyModal').classList.add('active');
      renderStoryOptions();
    }

    function closeStoryModal() {
      document.getElementById('storyModal').classList.remove('active');
    }

    function getUnlockedStoryLevel() {
      const unlockedLevel = parseInt(localStorage.getItem('unlockedStoryLevel'), 10) || 1;
      return Math.max(1, unlockedLevel);
    }

    function getCurrentPlayableStoryId() {
      return Math.min(getUnlockedStoryLevel(), storiesData.length);
    }

    function applyStorySelectTheme(story) {
      const overlay = document.getElementById('storyModal');
      if (!overlay || !story) return;
      overlay.style.backgroundImage =
        `linear-gradient(135deg, rgba(8, 12, 18, 0.72), rgba(0, 0, 0, 0.82)), url('${story.image}')`;
    }

    function renderStoryOptions() {
      const container = document.getElementById('storyListContainer');
      const unlockedLevel = getUnlockedStoryLevel();
      const allComplete = unlockedLevel > storiesData.length;
      const currentId = getCurrentPlayableStoryId();
      const story = storiesData.find(s => s.id === currentId);

      if (!story) {
        container.innerHTML = '';
        return;
      }

      applyStorySelectTheme(story);

      container.innerHTML = storiesData.map((item) => {
        const isLocked = item.id > unlockedLevel;
        const isCurrent = item.id === currentId;
        const cardClass = isLocked
          ? 'story-card locked'
          : (isCurrent ? 'story-card unlocked story-card-current' : 'story-card unlocked');
        const bgStyle = ` style="background-image: url('${item.image}');"`;
        const statusLabel = isLocked
          ? 'Locked — complete the previous level first.'
          : (isCurrent
            ? (allComplete
              ? 'YOU ARE HERE · All levels complete — replay the final story'
              : `YOU ARE HERE · Current level ${item.id} of ${storiesData.length}. Complete 3 quests to unlock the next level.`)
            : 'Completed — replay this level.');
        const statusColor = isCurrent ? '#00ffc8' : '#8fa9ab';
        const action = isLocked
          ? '<button class="btn-locked" disabled><i class="fas fa-lock"></i> LOCKED</button>'
          : `<button class="btn-play-story" onclick="startStory(${item.id})"><i class="fas fa-play"></i>PLAY</button>`;

        return `
        <div class="${cardClass}" data-theme="${item.theme}"${bgStyle}>
          <div class="story-info">
            <div class="story-title-text">${item.title}</div>
            <span class="story-diff ${item.class}">${item.diff}</span>
            <div style="font-size: 11px; color: #8fa9ab; margin-top: 4px;">${item.desc}</div>
            <div style="font-size: 11px; color: ${statusColor}; margin-top: 8px;">${statusLabel}</div>
          </div>
          <div>
            ${action}
          </div>
        </div>
        `;
      }).join('');
    }

    function startStory(storyId) {
      const unlockedLevel = getUnlockedStoryLevel();
      if (storyId < 1 || storyId > unlockedLevel) {
        return;
      }
      const playableId = Math.min(storyId, storiesData.length);
      location.href = `storyline.php?story=${playableId}`;
    }

    window.addEventListener('pageshow', renderStoryOptions);

    /* ---------- Navigation ---------- */
    function showAbout()   { location.href = 'about.php'; }
    function showCredits() { location.href = 'credits.php'; }

    function logoutUser() {
      location.href = 'logout.php';
    }

    /* ---------- Drawer open/close ---------- */
    const drawer       = document.getElementById('drawer');
    const drawerTab    = document.getElementById('drawerTab');
    const drawerArrow  = drawerTab.querySelector('i');
    const trigger      = document.getElementById('drawerTrigger');
    let openTimer      = null;

    function openDrawer() {
      clearTimeout(openTimer);
      drawer.classList.add('open');
      drawerTab.style.left = (drawer.offsetWidth) + 'px';
      drawerArrow.className = 'fas fa-chevron-left';
      drawerArrow.style.animation = 'none';
      refreshStats();
    }

    function closeDrawer() {
      drawer.classList.remove('open');
      drawerTab.style.left = '0px';
      drawerArrow.className = 'fas fa-chevron-right';
      drawerArrow.style.animation = '';
    }

    function scheduleClose() {
      clearTimeout(openTimer);
      openTimer = setTimeout(closeDrawer, 150);
    }

    document.addEventListener('mousemove', (e) => {
      if (e.clientX <= 340) {
        openDrawer();
      } else {
        clearTimeout(openTimer);
      }
    });

    drawer.addEventListener('mouseenter', openDrawer);
    drawer.addEventListener('mouseleave', scheduleClose);
    drawerTab.addEventListener('mouseenter', openDrawer);
    drawerTab.addEventListener('mouseleave', scheduleClose);
    trigger.addEventListener('mouseenter', openDrawer);

    drawerTab.addEventListener('click', () => {
      if (drawer.classList.contains('open')) {
        closeDrawer();
      } else {
        openDrawer();
      }
    });

    /* ---------- Settings ---------- */
    function openSettings() {
      document.getElementById('settingsOverlay').classList.add('active');
    }

    function closeSettings() {
      document.getElementById('settingsOverlay').classList.remove('active');
    }

    const soundToggle = document.getElementById('soundToggle');
    const musicToggle = document.getElementById('musicToggle');

    function loadSettings() {
      const sound = localStorage.getItem('codemaze_sound') !== 'off';
      const music = localStorage.getItem('codemaze_music') === 'on';
      soundToggle.checked = sound;
      musicToggle.checked = music;
    }

    soundToggle.addEventListener('change', () => {
      localStorage.setItem('codemaze_sound', soundToggle.checked ? 'on' : 'off');
    });

    musicToggle.addEventListener('change', () => {
      localStorage.setItem('codemaze_music', musicToggle.checked ? 'on' : 'off');
    });

    loadSettings();

    /* ---------- Live progress dashboard ---------- */
    let lastRefreshed = 0;

    function refreshStats() {
      const now = Date.now();
      if (now - lastRefreshed < 5000) return;
      lastRefreshed = now;

      fetch('progress_api.php')
        .then(r => r.json())
        .then(data => {
          if (!data.success) return;

          const today = data.today || { games_played: 0, quests_attempted: 0, quests_completed: 0, score_gained: 0 };
          const todayScore = Math.max(0, Number(today.score_gained) || 0);
          const pct = Math.min(100, (todayScore / TARGET_SCORE) * 100);

          document.getElementById('todayScoreNum').textContent = todayScore;
          document.getElementById('scoreFill').style.width = pct.toFixed(1) + '%';
          document.getElementById('playedNum').textContent  = Number(today.games_played) || 0;
          document.getElementById('doneNum').textContent    = Number(today.quests_completed) || 0;
          document.getElementById('attemptNum').textContent = Number(today.quests_attempted) || 0;

          renderWeakList(data.weak_quests || []);
          renderLeaderboard(data.leaderboard || [], data.my_rank);
        })
        .catch(() => {});
    }

    function checkDay(date) {
      fetch('progress_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'check_day', date: date })
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          lastRefreshed = 0;
          refreshStats();
          location.reload();
        }
      })
      .catch(() => {});
    }

    function deleteDay(date) {
      if (!confirm('Delete this day\'s progress?')) return;
      fetch('progress_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_day', date: date })
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          lastRefreshed = 0;
          refreshStats();
          location.reload();
        }
      })
      .catch(() => {});
    }

    function renderWeakList(items) {
      const box = document.getElementById('weakListContainer');
      if (!items.length) {
        box.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-shield-alt"></i>
            No frequently missed quests yet — keep coding!
          </div>`;
        return;
      }

      box.innerHTML = items.map(w => {
        const lang = w.language || '';
        const lower = lang.toLowerCase();
        const q = w.question_text || '';
        const code = w.code_text || '';
        const hint = w.hint_text || '';
        const wc = Number(w.wrong_count) || 0;

        return `
          <div class="weak-row">
            <div class="weak-top">
              <span class="lang-chip ${lower}"><i class="fas fa-code"></i> ${escapeHtml(lang)}</span>
              <span class="wrong-count"><i class="fas fa-times-circle"></i> Missed ${wc} time${wc === 1 ? '' : 's'}</span>
            </div>
            <div class="weak-question">${escapeHtml(q)}</div>
            ${code ? `<div class="weak-code">${escapeHtml(code)}</div>` : ''}
            ${hint ? `<div class="weak-hint"><i class="fas fa-lightbulb"></i> ${escapeHtml(hint)}</div>` : ''}
          </div>`;
      }).join('');
    }

    function abbreviateEmail(email) {
      if (!email) return 'Player';
      const local = String(email).split('@')[0];
      return local.length > 10 ? local.slice(0, 10) + '…' : local;
    }

    function renderLeaderboard(items, myRank) {
      const box = document.getElementById('leaderboardContainer');
      if (!items || !items.length) {
        box.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-trophy"></i>
            No scores yet — be the first champion!
          </div>`;
        return;
      }

      const rows = items.map((p, i) => {
        const rank = i + 1;
        const score = Number(p.total_score) || 0;
        const quests = Number(p.total_quests) || 0;
        const email = abbreviateEmail(p.email);
        const isMe = Number(p.id) === Number(DASHBOARD.myRankUserId);
        const isTop = rank === 1;
        const rankIcon = isTop ? '<i class="fas fa-crown"></i>' : (rank <= 3 ? '<i class="fas fa-medal"></i>' : rank);

        return `
          <div class="lb-row ${isTop ? 'top-1' : ''}">
            <div class="lb-rank">${rankIcon}</div>
            <div class="lb-email ${isMe ? 'me' : ''}">${escapeHtml(email)}</div>
            <div class="lb-quests"><i class="fas fa-tasks"></i> ${quests}</div>
            <div class="lb-score"><i class="fas fa-star"></i> ${score}</div>
          </div>`;
      }).join('');

      const myRankHtml = myRank ?
        `<div class="lb-my-rank">Your global rank: <b>#${myRank}</b></div>` : '';

      box.innerHTML = rows + myRankHtml;
    }

    function escapeHtml(str) {
      const div = document.createElement('div');
      div.textContent = str == null ? '' : String(str);
      return div.innerHTML;
    }

    // Open drawer once shortly after page load to give a first glance, then hide.
    setTimeout(openDrawer, 400);
    setTimeout(closeDrawer, 1800);

    /* ---------- Matrix Rain Script ---------- */
    const canvas = document.getElementById('matrix-canvas');
    const ctx = canvas.getContext('2d');

    function resizeCanvas() {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    }
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    const katakana = 'アァカサタナハマヤャラワガザダバパイィキシチニヒミリヰギジヂビピウゥクスツヌフムユュルグズブヅプエェケセテネヘメレヱゲゼデベペオォコソトノホモヨョロヲゴゾドボポヴッン';
    const latin = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    const alphabet = katakana + latin;

    const fontSize = 16;
    let columns = Math.floor(canvas.width / fontSize);

    let rainDrops = [];
    for (let x = 0; x < columns; x++) {
      rainDrops[x] = Math.floor(Math.random() * canvas.height / fontSize);
    }

    function drawMatrix() {
      ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      ctx.font = fontSize + 'px monospace';

      for (let i = 0; i < rainDrops.length; i++) {
        ctx.fillStyle = Math.random() > 0.5 ? '#00FF66' : '#00E5FF';

        const text = alphabet.charAt(Math.floor(Math.random() * alphabet.length));
        ctx.fillText(text, i * fontSize, rainDrops[i] * fontSize);

        if (rainDrops[i] * fontSize > canvas.height && Math.random() > 0.975) {
          rainDrops[i] = 0;
        }
        rainDrops[i]++;
      }
    }

    setInterval(drawMatrix, 35);
  </script>
</body>
</html>