<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cart-to-checkout.php';
require_once __DIR__ . '/includes/checkout-functions.php';

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$postedToken = is_string($_POST['checkout_token'] ?? null) ? $_POST['checkout_token'] : '';
// The PHP session serializes requests; a repeated successful POST only redirects.
if ($isPost && $postedToken !== '' && isset($_SESSION['completed_checkout_tokens'][$postedToken])) {
    header('Location: order_confirmation.php?order_id=' . (int) $_SESSION['completed_checkout_tokens'][$postedToken], true, 303);
    exit;
}

$cart = load_cart_items($conn, $_SESSION['cart'] ?? []);
if (!$cart['items'] || $cart['issues']) {
    header('Location: cart.php', true, 303);
    exit;
}
$items = $cart['items'];
$subtotalCents = calculate_cart_totals($items)['subtotal_cents'];
$errors = [];
$values = ['email' => (string) ($_SESSION['user_email'] ?? ''), 'first_name' => '',
    'last_name' => '', 'address' => '', 'postal_code' => '', 'phone' => '', 'email_updates' => 0];

if ($isPost) {
    foreach (['email', 'first_name', 'last_name', 'address', 'postal_code', 'phone'] as $field) {
        $values[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
    }
    $values['email_updates'] = isset($_POST['email_updates']) ? 1 : 0;
    $errors = checkout_errors($values);
    $expectedToken = $_SESSION['checkout_token'] ?? '';
    if ($postedToken === '' || $expectedToken === '' || !hash_equals($expectedToken, $postedToken)) {
        $errors[] = 'Your checkout form expired. Please review it and submit again.';
    }
    if (!$errors) {
        try {
            // The display snapshot prevents silently charging a changed price.
            if (($_SESSION['checkout_cart'] ?? null) !== ($_SESSION['cart'] ?? [])) {
                throw new DomainException('Your bag changed. Review the summary and submit again.');
            }
            $userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]) ?: null;
            $orderId = place_demo_order($conn, $_SESSION['cart'], $values, $userId,
                $_SESSION['checkout_subtotal_cents'] ?? -1);

            // These values are the handoff to your friend's confirmation page.
            $_SESSION['last_order_id'] = $orderId;
            $_SESSION['confirmation_order_ids'][$orderId] = true;
            $_SESSION['completed_checkout_tokens'][$postedToken] = $orderId;
            $_SESSION['cart'] = [];
            unset($_SESSION['checkout_token'], $_SESSION['checkout_cart'], $_SESSION['checkout_subtotal_cents']);
            header('Location: order_confirmation.php?order_id=' . $orderId, true, 303);
            exit;
        } catch (DomainException $error) {
            $errors[] = $error->getMessage();
        } catch (Throwable $error) {
            error_log('OBJEK checkout failed: ' . $error->getMessage());
            $errors[] = 'Your order could not be saved. Your bag has been kept; please try again.';
        }
    }
    // Refresh the summary following stock/price changes; keep all entered values.
    $cart = load_cart_items($conn, $_SESSION['cart'] ?? []);
    $items = $cart['items'];
    $subtotalCents = calculate_cart_totals($items)['subtotal_cents'];
    $errors = array_merge($errors, $cart['issues']);
}
if (empty($_SESSION['checkout_token'])) {
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
}
$_SESSION['checkout_cart'] = $_SESSION['cart'];
$_SESSION['checkout_subtotal_cents'] = $subtotalCents;
$pageTitle = 'Checkout | OBJEK';
$hideDeliveryNote = true;
include __DIR__ . '/includes/header.php';
?>
<main id="main" class="wrap section objek-checkout">
  <div class="section__head">
    <h1 class="page-title">Checkout</h1>
    <a class="text-link" href="cart.php">Back to your bag &nearr;</a>
  </div>
  <?php if ($errors): ?>
    <div class="notice" role="alert">
      <p>Please check the following:</p>
      <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>
  <form action="checkout.php" method="post" class="objek-checkout__layout">
    <input type="hidden" name="checkout_token" value="<?= e($_SESSION['checkout_token']) ?>">
    <section aria-labelledby="delivery-title">
      <h2 class="section__title" id="delivery-title">Delivery details</h2>
      <div class="objek-checkout__fields">
        <?php
        $fields = [
            'email' => ['Email', 'email', 'email', 255],
            'first_name' => ['First name', 'text', 'given-name', 50],
            'last_name' => ['Last name', 'text', 'family-name', 50],
            'address' => ['Delivery address', 'text', 'street-address', 255],
            'postal_code' => ['Singapore postal code', 'text', 'postal-code', 6],
            'phone' => ['Phone', 'tel', 'tel', 20],
        ];
        foreach ($fields as $name => [$label, $type, $autocomplete, $maxLength]): ?>
          <div class="objek-checkout__field">
            <label class="field__label" for="<?= e($name) ?>"><?= e($label) ?></label>
            <input id="<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>"
                   autocomplete="<?= e($autocomplete) ?>" maxlength="<?= $maxLength ?>"
                   value="<?= e($values[$name]) ?>" required
                   <?= $name === 'postal_code' ? 'inputmode="numeric" pattern="[0-9]{6}" title="Enter six digits"' : '' ?>>
          </div>
        <?php endforeach; ?>
      </div>
      <label class="choice objek-checkout__updates">
        <input type="checkbox" name="email_updates" value="1" <?= $values['email_updates'] ? 'checked' : '' ?>>
        Receive order status updates by email
      </label>
    </section>
    <aside class="panel objek-checkout__summary" aria-labelledby="summary-title">
      <div class="panel__bar"><h2 id="summary-title">Order summary</h2></div>
      <div class="panel__body">
        <ul class="objek-checkout__items">
          <?php foreach ($items as $item): ?>
            <li>
              <div><strong><?= e($item['name']) ?></strong>
                <p class="product-card__variant"><?= e($item['variant']) ?></p>
                <p>Quantity: <?= $item['quantity'] ?></p>
              </div>
              <span><?= money($item['line_subtotal_cents'] / 100) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <dl class="objek-checkout__totals">
          <div><dt>Subtotal (incl. GST)</dt><dd><?= money($subtotalCents / 100) ?></dd></div>
          <div><dt>Delivery (demo)</dt><dd><?= money(0) ?></dd></div>
          <div class="objek-checkout__total"><dt>Total</dt><dd><?= money($subtotalCents / 100) ?></dd></div>
        </dl>
        <p class="notice">Demo payment &mdash; no money will be charged. Placing this order simulates a successful payment.</p>
        <button class="btn btn--lime objek-checkout__submit" type="submit"
                <?= (!$items || $cart['issues']) ? 'disabled' : '' ?>>Place demo order &rarr;</button>
      </div>
    </aside>
  </form>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
