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
    <title>Admin Register – CodeMaze</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        body {
            background-color: #0d1117;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        /* Video Background CSS */
        .video-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
        }

        /* Dark Overlay para mas madaling basahin ang form */
        .video-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: -1;
        }
        .register-card {
            background: rgba(18, 26, 20, 0.95);
            border: 1px solid #00ff66;
            box-shadow: 0 0 20px rgba(0, 255, 102, 0.2);
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            color: #fff;
            text-align: center;
        }
        .register-card h1 { margin-bottom: 8px; color: #00ff66; }
        .register-card p { font-size: 14px; color: #aaa; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; text-align: left; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 13px; color: #ccc; }
        .form-group input {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #222;
            background: #050b07;
            color: #fff;
            box-sizing: border-box;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #00ff66;
            border: none;
            color: #000;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-submit:hover { background: #00cc52; }
        .switch-link { margin-top: 20px; font-size: 14px; display: block; color: #00ff66; text-decoration: none; }
    </style>
</head>
<body>
    <!-- Background Video Element -->
    <video autoplay loop muted playsinline class="video-bg">
        <source src="../videos/awevid.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    
    <!-- Dark overlay over the video -->
    <div class="video-overlay"></div>

    <div class="register-card">
        <h1>Create Account</h1>
        <p>Register new admin account</p>
        <form id="signupForm" novalidate>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="signupEmail" placeholder="Enter email" required />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="signupPassword" placeholder="Min 6 characters" required />
            </div>
            <button type="submit" class="btn-submit" id="signupBtn">Register</button>
        </form>
        <a href="index.php" class="switch-link">Already have an account? Sign In</a>
    </div>
    <script src="../js/admin_login.js"></script>
</body>
</html>