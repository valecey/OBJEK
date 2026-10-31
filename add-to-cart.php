<?php
// for "add to bag" on product.php.
require 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listing.php');
    exit;
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$colourId  = filter_input(INPUT_POST, 'colour_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$qty       = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT);

if (!$productId) {
    header('Location: listing.php');
    exit;
}
$back = 'product.php?id=' . $productId;

if (!$colourId) {
    header('Location: ' . $back . '&error=colour');
    exit;
}

$stmt = $conn->prepare('SELECT stock FROM product_colours WHERE id = ? AND product_id = ?');
$stmt->bind_param('ii', $colourId, $productId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    header('Location: ' . $back . '&error=colour');
    exit;
}
$back .= '&colour=' . $colourId;

if ($qty === false || $qty === null || $qty < 1 || $qty > 10) {
    header('Location: ' . $back . '&error=qty');
    exit;
}

// check if quantity exceeds the stock of the selected color
$alreadyInBag = $_SESSION['cart'][$colourId] ?? 0;
if ($alreadyInBag + $qty > (int) $row['stock']) {
    header('Location: ' . $back . '&error=stock');
    exit;
}

$_SESSION['cart'][$colourId] = $alreadyInBag + $qty;

// refresh the page doesnt add the item twice
header('Location: ' . $back . '&added=' . $qty);
exit;
