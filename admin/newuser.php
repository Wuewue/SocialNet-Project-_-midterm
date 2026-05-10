<?php
// ============================================================
// File: admin/newuser.php
// Description: Admin-only page to create new user accounts.
//              Password is hashed with PHP's password_hash()
//              before being stored in the database.
//              NOTE: In production, protect this route with
//              HTTP Basic Auth or IP whitelisting in Nginx.
// ============================================================

require_once '../db_connect.php';

$message = '';
$msgType = '';

// ── Handle form submission ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize & retrieve inputs
    $username    = trim($_POST['username']    ?? '');
    $fullname    = trim($_POST['fullname']    ?? '');
    $password    = $_POST['password']         ?? '';
    $description = trim($_POST['description'] ?? '');

    // 2. Validate inputs
    if (empty($username) || empty($fullname) || empty($password)) {
        $message = 'Username, Full Name, and Password are required.';
        $msgType = 'error';
    } elseif (strlen($username) > 50) {
        $message = 'Username must be 50 characters or fewer.';
        $msgType = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
        $msgType = 'error';
    } else {
        // 3. Hash the password using bcrypt (cost=12)
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // 4. Insert using a prepared statement to prevent SQL injection
        $stmt = $conn->prepare(
            "INSERT INTO account (username, fullname, password, description)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('ssss', $username, $fullname, $hashedPassword, $description);

        if ($stmt->execute()) {
            $message = "User '@{$username}' created successfully!";
            $msgType = 'success';
        } else {
            // Check for duplicate username (MySQL error 1062)
            if ($conn->errno === 1062) {
                $message = "Username '@{$username}' is already taken.";
            } else {
                $message = 'Database error. Please try again.';
            }
            $msgType = 'error';
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
    <title>Admin — New User | SocialNet</title>
    <link rel="stylesheet" href="/socialnet/style.css">
</head>
<body>

<div class="admin-page">
    <div class="admin-card">

        <!-- Card Header -->
        <div class="admin-card-header">
            <div class="admin-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>
            <div>
                <h2>Admin Panel</h2>
                <p>Create a new SocialNet user</p>
            </div>
        </div>

        <!-- Card Body -->
        <div class="admin-card-body">

            <div class="admin-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Restricted Access
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $msgType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/admin/newuser.php" autocomplete="off">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text"
                           id="username"
                           name="username"
                           placeholder="e.g. john_doe"
                           maxlength="50"
                           required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text"
                           id="fullname"
                           name="fullname"
                           placeholder="e.g. John Doe"
                           maxlength="100"
                           required
                           value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password"
                           id="password"
                           name="password"
                           placeholder="Minimum 8 characters"
                           minlength="8"
                           required>
                </div>

                <div class="form-group">
                    <label for="description">Bio / Description <span class="text-muted">(optional)</span></label>
                    <textarea id="description"
                              name="description"
                              placeholder="A short bio for the user's profile..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" y1="8" x2="19" y2="14"/>
                        <line x1="22" y1="11" x2="16" y2="11"/>
                    </svg>
                    Create User
                </button>

            </form>
        </div>
    </div>
</div>

</body>
</html>
