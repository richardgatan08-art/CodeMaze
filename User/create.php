<?php
session_start();

// Handle Form Submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db.php'; // Include your database connection

    $email           = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password        = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validation
    if (empty($email) || empty($password) || empty($confirmPassword)) {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid Gmail / Email address.";
        $messageType = "error";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match!";
        $messageType = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $messageType = "error";
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            
            if ($stmt->fetch()) {
                $message = "Email is already registered!";
                $messageType = "error";
            } else {
                // Hash the password securely
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert new account
                $insertStmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
                $insertStmt->execute([
                    'email' => $email,
                    'password' => $hashedPassword
                ]);

                $message = "Account created successfully! Redirecting...";
                $messageType = "success";

                // Automatically redirect to index.php or login page after 2 seconds
                echo "<script>setTimeout(() => { window.location.href = 'index.php'; }, 2000);</script>";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CODE MAZE - Register</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            height: 100vh;
            width: 100vw;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            background: #000;
            position: relative;
        }

        /* Background Image Container */
        .bg-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('image_ac7d82.jpg') no-repeat center center/contain;
            background-color: #050a0e;
            z-index: 1;
            filter: brightness(0.6) contrast(1.1);
        }

        /* Matrix Canvas */
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

        /* Glowing Overlay */
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

        /* Registration Glassmorphism Card */
        .register-container {
            position: relative;
            z-index: 4;
            width: 400px;
            padding: 48px 30px 35px;
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

        .register-container h2 {
            font-size: 1.8rem;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: linear-gradient(90deg, #00d2ff, #00ff66);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 10px rgba(0, 210, 255, 0.3);
        }

        .register-container p {
            font-size: 0.85rem;
            color: #7ce3ff;
            margin-bottom: 20px;
            letter-spacing: 1px;
        }

        /* Alert Message */
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        .alert.error {
            background: rgba(255, 50, 50, 0.2);
            border: 1px solid #ff4d4d;
            color: #ff9999;
        }
        .alert.success {
            background: rgba(0, 255, 102, 0.2);
            border: 1px solid #00ff66;
            color: #80ffbf;
        }

        /* Input Controls */
        .input-group {
            position: relative;
            margin-bottom: 18px;
            text-align: left;
        }

        .input-group label {
            display: block;
            font-size: 0.8rem;
            color: #00ffcc;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-group input {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(0, 210, 255, 0.4);
            border-radius: 8px;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: #00ff66;
            box-shadow: 0 0 12px rgba(0, 255, 102, 0.5);
            background: rgba(0, 0, 0, 0.8);
        }

        /* Back Button */
        .back-btn {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 7px 12px;
            border: 1px solid rgba(0, 210, 255, 0.45);
            border-radius: 7px;
            background: rgba(0, 0, 0, 0.35);
            color: #7ce3ff;
            font-size: 0.8rem;
            text-decoration: none;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: #00ff66;
            border-color: #00ff66;
            background: rgba(0, 255, 102, 0.1);
            box-shadow: 0 0 12px rgba(0, 255, 102, 0.35);
            transform: translateX(-2px);
        }

        /* Action Button */
        .register-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(90deg, #00ff66, #00c6ff);
            color: #000;
            font-size: 1rem;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 0 15px rgba(0, 255, 102, 0.4);
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .register-btn:hover {
            background: linear-gradient(90deg, #00c6ff, #0072ff);
            box-shadow: 0 0 25px rgba(0, 210, 255, 0.7);
            color: #fff;
            transform: translateY(-2px);
        }

        .footer-text {
            margin-top: 20px;
            font-size: 0.85rem;
            color: #888;
        }

        .footer-text a {
            color: #00d2ff;
            text-decoration: none;
            font-weight: bold;
        }

        .footer-text a:hover {
            color: #00ff66;
            text-shadow: 0 0 5px #00ff66;
        }
    </style>
</head>
<body>
    <!-- Background Image -->
    <div class="bg-image"></div>

    <!-- Matrix Code Animation -->
    <canvas id="matrix-canvas"></canvas>

    <!-- Visual Glow -->
    <div class="glow-overlay"></div>
    

    <!-- Register Form -->
    <div class="register-container">
        <a href="index.php" class="back-btn" onclick="if (history.length > 1) { history.back(); return false; }">
            ← Back
        </a>

        <h2>Join Code Maze</h2>
        <p>Create Your Adventurer Account</p>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <div class="input-group">
                <label for="email">Gmail / Email</label>
                <input type="email" id="email" name="email" placeholder="name@gmail.com" required autocomplete="off">
            </div>

            <div class="input-group">
                <label for="password">Passcode</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="input-group">
                <label for="confirm_password">Confirm Passcode</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="register-btn">Create Account</button>
        </form>

        <div class="footer-text">
            Already registered? <a href="index.php">Login Here</a>
        </div>
    </div>

    <!-- Matrix Rain Script -->
    <script>
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