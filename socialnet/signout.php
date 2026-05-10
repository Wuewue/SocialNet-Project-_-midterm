<?php
// ============================================================
// File: socialnet/signout.php
// Description: Destroys the active session completely and
//              redirects the user to the sign-in page.
//              This is a "page" rather than a pure redirect
//              so it can show a brief goodbye message.
// ============================================================

session_start();

// 1. Clear all session variables
$_SESSION = [];

// 2. Destroy the session cookie on the client side
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destroy the session data on the server
session_destroy();

// 4. Redirect to sign-in page
header('Location: /socialnet/signin.php');
exit;
?>
