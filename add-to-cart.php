<?php
// for "add to bag" on product.php.
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listing.php');
    exit;
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$qty       = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);

if (!$productId) {
    header('Location: listing.php');
    exit;
}

// checks current stock
$stmt = $conn->prepare('SELECT stock FROM products WHERE id = ?');
$stmt->bind_param('i', $productId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    header('Location: listing.php');
    exit;
}

if ($qty === false || $qty === null || $qty < 1 || $qty > 10) {
    header('Location: product.php?id=' . $productId . '&error=qty');
    exit;
}

// check if quantity exceeds stock
$alreadyInBag = $_SESSION['cart'][$productId] ?? 0;
if ($alreadyInBag + $qty > (int) $row['stock']) {
    header('Location: product.php?id=' . $productId . '&error=stock');
    exit;
}

$_SESSION['cart'][$productId] = $alreadyInBag + $qty;

// refresh the page doesnt add the item twice
header('Location: product.php?id=' . $productId . '&added=' . $qty);
exit;
