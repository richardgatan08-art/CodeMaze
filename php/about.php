
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../css/about.css">
    <link rel="icon" href="../image/logo.png">

    <title>Game Instructions</title>

    <style>
        .instructions {
            width: 95%;
            max-width: 1500px;
            margin: 0 auto;
            padding: 25px;
            box-sizing: border-box;
        }

        .instructions h3 {
            text-align: center;
            margin-bottom: 10px;
        }

        .instructions .subtitle {
            text-align: center;
            margin-bottom: 25px;
        }

        .instruction-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            grid-template-rows: repeat(2, minmax(150px, auto));
            gap: 18px;
            margin-top: 25px;
        }

        .instruction-card {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 22px 15px;
            min-height: 150px;
            box-sizing: border-box;

            background: rgba(20, 30, 25, 0.95);
            border: 1px solid #39ff88;
            border-radius: 12px;

            box-shadow: 0 0 8px rgba(57, 255, 136, 0.12);
            transition: transform 0.2s ease,
                        box-shadow 0.2s ease,
                        border-color 0.2s ease;
        }

        .instruction-card:hover {
            transform: translateY(-5px);
            border-color: #7dffb0;
            box-shadow: 0 0 16px rgba(57, 255, 136, 0.3);
        }

        .instruction-icon {
            font-size: 30px;
            margin-bottom: 12px;
        }

        .instruction-card strong {
            display: block;
            color: #39ff88;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .instruction-card p {
            margin: 0;
            line-height: 1.5;
            font-size: 14px;
        }

        .instruction-card .danger {
            color: #ff6464;
            font-weight: bold;
        }

        .instruction-card .warning {
            color: #ffd166;
            font-weight: bold;
        }

        /* Four columns on medium-sized screens */
        @media (max-width: 1100px) {
            .instruction-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                grid-template-rows: auto;
            }
        }

        /* Two columns on tablets and smaller screens */
        @media (max-width: 700px) {
            .instructions {
                width: 100%;
                padding: 18px;
            }

            .instruction-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .instruction-card {
                padding: 18px 10px;
                min-height: 160px;
            }
        }

        /* One column on very small screens */
        @media (max-width: 400px) {
            .instruction-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="instructions">

    <a href="buttons.php" class="back-button">⬅ Back</a>

    <h3>HOW TO PLAY</h3>

    <p class="subtitle">⚠️ Only 3 Quests Per Level!</p>

    <div class="instruction-grid">

        <div class="instruction-card">
            <div class="instruction-icon">✅</div>
            <strong>3 Quest Terminals</strong>
            <p>Each level has 3 randomly placed quest terminals.</p>
        </div>

        <div class="instruction-card">
            <div class="instruction-icon">❌</div>
            <strong>One Chance Per Question</strong>
            <p>Answer incorrectly and lose <span class="danger">50 points</span>.</p>
        </div>

        <div class="instruction-card">
            <div class="instruction-icon">💡</div>
            <strong>Hint Penalty</strong>
            <p>Using a hint costs <span class="warning">20 points</span>.</p>
        </div>

        <div class="instruction-card">
            <div class="instruction-icon">🎯</div>
            <strong>Difficulty Levels</strong>
            <p>L1: Normal<br>L2: Easy<br>L3: Medium<br>L4: Hard</p>
        </div>

        <div class="instruction-card">
            <div class="instruction-icon">💻</div>
            <strong>Supported Languages</strong>
            <p>Python, PHP, HTML, and JavaScript only.</p>
        </div>

        <div class="instruction-card">
            <div class="instruction-icon">⏱️</div>
            <strong>Time Limit</strong>
            <p>Complete your quests before the timer runs out.</p>
        </div>

        <div class="instruction-card">
            <div class="instruction-icon">🚫</div>
            <strong>No Retries</strong>
            <p>Think carefully before submitting your answer.</p>
        </div>

        <div class="instruction-card">
            <div class="instruction-icon">⭐</div>
            <strong>Bonus Points</strong>
            <p>Finish faster to earn more points.</p>
        </div>

        <div class="instruction-card">
            <div class="instruction-icon">🧱</div>
            <strong>Grid System</strong>
            <p>Navigate around walls to reach the terminals.</p>
        </div>

        <div class="instruction-card">
            <div class="instruction-icon">🐛</div>
            <strong>Bug AI</strong>
            <p>Watch out! Bugs chase you when you get close.</p>
        </div>

    </div>
</div>

</body>
</html>
