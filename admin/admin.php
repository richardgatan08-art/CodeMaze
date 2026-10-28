<?php
require_once __DIR__ . '/../php/auth.php';
requireAdmin('index.php');

$adminEmail = $_SESSION['admin_email'] ?? 'Admin';
$adminInitial = strtoupper(substr($adminEmail, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Game Admin Dashboard – CodeMaze</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-gamepad"></i> Game<span>Admin</span>
        </div>
        <ul class="sidebar-menu">
            <li class="active" data-tab="dashboard"><i class="fas fa-chart-line"></i> Dashboard</li>
            <li data-tab="leaderboard"><i class="fas fa-trophy"></i> Leaderboards</li>
            <li data-tab="blog"><i class="fas fa-pen-fancy"></i> Progress Blog</li>
            <li data-tab="slides"><i class="fas fa-images"></i> Demo Slides</li>
            <li data-tab="create-account">
            <i class="fas fa-user-plus"></i> Create Account</li>
        </ul>
        <div class="logout-btn" id="logoutBtn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </div>
    </aside>

    <div class="main-content">
        <div class="top-header">
            <h1 id="pageTitle">Dashboard <small>Game Overview</small></h1>
            <div class="admin-profile">
                <span>Hello, <?= htmlspecialchars($adminEmail) ?></span>
                <div class="avatar"><?= htmlspecialchars($adminInitial) ?></div>
            </div>
        </div>

        <p id="adminFlash" class="admin-flash" hidden></p>

        <!-- ── TAB 1: DASHBOARD ───────────────────────────────────────── -->
        <div id="dashboardTab" class="tab-content active">
            <div class="stats-grid">
                <button type="button" class="stat-card" data-tab="dashboard" data-focus="playerSearchInput">
                    <div class="stat-body">
                        <div class="label">Total Players</div>
                        <div class="value" id="totalPlayersCount">0</div>
                    </div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                </button>
                <button type="button" class="stat-card" data-tab="dashboard">
                    <div class="stat-body">
                        <div class="label">Games Today</div>
                        <div class="value" id="gamesTodayCount">0</div>
                    </div>
                    <div class="icon"><i class="fas fa-gamepad"></i></div>
                </button>
                <button type="button"
                    class="stat-card"
                    id="questsCompletedTodayCard"
                    data-tab="dashboard">
                <div class="stat-body">
                    <div class="label">Quests Completed Today</div>
                    <div class="value" id="questsTodayCount">0</div>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </button>
                <button type="button" class="stat-card" data-tab="leaderboard">
                    <div class="stat-body">
                        <div class="label">Leaderboards</div>
                        <div class="value" id="leaderboardCountStat">0</div>
                    </div>
                    <div class="icon"><i class="fas fa-trophy"></i></div>
                </button>
                <button type="button" class="stat-card" data-tab="blog">
                    <div class="stat-body">
                        <div class="label">Blog Posts</div>
                        <div class="value" id="blogCountStat">0</div>
                    </div>
                    <div class="icon"><i class="fas fa-pen-fancy"></i></div>
                </button>
                <button type="button" class="stat-card" data-tab="slides">
                    <div class="stat-body">
                        <div class="label">Demo Slides</div>
                        <div class="value" id="slideCountStat">0</div>
                    </div>
                    <div class="icon"><i class="fas fa-images"></i></div>
                </button>
            </div>
            <div class="section-card"
     id="completedQuestsCard"
     hidden
     style="margin-bottom: 20px;">

    <div class="section-title">
        <i class="fas fa-check-circle"></i>
        Completed Quests Today
    </div>

    <div id="completedQuestsList">
        <p class="text-muted">Loading completed quests...</p>
    </div>

</div>

            <div class="section-card" id="findPlayerCard">
                <div class="section-title"><i class="fas fa-search"></i> Find Player</div>
                <input type="text" id="playerSearchInput" placeholder="Search by user ID or email..." style="width: 100%;">
            </div>

            <div class="section-card" id="playerProfileCard" hidden>
                <div class="section-title"><i class="fas fa-id-card"></i> Player Profile</div>
                <div id="profileDetails"></div>
            </div>
        </div>
        <!-- CREATE ACCOUNT TAB -->
<div id="create-accountTab" class="tab-content">
    <div class="section-card">
        <div class="section-title">
            <i class="fas fa-user-plus"></i> Create Player Account
        </div>

        <p class="text-muted" style="margin-bottom:14px;">
            Create a player account and save it to the database.
        </p>

        <form id="createAccountForm" autocomplete="off">
            <label for="accountEmail" class="file-label">Email</label>
            <input
                type="email"
                id="accountEmail"
                name="email"
                placeholder="player@example.com"
                required
                maxlength="180"
                style="margin-bottom:14px; width:100%;"
            >

            <label for="accountPassword" class="file-label">Password</label>
            <input
                type="password"
                id="accountPassword"
                name="password"
                placeholder="At least 8 characters"
                required
                minlength="8"
                autocomplete="new-password"
                style="margin-bottom:14px; width:100%;"
            >

            <label for="accountConfirmPassword" class="file-label">
                Confirm Password
            </label>
            <input
                type="password"
                id="accountConfirmPassword"
                name="confirm_password"
                placeholder="Re-enter password"
                required
                minlength="8"
                autocomplete="new-password"
                style="margin-bottom:14px; width:100%;"
            >

            <div class="form-actions">
                <button
                    class="btn btn-primary"
                    id="createAccountBtn"
                    type="submit"
                >
                    <i class="fas fa-user-plus"></i> Create Account
                </button>

                <button
                    class="btn btn-outline"
                    id="resetAccountBtn"
                    type="reset"
                >
                    Clear
                </button>
            </div>
        </form>
    </div>
</div>

        <!-- ── TAB 2: LEADERBOARD ─────────────────────────────────────── -->
        <div id="leaderboardTab" class="tab-content">
            <div class="section-card">
                <div class="section-title"><i class="fas fa-trophy"></i> Global Leaderboard</div>
                <p class="text-muted" style="margin-bottom:16px;">Ranked the same way as the in-game menu: lifetime score, then quests completed.</p>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>User ID</th>
                                <th>Email</th>
                                <th>Lifetime Score</th>
                                <th>Quests Done</th>
                                <th>Games Played</th>
                            </tr>
                        </thead>
                        <tbody id="leaderboardTableBody">
                            <tr>
                                <td colspan="6" class="text-muted" style="text-align:center; padding:15px;">Loading leaderboard...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ── TAB 3: PROGRESS BLOG ───────────────────────────────────── -->
        <div id="blogTab" class="tab-content">
            <div class="section-card">
                <div class="section-title"><i class="fas fa-pen-fancy"></i> Add / Edit Progress Entry</div>
                <p class="text-muted" style="margin-bottom:14px;">Saving an existing week number updates that homepage post.</p>
                <div class="blog-form">
                    <input type="hidden" id="blogId" value="">
                    <input type="number" id="blogWeek" placeholder="Week number (e.g. 1, 2, 3…)" min="1" style="margin-bottom:10px; width:100%;" />
                    <textarea id="blogText" placeholder="Write progress update for this week..." rows="5" style="width:100%; margin-bottom:10px;"></textarea>
                    <div class="form-actions">
                        <button class="btn btn-primary" id="addBlogBtn" type="button"><i class="fas fa-plus"></i> Save Entry</button>
                        <button class="btn btn-outline" id="resetBlogBtn" type="button">Clear</button>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-list-ul"></i> Homepage Blog Posts</div>
                <div id="blogList">
                    <p class="text-muted">Loading progress blogs...</p>
                </div>
            </div>
        </div>

        <!-- ── TAB 4: DEMO SLIDES ─────────────────────────────────────── -->
        <div id="slidesTab" class="tab-content">
            <div class="section-card">
                <div class="section-title"><i class="fas fa-images"></i> Add / Edit Demo Slide</div>
                <p class="text-muted" style="margin-bottom:14px;">These images appear in the System Demo slider on the homepage.</p>
                <form id="slideForm">
                    <input type="hidden" id="slideId" value="">
                    <input type="text" id="slideTitle" placeholder="Title (optional)" style="margin-bottom:10px; width:100%;">
                    <textarea id="slideDescription" placeholder="Caption / description (required)" rows="3" style="width:100%; margin-bottom:10px;"></textarea>
                    <label class="file-label">Image (JPEG, PNG, GIF, or WebP)</label>
                    <input type="file" id="slideImage" accept="image/jpeg,image/png,image/gif,image/webp" style="margin-bottom:14px; width:100%;">
                    <div class="form-actions">
                        <button class="btn btn-primary" id="saveSlideBtn" type="submit"><i class="fas fa-upload"></i> Save Slide</button>
                        <button class="btn btn-outline" id="resetSlideBtn" type="button">Clear</button>
                    </div>
                </form>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fas fa-list-ul"></i> Homepage Demo Slides</div>
                <div id="slideList">
                    <p class="text-muted">Loading slides...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/admin.js"></script>
</body>
</html>
