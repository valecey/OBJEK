<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// 1. Read the order ID. Only checkout can authorize an order for this session.
$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]);
$order = null;
$items = [];
$errorMessage = '';

if (!$orderId) {
    http_response_code(400);
    $errorMessage = 'Please open the confirmation link provided after checkout.';
} elseif (empty($_SESSION['confirmation_order_ids'][$orderId])) {
    http_response_code(403);
    $errorMessage = 'We cannot display this order in your current shopping session.';
} else {
    // 2. Fetch the saved customer details and totals, not the now-empty cart.
    $stmt = $conn->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        http_response_code(404);
        $errorMessage = 'We could not find this order.';
    } else {
        // 3. These are the names and prices recorded at the time of purchase.
        $stmt = $conn->prepare('SELECT product_name, variant, unit_price, quantity
            FROM order_items WHERE order_id = ? ORDER BY id');
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

$pageTitle = 'Order confirmation | OBJEK';
$hideDeliveryNote = true;
include __DIR__ . '/includes/header.php';
?>
<main id="main" class="wrap section objek-cart objek-confirmation">
  <?php if ($errorMessage !== ''): ?>
    <h1 class="section__title">Order unavailable</h1>
    <p class="notice objek-confirmation__message" role="alert"><?= e($errorMessage) ?></p>
    <a class="btn btn--lime" href="listing.php">Continue shopping &rarr;</a>
  <?php else: ?>
    <p class="eyebrow">Order #<?= (int) $order['id'] ?></p>
    <h1 class="page-title">Thank you for your order</h1>
    <p class="objek-confirmation__message">Your order has been placed. Keep your order reference for future enquiries.</p>
    <p>Placed: <?= e($order['created_at']) ?></p>
    <p>Order status: <strong><?= e(ucfirst($order['status'])) ?></strong></p>
    <p>Payment status: <strong><?= e(ucfirst($order['payment_status'])) ?></strong></p>
    <p class="notice objek-confirmation__message">Demo payment &mdash; no money was charged.</p>

    <div class="objek-cart__table" role="region" aria-label="Ordered products" tabindex="0">
      <table class="spec-table">
        <caption class="visually-hidden">Products in order #<?= (int) $order['id'] ?></caption>
        <thead>
          <tr><th scope="col">Product</th><th scope="col">Price</th><th scope="col">Quantity</th><th scope="col">Subtotal</th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td><?= e($item['product_name']) ?><p class="product-card__variant"><?= e($item['variant']) ?></p></td>
              <td><?= money($item['unit_price']) ?></td>
              <td><?= (int) $item['quantity'] ?></td>
              <td><?= money((float) $item['unit_price'] * (int) $item['quantity']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="objek-checkout__layout">
      <section class="panel" aria-labelledby="delivery-title">
        <div class="panel__bar"><h2 class="objek-confirmation__heading" id="delivery-title">Delivery details</h2></div>
        <div class="panel__body">
          <p><?= e($order['first_name'] . ' ' . $order['last_name']) ?></p>
          <p class="objek-confirmation__address"><?= e($order['address']) ?></p>
          <p>Singapore <?= e($order['postal_code']) ?></p>
          <p>Email: <?= e($order['email']) ?></p>
          <p>Phone: <?= e($order['phone']) ?></p>
        </div>
      </section>
      <section class="panel" aria-labelledby="totals-title">
        <div class="panel__bar"><h2 class="objek-confirmation__heading" id="totals-title">Transaction amount</h2></div>
        <div class="panel__body">
          <dl class="objek-checkout__totals">
            <div><dt>Subtotal (incl. GST)</dt><dd><?= money($order['subtotal']) ?></dd></div>
            <div><dt>Delivery</dt><dd><?= money($order['shipping']) ?></dd></div>
            <div class="objek-checkout__total"><dt>Total</dt><dd><?= money($order['total']) ?></dd></div>
          </dl>
        </div>
      </section>
    </div>
    <div class="objek-cart__actions"><a class="btn btn--lime" href="listing.php">Continue shopping &rarr;</a></div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
