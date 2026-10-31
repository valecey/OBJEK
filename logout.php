<?php
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: account.php', true, 303);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
$expectedToken = $_SESSION['csrf_token'] ?? '';
if (!is_string($token) || $token === '' || $expectedToken === ''
    || !hash_equals($expectedToken, $token)) {
    http_response_code(403);
    exit('Your sign-out form expired. Return to Account and try again.');
}

$cart = $_SESSION['cart'] ?? [];
$_SESSION = ['cart' => $cart];
session_regenerate_id(true);

header('Location: account.php', true, 303);
exit;