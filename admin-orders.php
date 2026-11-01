<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

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

// Each order can move forward by one step. Cancellation is a separate feature.
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
            // Lock the row so two staff requests cannot advance it together.
            $stmt = $conn->prepare('SELECT status, payment_status FROM orders WHERE id = ? FOR UPDATE');
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $currentOrder = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$currentOrder) {
                throw new DomainException('Order not found.');
            }
            $nextStatus = $nextStatuses[$currentOrder['status']] ?? null;
            if ($currentOrder['payment_status'] !== 'paid' || $nextStatus === null || $newStatus !== $nextStatus) {
                throw new DomainException('That status change is not allowed. Review the current order status.');
            }

            $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $newStatus, $orderId);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare('INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)');
            $stmt->bind_param('iss', $orderId, $newStatus, $note);
            $stmt->execute();
            $stmt->close();

            // Save both the current status and its history together.
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
