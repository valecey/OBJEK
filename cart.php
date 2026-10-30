<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cart-to-checkout.php';

$cart = load_cart_items($conn, $_SESSION['cart'] ?? []);
$items = $cart['items'];
$issues = $cart['issues'];
$totals = calculate_cart_totals($items);
$canCheckout = count($items) > 0 && count($issues) === 0;

$pageTitle = 'Your bag | OBJEK';
include __DIR__ . '/includes/header.php';
?>
<main id="main" class="wrap section objek-cart">
  <div class="section__head">
    <h1 class="page-title">Your bag</h1>
    <a class="text-link" href="listing.php">Continue shopping &nearr;</a>
  </div>

  <?php if ($issues): ?>
    <div class="notice" role="alert">
      <p>Please resolve these bag issues before checkout:</p>
      <ul>
        <?php foreach ($issues as $issue): ?>
          <li><?= e($issue) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!$items && !$issues): ?>
    <section class="empty" aria-labelledby="empty-bag-title">
      <h2 id="empty-bag-title">Your bag is empty</h2>
      <p>Add a product to see your items and transaction amount here.</p>
      <a class="btn btn--lime" href="listing.php">Browse products &rarr;</a>
    </section>
  <?php else: ?>
    <?php if ($items): ?>
      <div class="objek-cart__table" role="region" aria-label="Bag items" tabindex="0">
        <table class="spec-table">
          <caption class="visually-hidden">Products in your bag</caption>
          <thead>
            <tr>
              <th scope="col">Product</th>
              <th scope="col">Price</th>
              <th scope="col">Quantity</th>
              <th scope="col">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <a href="product.php?id=<?= $item['id'] ?>"><?= e($item['name']) ?></a>
                  <p class="product-card__variant"><?= e($item['variant']) ?></p>
                  <?php if ($item['issue'] !== ''): ?>
                    <p class="field-error"><?= e($item['issue']) ?></p>
                  <?php endif; ?>
                </td>
                <td><?= money($item['unit_price_cents'] / 100) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= money($item['line_subtotal_cents'] / 100) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <section class="objek-cart__summary" aria-labelledby="transaction-title">
      <h2 class="section__title" id="transaction-title">Transaction amount</h2>
      <p class="objek-cart__subtotal">
        <span>Product subtotal (incl. GST)</span>
        <strong><?= money($totals['subtotal_cents'] / 100) ?></strong>
      </p>
      <p class="objek-cart__note">Delivery charges are not included in this subtotal.</p>
    </section>
  <?php endif; ?>

  <div class="objek-cart__actions">
    <?php if ($canCheckout): ?>
      <a class="btn btn--lime" href="checkout.php">Proceed to checkout &rarr;</a>
    <?php else: ?>
      <button class="btn btn--lime" type="button" disabled>Proceed to checkout</button>
    <?php endif; ?>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
