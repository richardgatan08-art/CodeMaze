<?php
// create_player_account.php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/auth.php';

// Only authenticated administrators can create player accounts.
requireAdminApi();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed.'
    ]);
    exit;
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 180) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Enter a valid email address (maximum 180 characters).'
    ]);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'Password must be at least 8 characters.'
    ]);
    exit;
}

try {
    // Uses the existing PDO connection from db.php.
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection unavailable.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    if ($passwordHash === false) {
        throw new RuntimeException('Password hashing failed.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password) VALUES (:email, :password)'
    );

    $stmt->execute([
        ':email' => $email,
        ':password' => $passwordHash
    ]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Player account created successfully.'
    ]);

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'An account with that email already exists.'
        ]);
    } else {
        error_log('Create player account failed: ' . $e->getMessage());

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Could not save the account to the database.'
        ]);
    }
} catch (Throwable $e) {
    error_log('Create player account failed: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Could not create the account. Check the database connection.'
    ]);
}