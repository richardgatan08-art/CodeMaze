<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CODE MAZE - Login</title>
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

        /* 1. Video Background Container */
        .bg-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover; /* Pinananatili ang aspect ratio upang mapuno ang buong screen */
            z-index: 1;
            filter: brightness(0.6) contrast(1.1);
        }

        /* 2. Back / Close Button (Upper-Left Corner) */
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: rgba(10, 20, 30, 0.7);
            border: 1px solid rgba(0, 255, 200, 0.4);
            border-radius: 30px;
            color: #00ffcc;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: bold;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transition: all 0.3s ease;
            box-shadow: 0 0 10px rgba(0, 230, 255, 0.2);
        }

        .back-btn:hover {
            background: rgba(0, 255, 102, 0.2);
            border-color: #00ff66;
            color: #fff;
            box-shadow: 0 0 15px rgba(0, 255, 102, 0.6);
            transform: translateX(-3px);
        }

        .back-btn svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        /* 3. Floating Matrix Canvas Overlay */
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

        /* Ambient Glow Effect */
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

        /* 4. Login Box (Glassmorphism UI) */
        .login-container {
            position: relative;
            z-index: 4;
            width: 380px;
            padding: 40px 30px;
            background: rgba(10, 20, 30, 0.31);
            backdrop-filter: blur(9px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            border: 1px solid rgba(0, 255, 200, 0.25);
            box-shadow: 0 0 30px rgba(0, 230, 255, 0.2),
                        inset 0 0 15px rgba(0, 255, 128, 0.1);
            text-align: center;
            color: #fff;
        }

        .login-container h2 {
            font-size: 2rem;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: linear-gradient(90deg, #00d2ff, #00ff66);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 10px rgba(0, 210, 255, 0.3);
        }

        .login-container p {
            font-size: 0.85rem;
            color: #7ce3ff;
            margin-bottom: 30px;
            letter-spacing: 1px;
        }

        /* Form Inputs */
        .input-group {
            position: relative;
            margin-bottom: 25px;
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
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: #00ff66;
            box-shadow: 0 0 12px rgba(0, 255, 102, 0.5);
            background: rgba(0, 0, 0, 0.8);
        }

        /* Buttons & Actions */
        .login-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(90deg, #00c6ff, #0072ff);
            color: #fff;
            font-size: 1rem;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 0 15px rgba(0, 198, 255, 0.4);
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: linear-gradient(90deg, #00ff66, #00c6ff);
            box-shadow: 0 0 25px rgba(0, 255, 102, 0.7);
            color: #000;
            transform: translateY(-2px);
        }

        .footer-text {
            margin-top: 20px;
            font-size: 0.8rem;
            color: #888;
        }

        .footer-text a {
            color: #00ff66;
            text-decoration: none;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }
        
                .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            width: 100%;
            padding-right: 45px;
            box-sizing: border-box;
        }

        .eye-icon {
            position: absolute;
            right: 12px;
            top: 65%;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
        }
    </style>
</head>
<body>

    <!-- Back / Close Button (Upper Left Side) -->
    <a href="../index.php" class="back-btn">
        <svg viewBox="0 0 24 24">
            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
        </svg>
        <span>Back</span>
    </a>

    <!-- Video Background -->
    <video class="bg-video" autoplay loop muted playsinline>
        <source src="../videos/ahightek.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <!-- Matrix Code Animation Overlay -->
    <canvas id="matrix-canvas"></canvas>

    <!-- Visual Glow Overlay -->
    <div class="glow-overlay"></div>

    <!-- Login Box -->
    <div class="login-container">
        <h2>Code Maze</h2>
        <p>Code Your Adventure</p>

        <form action="log.in.php" method="POST">
            <div class="input-group">
                <label for="username">Gmail / Email</label>
                <input type="email" id="username" name="email" placeholder="name@gmail.com" required>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
                <span id="togglePassword" class="eye-icon">🙉</span>
            </div>

            <button type="submit" class="login-btn">Start Game</button>
        </form>

        <div class="footer-text">
            New Adventurer? <a href="create.php">Create Account</a>
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

                const password = document.getElementById("password");
        const togglePassword = document.getElementById("togglePassword");

        togglePassword.addEventListener("click", function () {
            if (password.type === "password") {
                password.type = "text";
                togglePassword.textContent = "🙈";
            } else {
                password.type = "password";
                togglePassword.textContent = "🙉";
            }
        });
    </script>
</body>
</html>