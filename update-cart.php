<?php
require_once __DIR__ . '/includes/db.php';

// Visiting this URL alone must not clear the cart.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php', true, 303);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
$expectedToken = $_SESSION['csrf_token'] ?? '';
if (!is_string($token) || $token === '' || $expectedToken === ''
    || !hash_equals($expectedToken, $token)) {
    http_response_code(403);
    exit('Your form expired. Return to your bag and try again.');
}

// These actions affect only this browser session's shopping items.
$action = $_POST['action'] ?? '';
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    $_SESSION['cart_message'] = 'Your cart has been cleared.';
} elseif ($action === 'remove') {
    // Cart keys are colour IDs, so removing one colour keeps the other variants.
    $colourId = filter_input(INPUT_POST, 'colour_id', FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]);
    if (!$colourId) {
        http_response_code(400);
        exit('Choose a valid cart item.');
    }
    if (isset($_SESSION['cart'][$colourId])) {
        unset($_SESSION['cart'][$colourId]);
        $_SESSION['cart_message'] = 'Item removed from your cart.';
    } else {
        $_SESSION['cart_message'] = 'That item is no longer in your cart.';
    }
} elseif ($action === 'update') {
    $colourId = filter_input(INPUT_POST, 'colour_id', FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]);
    $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 10]]);

    if (!$colourId || !isset($_SESSION['cart'][$colourId])) {
        $_SESSION['cart_message'] = 'That item is not in your cart.';
    } elseif (!$qty) {
        $_SESSION['cart_message'] = 'Choose a whole quantity from 1 to 10.';
    } else {
        // Stock belongs to the selected colour, not just the product.
        $stmt = $conn->prepare('SELECT stock FROM product_colours WHERE id = ?');
        $stmt->bind_param('i', $colourId);
        $stmt->execute();
        $colour = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$colour || $qty > (int) $colour['stock']) {
            $_SESSION['cart_message'] = 'That quantity is unavailable.';
        } else {
            // Replace the quantity. No database stock is reserved or changed.
            $_SESSION['cart'][$colourId] = $qty;
            $_SESSION['cart_message'] = 'Quantity updated.';
        }
    }
} else {
    http_response_code(400);
    exit('Unknown cart action.');
}

header('Location: cart.php', true, 303);
exit;