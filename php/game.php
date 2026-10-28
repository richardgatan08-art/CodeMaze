<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Runner - Programming Adventure</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/game.css">
    <link rel="icon" href="../image/log.png">
    <style>
        /* Modern Vertical Left Sidebar & Quest Popup Layout */
        body {
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            min-height: 100vh;
            background-color: #0b0f19;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .header {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .game-title {
            margin: 0;
            font-size: 2rem;
            color: #00e676;
            text-shadow: 0 0 10px rgba(0, 230, 118, 0.5);
        }

        .subtitle {
            margin: 5px 0 0 0;
            font-size: 0.9rem;
            color: #b0bec5;
        }

        /* Container Layout: Left Panel + Center Map */
        .game-container {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            justify-content: center;
            gap: 15px;
            max-width: 100vw;
            padding: 10px;
        }

        /* Vertical Left Control Panel */
        .left-panel {
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 250px;
            flex-shrink: 0;
        }

        .panel-card {
            background: rgba(22, 28, 45, 0.9);
            border: 2px solid #1f293d;
            border-radius: 10px;
            padding: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        /* Vertical Stats Stack */
        .stats-vertical {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .stat-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #111625;
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 4px solid #00e676;
            height: 10px;
        }

        .stat-label {
            font-size: 0.8rem;
            font-weight: bold;
            color: #90a4ae;
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: bold;
            color: #ffffff;
        }

        /* Controls Card */
        .controls-vertical {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .timer-container {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            color: #ffca28;
            background: #111625;
            padding: 8px;
            border-radius: 6px;
        }

        .difficulty-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            background: #111625;
            padding: 8px;
            border-radius: 6px;
        }

        .sound-controls {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .sound-btn {
            background: #263238;
            border: 1px solid #37474f;
            color: #fff;
            padding: 6px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .sound-btn:hover {
            background: #37474f;
        }
        .sound-btn.active {
            background: #00838f;
            border-color: #00acc1;
        }

        .btn-view-quests {
            background: linear-gradient(135deg, #9c27b0, #673ab7);
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(156, 39, 176, 0.4);
            transition: transform 0.2s;
        }

        .btn-view-quests:hover {
            transform: scale(1.03);
        }

        /* Game Board Area */
        .game-area {
            position: relative;
        }

        .game-board {
            position: relative;
        }

        #game-canvas {
            border: 3px solid #1f293d;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.8);
            background: #050811;
        }

        /* Legend Items */
        .object-legend {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.85rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            display: inline-block;
        }

        /* Controls Help Styling */
        .controls-help {
            display: none !important;
        }
        .controls-title {
            margin: 0 0 6px 0;
            color: #80cbd3;
        }
        .key-row {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 4px;
        }
        .key {
            background: #37474f;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.75rem;
            font-family: monospace;
        }
        .key-label {
            margin-left: auto;
            color: #b0bec5;
        }

        /* Active Quests Pop-up Modal */
        .quests-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .quests-modal-content {
            background: #161c2d;
            border: 2px solid #9c27b0;
            border-radius: 12px;
            width: 420px;
            max-width: 90vw;
            padding: 20px;
            box-shadow: 0 0 30px rgba(156, 39, 176, 0.5);
            position: relative;
            animation: modalFadeIn 0.25s ease-out;
        }

        @keyframes modalFadeIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .quests-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #2a354d;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .quests-modal-title {
            margin: 0;
            font-size: 1.2rem;
            color: #e1bee7;
        }

        .quests-modal-close {
            background: none;
            border: none;
            color: #ff5252;
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
        }

        .code-blocks {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .code-block {
            background: #0f1422;
            border: 1px solid #2a354d;
            padding: 10px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .code-block.collected {
            border-color: #4caf50;
            background: rgba(76, 175, 80, 0.1);
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="bg-video">
        <source src="../videos/galax.mp4" type="video/mp4">
    </video>
    
    <a href="buttons.php" class="back-button">⬅ Back</a> 
    
    <div class="header">
        <h1 class="game-title">CODE MAZE</h1>
        <p class="subtitle">Only 3 quests per level! Random spawn positions!</p>
    </div>
    
    <div class="game-container">
        <!-- LEFT SIDE VERTICAL CARDS PANEL -->
        <div class="left-panel">
            <!-- STATS CARD -->
            <div class="panel-card">
                <div class="stats-vertical">
                    <div class="stat-box">
                        <span class="stat-label">SCORE</span>
                        <span id="score" class="stat-value">0</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">LIVES</span>
                        <span id="lives" class="stat-value">3</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">LEVEL</span>
                        <span id="level" class="stat-value">1</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">QUESTS</span>
                        <span id="quests-completed" class="stat-value">0/3</span>
                    </div>
                </div>
            </div>

            <!-- CONTROLS & TIMERS CARD -->
            <div class="panel-card controls-vertical">
                <div class="timer-container">
                    <i class="fas fa-clock timer-icon"></i>
                    <div class="timer-text">Time: <span id="timer">120</span>s</div>
                </div>
                
                <div class="difficulty-indicator">
                    <i class="fas fa-chart-line"></i>
                    <span class="difficulty-label">Difficulty:</span>
                    <span class="difficulty-value" id="difficulty">NORMAL</span>
                </div>
                
                <div class="sound-controls">
                    <button id="sound-toggle" class="sound-btn active">
                        <i class="fas fa-volume-up"></i> 
                    </button>
                    <button id="music-toggle" class="sound-btn">
                        <i class="fas fa-music"></i> Music: OFF
                    </button>
                </div>

                <!-- BUTTON TO OPEN ACTIVE QUESTS POPUP -->
                <button class="btn-view-quests" id="open-quests-btn">
                    <i class="fas fa-scroll"></i> View Quests
                </button>
            </div>
            <!-- CONTROLS HELP CARD -->
            <div class="panel-card controls-help">
                <h4 class="controls-title">CONTROLS</h4>
                <div class="key-row">
                    <span class="key">↑</span><span class="key">↓</span><span class="key">←</span><span class="key">→</span>
                    <span class="key">W</span><span class="key">A</span><span class="key">S</span><span class="key">D</span>
                    <span class="key-label">Move</span>
                </div>
                <div class="key-row">
                    <span class="key">P</span><span class="key">Esc</span>
                    <span class="key-label">Pause</span>
                </div>
                <div class="key-row">
                    <span class="key">R</span>
                    <span class="key-label">Restart</span>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE MAP CANVAS AREA -->
        <div class="game-area">
            <div class="game-board">
                <canvas id="game-canvas" width="1200" height="750"></canvas>
                
                <div id="level-complete-message" class="game-message">
                    <h2 class="message-title">LEVEL COMPLETE!</h2>
                    <p class="message-text">You completed all 3 programming quests!</p>
                    <button id="next-level-btn" class="btn">NEXT LEVEL</button>
                </div>
                
                <div id="game-over-message" class="game-message">
                    <h2 class="message-title">GAME OVER</h2>
                    <p class="message-text">You ran out of lives or time!</p>
                    <button id="restart-btn" class="btn">PLAY AGAIN</button>
                </div>
                
                <div id="pause-menu" class="game-message">
                    <h2 class="pause-title">GAME PAUSED</h2>
                    <div class="pause-stats">
                        <div class="pause-stat">
                            <span class="pause-stat-label">Level:</span>
                            <span class="pause-stat-value" id="pause-level">1</span>
                        </div>
                        <div class="pause-stat">
                            <span class="pause-stat-label">Score:</span>
                            <span class="pause-stat-value" id="pause-score">0</span>
                        </div>
                        <div class="pause-stat">
                            <span class="pause-stat-label">Lives:</span>
                            <span class="pause-stat-value" id="pause-lives">3</span>
                        </div>
                        <div class="pause-stat">
                            <span class="pause-stat-label">Time Left:</span>
                            <span class="pause-stat-value" id="pause-time">120s</span>
                        </div>
                        <div class="pause-stat">
                            <span class="pause-stat-label">Difficulty:</span>
                            <span class="pause-stat-value" id="pause-difficulty">NORMAL</span>
                        </div>
                    </div>
                    <div class="pause-buttons">
                        <button id="resume-btn" class="pause-btn resume-btn">RESUME GAME</button>
                        <button id="pause-restart-btn" class="pause-btn restart-btn">RESTART LEVEL</button>
                        <button id="quit-btn" class="pause-btn quit-btn">QUIT TO MENU</button>
                    </div>
                </div>
                
                <!-- ANSWER QUEST POPUP -->
                    <div id="quest-popup" class="quest-popup">
                        <div class="quest-header">
                            <h2 class="quest-title">QUEST <span id="quest-number">1</span> 
                                <span id="language-badge" class="language-badge python-badge">Python</span>
                            </h2>
                            <div class="quest-points">+100 points</div>
                        </div>
                        
                        <div class="quest-timer">
                            <i class="fas fa-hourglass-half"></i> Time: <span class="quest-time-left" id="quest-time-left">30</span>s
                        </div>
                        
                        <div class="question-text" id="quest-question"></div>
                        <div class="question-code" id="quest-code"></div>
                        <div class="quest-options" id="quest-options"></div>
                        <div class="quest-result" id="quest-result"></div>
                        
                        <div class="quest-buttons">
                            <button class="quest-btn quest-hint" id="quest-hint-btn">
                                <i class="fas fa-lightbulb"></i> Hint (-20pts)
                            </button>
                            <button class="quest-btn quest-submit" id="quest-submit-btn" disabled>
                                <i class="fas fa-paper-plane"></i> Submit Answer
                            </button>
                        </div>
                        <!-- Container sa upper left side para sa Return to Maze / Close Button na may Logo -->
                        <div class="quest-header-left">
                            
                            <button id="quest-close-btn" class="quest-close-btn">❌️</button>
                        </div>
                        <div class="quest-exit-buttons">
                            <button class="quest-btn quest-exit" id="quest-exit-btn">
                                <i class="fas fa-sign-out-alt"></i> Exit Game
                            </button>
                        </div>
                    </div>
            </div>
        </div>
    </div>

    <!-- ACTIVE QUESTS POPUP MODAL -->
    <div id="active-quests-modal" class="quests-modal">
        <div class="quests-modal-content">
            <div class="quests-modal-header">
                <h3 class="quests-modal-title"><i class="fas fa-tasks"></i> ACTIVE QUESTS (3/LEVEL)</h3>
                <button class="quests-modal-close" id="close-quests-modal-btn">&times;</button>
            </div>
            <div class="code-blocks" id="quest-list"></div>
        </div>
    </div>

    <!-- AUDIO FILES -->
    <audio id="background-music" loop><source src="../image/Ghostpocalypse.mp3" type="audio/mpeg"></audio>
    <audio id="collect-sound"><source src="../image/correct.mp3" type="audio/mpeg"></audio>
    <audio id="hit-sound"><source src="https://assets.mixkit.co/sfx/preview/mixkit-retro-game-emergency-alarm-1000.mp3" type="audio/mpeg"></audio>
    <audio id="door-sound"><source src="https://assets.mixkit.co/sfx/preview/mixkit-video-game-mystery-alert-234.mp3" type="audio/mpeg"></audio>
    <audio id="level-complete-sound"><source src="https://assets.mixkit.co/sfx/preview/mixkit-winning-chimes-2015.mp3" type="audio/mpeg"></audio>
    <audio id="game-over-sound"><source src="https://assets.mixkit.co/sfx/preview/mixkit-sad-game-over-trombone-471.mp3" type="audio/mpeg"></audio>
    <audio id="pause-sound"><source src="https://assets.mixkit.co/sfx/preview/mixkit-select-click-1109.mp3" type="audio/mpeg"></audio>
    <audio id="tick-sound"><source src="https://assets.mixkit.co/sfx/preview/mixkit-click-error-1110.mp3" type="audio/mpeg"></audio>
    <audio id="wrong-answer-sound"><source src="../image/wrong.mp3" type="audio/mpeg"></audio>
    <audio id="correct-answer-sound"><source src="../image/correct.mp3" type="audio/mpeg"></audio>

    <script src="../js/progress_tracker.js"></script>
    <script src="../js/game.js"></script>
</body>
</html>