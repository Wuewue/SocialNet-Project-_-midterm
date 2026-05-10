<?php
// ============================================================
// File: socialnet/profile.php
// Description: Displays a user's profile (username, fullname,
//              description). Accepts an optional `?owner=`
//              query string to view another user's profile.
//              Defaults to the logged-in user's profile.
// ============================================================

session_start();

// ── Auth Guard ───────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit;
}

require_once '../db_connect.php';

// ── Determine whose profile to display ───────────────────────
// If ?owner= is set, strip and use it; otherwise default to self.
$requestedOwner = trim($_GET['owner'] ?? '');

if (!empty($requestedOwner)) {
    // View another user's profile
    $stmt = $conn->prepare(
        "SELECT username, fullname, description FROM account WHERE username = ? LIMIT 1"
    );
    $stmt->bind_param('s', $requestedOwner);
} else {
    // Default: view own profile
    $currentUserId = $_SESSION['user_id'];
    $stmt = $conn->prepare(
        "SELECT username, fullname, description FROM account WHERE Id = ? LIMIT 1"
    );
    $stmt->bind_param('i', $currentUserId);
}

$stmt->execute();
$result  = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();
$conn->close();

// ── 404 handling ─────────────────────────────────────────────
$notFound = ($profile === null);

// Is this the logged-in user's own profile?
$isOwnProfile = !$notFound &&
                ($profile['username'] === $_SESSION['username']);

$activePage = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $notFound ? 'User Not Found' : htmlspecialchars($profile['username']) ?> | SocialNet
    </title>
    <link rel="stylesheet" href="/socialnet/style.css">
</head>
<body>

<div class="app-wrapper">

    <!-- Sidebar Navigation -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-inner">

            <?php if ($notFound): ?>
                <!-- ── User not found ── -->
                <div class="page-header">
                    <h2>Profile</h2>
                    <p>User not found</p>
                </div>
                <div class="profile-placeholder">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <p>The user <strong>@<?= htmlspecialchars($requestedOwner) ?></strong> does not exist.</p>
                    <a href="/socialnet/index.php" class="btn btn-outline btn-sm mt-3">← Back to Home</a>
                </div>

            <?php else: ?>
                <!-- ── Profile Card ── -->
                <div class="profile-header">

                    <!-- Avatar -->
                    <div class="profile-header-avatar">
                        <div class="user-avatar-ring">
                            <div class="user-avatar xl">
                                <?= strtoupper(substr($profile['username'], 0, 1)) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="profile-header-info">
                        <div class="username-row">
                            <h2><?= htmlspecialchars($profile['username']) ?></h2>
                            <?php if ($isOwnProfile): ?>
                                <a href="/socialnet/setting.php" class="btn btn-outline btn-sm">
                                    Edit Profile
                                </a>
                            <?php else: ?>
                                <a href="/socialnet/index.php" class="btn btn-outline btn-sm">
                                    ← Back
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Placeholder stats for visual fidelity -->
                        <div class="profile-stats">
                            <div class="stat-item">
                                <span class="stat-num">0</span>
                                <span class="stat-label">posts</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-num">0</span>
                                <span class="stat-label">followers</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-num">0</span>
                                <span class="stat-label">following</span>
                            </div>
                        </div>

                        <div class="fullname"><?= htmlspecialchars($profile['fullname']) ?></div>

                        <?php if (!empty($profile['description'])): ?>
                            <div class="bio"><?= htmlspecialchars($profile['description']) ?></div>
                        <?php else: ?>
                            <div class="bio text-muted" style="font-style:italic;">
                                No bio yet.
                                <?php if ($isOwnProfile): ?>
                                    <a href="/socialnet/setting.php" style="color:var(--accent);">Add one →</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Posts Placeholder Grid -->
                <div class="profile-placeholder">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    <p>No posts yet.</p>
                </div>

            <?php endif; ?>

        </div>
    </main>

</div>

</body>
</html>
