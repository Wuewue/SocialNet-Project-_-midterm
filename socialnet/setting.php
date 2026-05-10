<?php
// ============================================================
// File: socialnet/setting.php
// Description: Settings page. Allows the logged-in user to
//              update their profile description (bio).
//              Protected by session auth guard.
// ============================================================

session_start();

// ── Auth Guard ───────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit;
}

require_once '../db_connect.php';

$message = '';
$msgType = '';
$userId  = $_SESSION['user_id'];

// ── Fetch current user data ───────────────────────────────────
$stmt = $conn->prepare(
    "SELECT username, fullname, description FROM account WHERE Id = ? LIMIT 1"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

// ── Handle form submission ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description'] ?? '');

    // Update the description column
    $stmtUp = $conn->prepare(
        "UPDATE account SET description = ? WHERE Id = ?"
    );
    $stmtUp->bind_param('si', $description, $userId);

    if ($stmtUp->execute()) {
        $message        = 'Profile updated successfully!';
        $msgType        = 'success';
        $user['description'] = $description; // Reflect update in form
    } else {
        $message = 'Failed to update profile. Please try again.';
        $msgType = 'error';
    }

    $stmtUp->close();
}

$conn->close();

$activePage = 'setting';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | SocialNet</title>
    <link rel="stylesheet" href="/socialnet/style.css">
</head>
<body>

<div class="app-wrapper">

    <!-- Sidebar Navigation -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-inner">

            <!-- Page Header -->
            <div class="page-header">
                <h2>Settings</h2>
                <p>Manage your account information</p>
            </div>

            <div class="settings-card">

                <!-- User Preview Header -->
                <div class="settings-card-header">
                    <div class="avatar-section">
                        <div class="user-avatar-ring">
                            <div class="user-avatar lg">
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </div>
                        </div>
                        <div>
                            <h3><?= htmlspecialchars($user['fullname']) ?></h3>
                            <p>@<?= htmlspecialchars($user['username']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Settings Form -->
                <div class="settings-card-body">

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $msgType ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/socialnet/setting.php">

                        <!-- Read-only fields for reference -->
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text"
                                   value="<?= htmlspecialchars($user['username']) ?>"
                                   disabled
                                   style="background:#f5f5f5;cursor:not-allowed;color:var(--text-secondary);">
                        </div>

                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text"
                                   value="<?= htmlspecialchars($user['fullname']) ?>"
                                   disabled
                                   style="background:#f5f5f5;cursor:not-allowed;color:var(--text-secondary);">
                        </div>

                        <!-- Editable bio field -->
                        <div class="form-group">
                            <label for="description">Bio / Description</label>
                            <textarea id="description"
                                      name="description"
                                      placeholder="Write a short bio about yourself..."
                                      style="min-height:140px;"><?= htmlspecialchars($user['description'] ?? '') ?></textarea>
                        </div>

                        <div style="display:flex;gap:12px;">
                            <button type="submit" class="btn btn-primary" style="flex:1;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                                    <polyline points="17 21 17 13 7 13 7 21"/>
                                    <polyline points="7 3 7 8 15 8"/>
                                </svg>
                                Save Changes
                            </button>
                            <a href="/socialnet/profile.php" class="btn btn-outline">
                                View Profile
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </main>

</div>

</body>
</html>
