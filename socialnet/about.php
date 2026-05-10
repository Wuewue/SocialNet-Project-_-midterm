<?php
// ============================================================
// File: socialnet/about.php
// Description: Static about page displaying project info,
//              student name, and student number.
// ============================================================

session_start();

// ── Auth Guard ───────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit;
}

$activePage = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | SocialNet</title>
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
                <h2>About</h2>
                <p>Project information and credits</p>
            </div>

            <div class="about-card">
                <!-- Banner -->
                <div class="about-banner">
                    <div class="about-banner-pattern"></div>
                </div>

                <!-- Body -->
                <div class="about-body">
                    <h2>SocialNet</h2>
                    <p class="subtitle">
                        A social networking web application built as a university project,
                        featuring user authentication, profile management, and a clean
                        Instagram-inspired interface.
                    </p>

                    <!-- Student Info -->
                    <div class="info-row">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div>
                            <div class="info-label">Student Name</div>
                            <div class="info-value">Your Full Name Here</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </div>
                        <div>
                            <div class="info-label">Student Number</div>
                            <div class="info-value">123456789</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                                <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                            </svg>
                        </div>
                        <div>
                            <div class="info-label">Subject / Course</div>
                            <div class="info-value">Web Application Development</div>
                        </div>
                    </div>

                    <div class="info-row">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <div>
                            <div class="info-label">Submission Date</div>
                            <div class="info-value"><?= date('F j, Y') ?></div>
                        </div>
                    </div>

                    <!-- Tech Stack Badges -->
                    <div class="tech-badges">
                        <span class="badge">PHP</span>
                        <span class="badge">MySQL</span>
                        <span class="badge">Nginx</span>
                        <span class="badge">Ubuntu Linux</span>
                        <span class="badge">HTML5</span>
                        <span class="badge">CSS3</span>
                        <span class="badge">Session Auth</span>
                        <span class="badge">Bcrypt</span>
                    </div>
                </div>
            </div>

        </div>
    </main>

</div>

</body>
</html>
