<?php
session_start();
require_once 'db.php'; // Includes your PDO database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: index.php?error=empty_fields");
        exit;
    }

    try {
        // Query database for the user by email
        $stmt = $pdo->prepare("SELECT id, email, password FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Verify user exists and the password matches
        if ($user && password_verify($password, $user['password'])) {
            // Store user session data
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_email'] = $user['email'];

            // Redirect to your game or dashboard page on successful login
            header("Location: ../php/buttons.php"); 
            exit;
        } else {
            // Invalid credentials, redirect back with error parameter
            header("Location: index.php?error=invalid_credentials");
            exit;
        }
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit;
}
?>