<?php
require_once __DIR__ . '/includes/db.php';

// Opening this URL alone must not sign someone out.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: account.php', true, 303);
    exit;
}

// Only accept the sign-out form from this browser session.
$token = $_POST['csrf_token'] ?? '';
$expectedToken = $_SESSION['csrf_token'] ?? '';
if (!is_string($token) || $token === '' || $expectedToken === ''
    || !hash_equals($expectedToken, $token)) {
    http_response_code(403);
    exit('Your sign-out form expired. Return to Account and try again.');
}

// Forget the login and private order permissions, but keep shopping items.
$cart = $_SESSION['cart'] ?? [];
$_SESSION = ['cart' => $cart];
session_regenerate_id(true);

header('Location: account.php', true, 303);
exit;