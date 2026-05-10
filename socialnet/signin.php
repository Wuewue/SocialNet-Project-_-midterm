<?php
// ============================================================
// File: socialnet/signin.php
// Description: Login page. Validates credentials against the
//              account table using password_verify(). On
//              success, stores user data in the session and
//              redirects to the home page.
// ============================================================

session_start();

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header('Location: /socialnet/index.php');
    exit;
}

require_once '../db_connect.php';

$error = '';

// ── Handle form submission ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter your username and password.';
    } else {
        // Fetch user record by username (prepared statement)
        $stmt = $conn->prepare(
            "SELECT Id, username, fullname, password FROM account WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify the submitted password against the stored hash
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Store essential user info in session
                $_SESSION['user_id']  = $user['Id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];

                $stmt->close();
                $conn->close();

                header('Location: /socialnet/index.php');
                exit;
            } else {
                $error = 'Incorrect username or password.';
            }
        } else {
            // Generic message — do not reveal whether username exists
            $error = 'Incorrect username or password.';
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | SocialNet</title>
    <link rel="stylesheet" href="/socialnet/style.css">
</head>
<body>

<div class="auth-page">
    <div class="auth-card">

        <!-- Logo -->
        <div class="auth-logo">
            <div class="logo-mark-lg">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 5a3 3 0 110 6 3 3 0 010-6zm0 13c-2.67 0-5.02-1.22-6.59-3.12C6.72 15.75 9.21 15 12 15s5.28.75 6.59 1.88C17.02 18.78 14.67 20 12 20z"/>
                </svg>
            </div>
            <h1>SocialNet</h1>
            <p>Sign in to see photos and updates from friends.</p>
        </div>

        <!-- Error Alert -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Sign In Form -->
        <form method="POST" action="/socialnet/signin.php" autocomplete="off">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text"
                       id="username"
                       name="username"
                       placeholder="Enter your username"
                       required
                       autofocus
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password"
                       id="password"
                       name="password"
                       placeholder="Enter your password"
                       required>
            </div>

            <button type="submit" class="btn btn-primary mt-2">
                Sign In
            </button>

        </form>

        <div class="auth-divider mt-3">or</div>

        <p class="text-center text-sm text-muted">
            Don't have an account? Contact your administrator.
        </p>

    </div>
</div>

</body>
</html>
