<?php
require_once __DIR__ . '/../php/auth.php';
if (isLoggedIn()) {
    header('Location: admin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login – CodeMaze</title>
    <link rel="stylesheet" href="../css/admin_login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>
<body>
    <canvas id="matrixCanvas" aria-hidden="true"></canvas>
    <div class="matrix-vignette" aria-hidden="true"></div>
    <div class="toast" id="toast">
        <i class="fas fa-check-circle" id="toastIcon"></i>
        <span id="toastMsg"></span>
    </div>

    <div class="container" id="container">

        <!-- Sign-up panel -->
        <div class="form-container sign-up">
            <form id="signupForm" novalidate>
                <h1>Create Account</h1>
                <span>or use your email for registration</span>
                <input type="email"    id="signupEmail"    placeholder="Email"    required />
                <input type="password" id="signupPassword" placeholder="Password (min 6 chars)" required />
                <button type="submit" class="input" id="signupBtn">Register123</button>
            </form>
        </div>

        <!-- Sign-in panel -->
        <div class="form-container sign-in">
            <form id="loginForm" novalidate>
                <h1>Sign In</h1>
                <span>or use your email &amp; password</span>
                <input type="email"    id="loginEmail"    placeholder="Email"    required />
                <input type="password" id="loginPassword" placeholder="Password" required />
                <a href="#" id="forgotLink">Forgot Your Password?</a>
                <button type="submit" class="input" id="loginBtn">LOGIN</button>
                <span id="togglePassword" class="eye-icon">🙉</span>
            </form>
        </div>

        <!-- Toggle panel -->
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Welcome Back!</h1>
                    <p>Already have an account? Sign in</p>
                    <button class="hidden" id="loginBtn2">Sign In</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <video class="register-card-video" autoplay muted loop playsinline aria-hidden="true">
                        <source src="../videos/galax.mp4" type="video/mp4">
                    </video>
                    <div class="register-card-video-overlay" aria-hidden="true"></div>
                    <div class="register-card-content">
                        <h1>Admin Site</h1>
                        <p>Only admins can Sign In on this site</p>
                        
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="../js/admin_login.js"></script>
    <style>
        :root {
            --green: #00ff88;
            --green-soft: #65ffb3;
            --cyan: #22d3ee;
            --ink: #06100d;
            --panel: rgba(5, 18, 14, 0.78);
            --panel-strong: rgba(4, 14, 11, 0.9);
            --line: rgba(0, 255, 136, 0.22);
            --muted: #8fa9a0;
            --white: #f4fff9;
        }

        * { box-sizing: border-box; }

        html, body {
            width: 100%;
            min-height: 100%;
        }

        body {
            margin: 0;
            min-height: 100vh;
            overflow: hidden;
            display: grid;
            place-items: center;
            position: relative;
            background:
                radial-gradient(circle at 50% 35%, rgba(0,255,136,.08), transparent 34%),
                #010604;
            color: var(--white);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        #matrixCanvas {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            opacity: .42;
        }

        .matrix-vignette {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background:
                radial-gradient(circle at center, transparent 20%, rgba(0,0,0,.28) 62%, rgba(0,0,0,.78) 100%),
                linear-gradient(180deg, rgba(0,20,12,.18), rgba(0,0,0,.55));
        }

        .container {
            position: relative;
            z-index: 5;
            width: min(940px, calc(100vw - 40px));
            height: 570px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            overflow: hidden;
            border: 1px solid rgba(94,255,178,.25);
            border-radius: 28px;
            background: rgba(3, 13, 10, .72);
            box-shadow:
                0 30px 100px rgba(0,0,0,.7),
                0 0 60px rgba(0,255,136,.08),
                inset 0 1px 0 rgba(255,255,255,.06);
            backdrop-filter: blur(22px) saturate(130%);
            -webkit-backdrop-filter: blur(22px) saturate(130%);
            isolation: isolate;
        }

        .container::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(90deg, rgba(0,255,136,.05), transparent 35%, rgba(34,211,238,.035)),
                repeating-linear-gradient(0deg, transparent 0 31px, rgba(0,255,136,.018) 32px);
            z-index: -1;
        }

        .form-container {
            position: relative !important;
            inset: auto !important;
            width: auto !important;
            height: 100% !important;
            display: flex !important;
            align-items: center;
            justify-content: center;
            padding: 48px;
            background: transparent !important;
            transform: none !important;
            transition: opacity .35s ease, visibility .35s ease;
        }

        .form-container form {
            width: min(340px, 100%);
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 14px;
        }

        .form-container h1 {
            margin: 0 0 4px;
            font-size: clamp(2rem, 4vw, 2.65rem);
            letter-spacing: -.04em;
            color: var(--white);
        }

        .form-container > form > span {
            margin-bottom: 10px;
            color: var(--muted);
            font-size: .9rem;
        }

        .form-container input {
            width: 100%;
            height: 50px;
            padding: 0 16px;
            border: 1px solid rgba(148,163,184,.18);
            border-radius: 12px;
            outline: none;
            color: var(--white);
            background: rgba(0,0,0,.28);
            font-size: .94rem;
            transition: .25s ease;
        }

        .form-container input::placeholder { color: #668078; }

        .form-container input:focus {
            border-color: rgba(0,255,136,.7);
            background: rgba(0,255,136,.035);
            box-shadow: 0 0 0 4px rgba(0,255,136,.07), 0 0 24px rgba(0,255,136,.08);
        }

        .form-container .input {
            width: 100%;
            height: 50px;
            margin-top: 4px;
            border: 0;
            border-radius: 12px;
            cursor: pointer;
            color: #001b0e;
            background: linear-gradient(135deg, #00ff88, #8affc9);
            font-weight: 800;
            letter-spacing: .06em;
            box-shadow: 0 10px 30px rgba(0,255,136,.16);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
        }

        .form-container .input:hover {
            transform: translateY(-2px);
            filter: brightness(1.06);
            box-shadow: 0 14px 34px rgba(0,255,136,.25);
        }

        #forgotLink {
            align-self: flex-end;
            margin-top: -2px;
            color: #68e7bc;
            font-size: .82rem;
            text-decoration: none;
        }

        #forgotLink:hover { color: #fff; }

        .sign-up {
            grid-column: 2;
            grid-row: 1;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .sign-in {
            grid-column: 1;
            grid-row: 1;
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .container.register-mode .sign-up {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .container.register-mode .sign-in {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .toggle-container {
            position: absolute !important;
            inset: 0 0 0 50% !important;
            width: 50% !important;
            height: 100% !important;
            z-index: 10;
            overflow: hidden;
            pointer-events: none;
            transition: transform .55s cubic-bezier(.22,.8,.2,1);
        }

        .toggle {
            position: absolute !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            display: block !important;
            transform: none !important;
        }

        /* Register card video background */
        .toggle-panel.toggle-right {
            overflow: hidden !important;
            isolation: isolate;
            padding: 0 !important;
            align-items: stretch !important;
            justify-content: stretch !important;
        }

        .register-card-video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            z-index: 0;
            pointer-events: none;
            filter: saturate(1.05) contrast(1.05) brightness(.72);
            transform: scale(1.015);
        }

        .register-card-video-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            background:
                linear-gradient(135deg, rgba(0, 18, 12, .86), rgba(0, 255, 136, .08) 48%, rgba(0, 0, 0, .82)),
                radial-gradient(circle at 70% 30%, rgba(0, 255, 136, .22), transparent 45%);
            pointer-events: none;
        }

        .register-card-content {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            padding: 54px;
            text-align: left;
        }

        .toggle-panel {
            position: absolute !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            display: flex !important;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            padding: 54px;
            text-align: left;
            pointer-events: auto;
            background:
                linear-gradient(145deg, rgba(0,255,136,.13), rgba(2,25,18,.88) 58%, rgba(0,0,0,.72)),
                rgba(2,18,12,.8);
            border-left: 1px solid rgba(0,255,136,.2);
        }

        .toggle-panel::before {
            content: "CODE MAZE";
            margin-bottom: 30px;
            color: var(--green);
            font-family: "Courier New", monospace;
            font-weight: 800;
            letter-spacing: .25em;
            font-size: .8rem;
            text-shadow: 0 0 18px rgba(0,255,136,.45);
        }

        .register-card-content h1 {
            margin: 0 0 12px;
        }

        .register-card-content p {
            max-width: 290px;
            margin: 0 0 28px;
            color: #b7d5ca;
            line-height: 1.65;
            font-size: .92rem;
        }

        .toggle-panel h1 {
            margin: 0 0 12px;
            font-size: 2.25rem;
            letter-spacing: -.04em;
            color: #f5fff9;
        }

        .toggle-panel p {
            max-width: 290px;
            margin: 0 0 28px;
            color: #9bb7ac;
            line-height: 1.65;
            font-size: .92rem;
        }

        .toggle-panel button.hidden {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            min-width: 138px;
            height: 46px;
            padding: 0 22px;
            border: 1px solid rgba(0,255,136,.5);
            border-radius: 11px;
            color: #dffff0;
            background: rgba(0,255,136,.06);
            cursor: pointer;
            font-weight: 750;
            transition: .22s ease;
        }

        .toggle-panel button.hidden:hover {
            color: #001b0e;
            background: var(--green);
            box-shadow: 0 0 28px rgba(0,255,136,.25);
            transform: translateY(-2px);
        }

        .toggle-left { display: none !important; }

        .container.register-mode .toggle-container {
            left: 0 !important;
            right: auto !important;
        }

        .container.register-mode .toggle-panel.toggle-right {
            display: none !important;
        }

        .container.register-mode .toggle-panel.toggle-left {
            display: flex !important;
            border-left: 0;
            border-right: 1px solid rgba(0,255,136,.2);
        }

        .container.register-mode .toggle-panel::before {
            content: "SECURE ACCESS";
        }

        .toast {
            z-index: 50 !important;
            border: 1px solid rgba(0,255,136,.22) !important;
            background: rgba(3,15,11,.88) !important;
            backdrop-filter: blur(16px);
            box-shadow: 0 16px 40px rgba(0,0,0,.35);
        }

        @media (max-width: 760px) {
            body { overflow-y: auto; padding: 20px 0; }
            .container {
                height: auto;
                min-height: 680px;
                grid-template-columns: 1fr;
                border-radius: 22px;
            }

            .form-container {
                grid-column: 1 !important;
                grid-row: 1 !important;
                min-height: 680px;
                padding: 38px 28px;
            }

            .toggle-container {
                display: none !important;
            }

            .sign-up { display: none !important; }
            .container.register-mode .sign-up { display: flex !important; }
            .container.register-mode .sign-in { display: none !important; }
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
            right: 120px;
            top: 59%;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
        }
    </style>
    <script>
        const password = document.getElementById("loginPassword");
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

    <script>
        
        // Matrix rain background — visual only.
        (() => {
            const canvas = document.getElementById('matrixCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            const chars = 'アァカサタナハマヤャラワ0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ<>/{}[]$#@';
            const fontSize = 15;
            let drops = [];

            function resize() {
                const dpr = Math.min(window.devicePixelRatio || 1, 2);
                canvas.width = Math.floor(innerWidth * dpr);
                canvas.height = Math.floor(innerHeight * dpr);
                canvas.style.width = innerWidth + 'px';
                canvas.style.height = innerHeight + 'px';
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

                const columns = Math.ceil(innerWidth / fontSize);
                drops = Array.from({length: columns}, () => Math.random() * (innerHeight / fontSize));
            }

            function draw() {
                ctx.fillStyle = 'rgba(1, 6, 4, 0.085)';
                ctx.fillRect(0, 0, innerWidth, innerHeight);
                ctx.font = `${fontSize}px "Courier New", monospace`;

                for (let i = 0; i < drops.length; i++) {
                    const char = chars[Math.floor(Math.random() * chars.length)];
                    const x = i * fontSize;
                    const y = drops[i] * fontSize;
                    const bright = Math.random() > .92;

                    ctx.fillStyle = bright ? 'rgba(180,255,220,.9)' : 'rgba(0,255,120,.58)';
                    ctx.fillText(char, x, y);

                    if (y > innerHeight && Math.random() > .975) drops[i] = 0;
                    drops[i] += .72 + Math.random() * .35;
                }
                requestAnimationFrame(draw);
            }

            resize();
            addEventListener('resize', resize);
            draw();
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('container');
            const registerBtn = document.getElementById('registerBtn');
            const loginBtn2 = document.getElementById('loginBtn2');

            if (!container || !registerBtn || !loginBtn2) return;

            registerBtn.addEventListener('click', function (e) {
                e.preventDefault();
                container.classList.add('register-mode');
            });

            loginBtn2.addEventListener('click', function (e) {
                e.preventDefault();
                container.classList.remove('register-mode');
            });
        });
    </script>

   


</body>
</html>