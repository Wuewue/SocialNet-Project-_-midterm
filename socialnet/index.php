<?php
// ============================================================
// File: socialnet/index.php
// Description: Home feed page. Redirects to signin if no
//              active session. Displays the logged-in user's
//              info and a list of other registered users with
//              links to their profiles.
// ============================================================

session_start();

// ── Auth Guard ───────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit;
}

require_once '../db_connect.php';

// ── Fetch all users EXCEPT the currently logged-in one ───────
$currentUserId = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT Id, username, fullname, description
     FROM account
     WHERE Id != ?
     ORDER BY fullname ASC"
);
$stmt->bind_param('i', $currentUserId);
$stmt->execute();
$result     = $stmt->get_result();
$otherUsers = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();

// ── Template Variable ────────────────────────────────────────
$activePage = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | SocialNet</title>
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
                <h2>Welcome back, <?= htmlspecialchars($_SESSION['fullname']) ?> 👋</h2>
                <p>@<?= htmlspecialchars($_SESSION['username']) ?> · Here's who else is on SocialNet</p>
            </div>

            <?php if (!empty($otherUsers)): ?>

                <!-- Stories-style quick discover row -->
                <div class="discover-row">
                    <?php foreach ($otherUsers as $user): ?>
                        <a href="/socialnet/profile.php?owner=<?= urlencode($user['username']) ?>"
                           class="discover-item"
                           title="<?= htmlspecialchars($user['fullname']) ?>">
                            <div class="user-avatar-ring">
                                <div class="user-avatar" style="width:60px;height:60px;font-size:24px;">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                            </div>
                            <span><?= htmlspecialchars($user['username']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- User list cards -->
                <p class="feed-section-label">All Members</p>
                <div class="user-list">
                    <?php foreach ($otherUsers as $user): ?>
                        <div class="user-card">
                            <div class="user-avatar-ring">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                            </div>
                            <div class="user-card-info">
                                <div class="uname">@<?= htmlspecialchars($user['username']) ?></div>
                                <div class="fname"><?= htmlspecialchars($user['fullname']) ?></div>
                                <?php if (!empty($user['description'])): ?>
                                    <div class="text-sm text-muted mt-1" style="overflow:hidden;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;">
                                        <?= htmlspecialchars($user['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="/socialnet/profile.php?owner=<?= urlencode($user['username']) ?>"
                               class="btn btn-outline btn-sm">
                                View
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="profile-placeholder">
                    <svg viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                    <p>No other users yet. Ask your admin to add more accounts.</p>
                </div>
            <?php endif; ?>

        </div>
    </main>

</div>

</body>
</html>
