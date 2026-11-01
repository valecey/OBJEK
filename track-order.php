<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Allow up to 20 lookups per browser session every 15 minutes.
if (time() - ($_SESSION['tracking_started'] ?? 0) >= 900) {
    $_SESSION['tracking_started'] = time();
    $_SESSION['tracking_count'] = 0;
}

$message = '';
$order = null;
$history = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = filter_input(
        INPUT_POST,
        'order_id',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $email = trim($_POST['email'] ?? '');

    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token)
        || !hash_equals($_SESSION['csrf_token'], $token)) {
        $message = 'Your form expired. Refresh and try again.';
    } elseif ($_SESSION['tracking_count'] >= 20) {
        $message = 'Too many lookups. Please try again in 15 minutes.';
    } elseif (!$orderId || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Enter a valid order number and email.';
    } else {
        $_SESSION['tracking_count']++;
        $stmt = $conn->prepare(
            'SELECT id, status, payment_status, total, created_at
             FROM orders
             WHERE id = ? AND email = ?'
        );

        $stmt->bind_param('is', $orderId, $email);
        $stmt->execute();

        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

    if (!$order) {
        $message = 'No order matches those details.';
    } else {
        // Grant receipt access only after the number and email match.
        $_SESSION['confirmation_order_ids'][$order['id']] = true;

        $stmt = $conn->prepare(
            'SELECT status, note, created_at
            FROM order_status_history
            WHERE order_id = ?
            ORDER BY id'
        );

        $stmt->bind_param('i', $orderId);
        $stmt->execute();

        $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    }
}

$pageTitle = 'Track your order | OBJEK';
include __DIR__ . '/includes/header.php';
?>

<main id="main" class="wrap section">
    <h1 class="page-title">Track your order</h1>

    <form class="objek-form" action="track-order.php" method="post">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <div class="field">
            <label class="field__label" for="order-id">Order number</label>
            <input type="number" id="order-id" name="order_id" min="1" placeholder="1001"
                   value="<?= e($_POST['order_id'] ?? '') ?>" required>
        </div>
        <div class="field">
            <label class="field__label" for="order-email">Email used at checkout</label>
            <input type="email" id="order-email" name="email" maxlength="255" autocomplete="email"
                   value="<?= e($_POST['email'] ?? '') ?>" required>
        </div>
        <button class="btn btn--lime" type="submit">Find order</button>
    </form>

    <?php if ($message !== ''): ?>
        <p class="notice objek-form__message" role="alert"><?= e($message) ?></p>
    <?php endif; ?>

    <?php if ($order): ?>
        <section class="panel objek-track" aria-labelledby="track-title">
            <div class="panel__bar"><h2 class="objek-confirmation__heading" id="track-title">Order #<?= (int) $order['id'] ?></h2></div>
            <div class="panel__body">
                <p>Status: <strong><?= e(ucfirst($order['status'])) ?></strong></p>
                <p>Payment: <strong><?= e(ucfirst($order['payment_status'])) ?></strong></p>
                <p>Total: <?= money($order['total']) ?></p>
                <p>Placed: <?= e($order['created_at']) ?></p>

                <h3 class="field__label objek-track__heading">Status history</h3>
                <?php if (!$history): ?>
                    <p>No status updates yet.</p>
                <?php else: ?>
                    <ol class="objek-track__history">
                        <?php foreach ($history as $entry): ?>
                            <li>
                                <strong><?= e(ucfirst($entry['status'])) ?></strong>
                                &mdash; <?= e($entry['created_at']) ?>
                                <?php if ($entry['note'] !== ''): ?>
                                    <p><?= e($entry['note']) ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>

                <a class="btn btn--outline objek-track__link" href="order-confirmation.php?order_id=<?= (int) $order['id'] ?>">View order details</a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
