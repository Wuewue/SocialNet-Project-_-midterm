<?php
// ============================================================
// File: navbar.php
// Description: Shared sidebar navigation component.
//              Determines the "active" page from a $activePage
//              variable set by the parent before including.
//              Requires $_SESSION['username'] to be set.
// ============================================================

// Determine which nav item is active (set by parent page)
$activePage = $activePage ?? '';

// Get initials for avatar
$uname      = $_SESSION['username']  ?? '?';
$fname      = $_SESSION['fullname']  ?? 'User';
$initial    = strtoupper(substr($uname, 0, 1));
?>

<nav class="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo-mark">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
            </svg>
        </div>
        <span class="logo-text">SocialNet</span>
    </div>

    <!-- Navigation Links -->
    <div class="sidebar-nav">

        <a href="/socialnet/index.php"
           class="nav-item <?= $activePage === 'home' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24">
                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <span>Home</span>
        </a>

        <a href="/socialnet/profile.php"
           class="nav-item <?= $activePage === 'profile' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            <span>Profile</span>
        </a>

        <a href="/socialnet/setting.php"
           class="nav-item <?= $activePage === 'setting' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
            <span>Settings</span>
        </a>

        <a href="/socialnet/about.php"
           class="nav-item <?= $activePage === 'about' ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <span>About</span>
        </a>

        <a href="/socialnet/signout.php"
           class="nav-item signout">
            <svg class="nav-icon" viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            <span>Sign Out</span>
        </a>

    </div>

    <!-- Logged-in User -->
    <div class="sidebar-user">
        <a href="/socialnet/profile.php" class="sidebar-user-card">
            <div class="user-avatar">
                <?= htmlspecialchars($initial) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="uname"><?= htmlspecialchars($uname) ?></div>
                <div class="fname"><?= htmlspecialchars($fname) ?></div>
            </div>
        </a>
    </div>
</nav>
