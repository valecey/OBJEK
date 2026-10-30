
<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$orderId = filter_input(
    INPUT_GET,
    'order_id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$_SESSION['confirmation_order_ids'][$orderId] = true;
$canView = $orderId
    && !empty($_SESSION['confirmation_order_ids'][$orderId]);
