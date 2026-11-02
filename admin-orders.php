<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/mail.php';

// visitors must sign in first
if (empty($_SESSION['user_id'])) {
    header('Location: account.php');
    exit;
}

// check the user's current role in the database
$stmt = $conn->prepare(
    'SELECT is_admin FROM users WHERE id = ?'
);

$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// customer account cannot access staff tools
if (!$user || (int) $user['is_admin'] !== 1) {
    http_response_code(403);
    exit('Staff access is required.');
}

// Orders move forward one step, or can be cancelled before they are packed.
$nextStatuses = [
    'confirmed' => 'packed',
    'packed' => 'shipped',
    'shipped' => 'delivered',
];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$error = '';
$message = $_SESSION['admin_order_message'] ?? '';
unset($_SESSION['admin_order_message']);

// Handle updates before loading the list or outputting any HTML.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]);
    $newStatus = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
    $note = is_string($_POST['note'] ?? null) ? trim($_POST['note']) : '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Your form expired. Refresh and try again.';
    } elseif (!$orderId || mb_strlen($note) > 255) {
        $error = 'Choose a valid order and a note of up to 255 characters.';
    } else {
        $conn->begin_transaction();
        try {
            // lock the row so two staff requests cannot advance it together
            $stmt = $conn->prepare('SELECT status, payment_status, email, first_name, email_updates FROM orders WHERE id = ? FOR UPDATE');
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $currentOrder = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$currentOrder) {
                throw new DomainException('Order not found.');
            }
            $nextStatus = $nextStatuses[$currentOrder['status']] ?? null;
            $canCancel = $currentOrder['status'] === 'confirmed' && $newStatus === 'cancelled';
            $canAdvance = $nextStatus !== null && $newStatus === $nextStatus;
            if ($currentOrder['payment_status'] !== 'paid' || (!$canCancel && !$canAdvance)) {
                throw new DomainException('That status change is not allowed. Review the current order status.');
            }

            if ($canCancel) {
                // locked confirmed order can be cancelled only once
                // group by colour in case the same variant occurs on several lines
                $stmt = $conn->prepare('SELECT colour_id, SUM(quantity) AS quantity
                    FROM order_items WHERE order_id = ? GROUP BY colour_id ORDER BY colour_id');
                $stmt->bind_param('i', $orderId);
                $stmt->execute();
                $orderedColours = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                if (!$orderedColours) {
                    throw new DomainException('This order has no saved items. Review it before cancelling.');
                }

                $restore = $conn->prepare('UPDATE product_colours SET stock = stock + ? WHERE id = ?');
                foreach ($orderedColours as $item) {
                    $colourId = (int) $item['colour_id'];
                    $quantity = (int) $item['quantity'];
                    if ($colourId < 1 || $quantity < 1) {
                        throw new DomainException('An item has a missing colour or invalid quantity. Review the order before cancelling.');
                    }
                    $restore->bind_param('ii', $quantity, $colourId);
                    $restore->execute();
                    if ($restore->affected_rows !== 1) {
                        throw new DomainException('A colour is no longer available. Stock could not be restored.');
                    }
                }
                $restore->close();
                if ($note === '') {
                    $note = 'Cancelled by staff. Stock restored. Demo payment; no real refund was processed.';
                }
            }

            $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $newStatus, $orderId);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare('INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)');
            $stmt->bind_param('iss', $orderId, $newStatus, $note);
            $stmt->execute();
            $stmt->close();

            // only opted-in customers receive a demo status-notification record
            if ((int) $currentOrder['email_updates'] === 1) {
                $subject = 'OBJEK order #' . $orderId . ' is ' . $newStatus;
                $body = 'Hi ' . $currentOrder['first_name'] . ",\n\n"
                    . 'Your OBJEK order #' . $orderId . ' is now ' . $newStatus . ".\n";
                if ($note !== '') { $body .= "\n" . $note . "\n"; }
                $body .= "\nDemo notification: recorded only; no email was sent. No real payment or refund was processed.\n";
                log_email($conn, $currentOrder['email'], $subject, $body, $orderId);
            }

            // save status, history, notification log, and restored stock together
            $conn->commit();
            $_SESSION['admin_order_message'] = 'Order #' . $orderId . ' updated to ' . $newStatus . '.';
            header('Location: admin-orders.php', true, 303);
            exit;
        } catch (DomainException $exception) {
            $conn->rollback();
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            $conn->rollback();
            error_log($exception->getMessage());
            $error = 'The update could not be saved. Please try again.';
        }
    }
}

// load the newest 100 orders after staff access has been checked
$stmt = $conn->prepare(
    'SELECT id, first_name, last_name, email,
            total, payment_status, status, created_at
     FROM orders
     ORDER BY id DESC
     LIMIT 100'
);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = 'Manage orders | OBJEK';
include __DIR__ . '/includes/header.php';
?>

<main id="main" class="wrap section">
    <h1 class="page-title">Manage orders</h1>
    <p>You are signed in as staff.</p>
    <p>Demo email notifications are logged for opted-in customers. No emails are sent.</p>
    <p>Confirmed orders can be cancelled and their stock restored. Packed, shipped, and delivered orders cannot be cancelled here.</p>
    <?php if ($error !== ''): ?><p class="notice" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($message !== ''): ?><p class="notice" role="status"><?= e($message) ?></p><?php endif; ?>
    <?php if (!$orders): ?>
        <p>No orders yet.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="spec-table">
                <thead>
                    <tr>
                        <th scope="col">Order</th>
                        <th scope="col">Customer</th>
                        <th scope="col">Total</th>
                        <th scope="col">Payment</th>
                        <th scope="col">Status</th>
                        <th scope="col">Placed</th>
                        <th scope="col">Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= (int) $order['id'] ?></td>
                            <td>
                                <?= e($order['first_name'] . ' ' . $order['last_name']) ?><br>
                                <?= e($order['email']) ?>
                            </td>
                            <td><?= money($order['total']) ?></td>
                            <td><?= e($order['payment_status']) ?></td>
                            <td><?= e($order['status']) ?></td>
                            <td><?= e($order['created_at']) ?></td>
                            <td>
                                <?php $nextStatus = $nextStatuses[$order['status']] ?? null; ?>
                                <?php if ($nextStatus !== null && $order['payment_status'] === 'paid'): ?>
                                    <form action="admin-orders.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                        <label for="status-<?= (int) $order['id'] ?>">Next status</label>
                                        <select id="status-<?= (int) $order['id'] ?>" name="status" required>
                                            <option value="<?= e($nextStatus) ?>"><?= e(ucfirst($nextStatus)) ?></option>
                                            <?php if ($order['status'] === 'confirmed'): ?>
                                                <option value="cancelled">Cancel order (restore stock)</option>
                                            <?php endif; ?>
                                        </select>
                                        <label for="note-<?= (int) $order['id'] ?>">Customer-visible note (optional)</label>
                                        <input id="note-<?= (int) $order['id'] ?>" type="text" name="note" maxlength="255">
                                        <button class="btn btn--outline" type="submit">Update</button>
                                    </form>
                                <?php else: ?>
                                    No update available.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
