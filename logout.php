<?php
session_start();

// Only accept POST to logout (prevents CSRF via link clicking)
// You can add a CSRF token for better security.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Unset all session variables
    $_SESSION = [];

    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    // Destroy session
    session_destroy();
}

header('Location: login.php');
exit();
