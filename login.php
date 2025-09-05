<?php
session_start();
include 'db.php'; // database connection

// Configuration
$MAX_ATTEMPTS = 5;
$LOCKOUT_TIME = 900; // 15 minutes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Fetch user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // Check if account is locked
        if ($user['failed_attempts'] >= $MAX_ATTEMPTS && 
            strtotime($user['last_failed']) > (time() - $LOCKOUT_TIME)) {
            die("Account locked. Try again later.");
        }

        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            // Reset failed attempts
            $stmt = $pdo->prepare("UPDATE users SET failed_attempts=0 WHERE username=?");
            $stmt->execute([$username]);

            $_SESSION['user'] = $username;
            echo "Login successful. Welcome, " . htmlspecialchars($username);
        } else {
            // Increment failed attempts
            $stmt = $pdo->prepare("UPDATE users SET failed_attempts=failed_attempts+1, last_failed=NOW() WHERE username=?");
            $stmt->execute([$username]);
            die("Invalid credentials. Please try again.");
        }
    } else {
        die("Invalid credentials. User not found.");
    }
}
?>