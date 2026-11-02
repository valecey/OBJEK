<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $message = 'Your sign-in form expired. Refresh and try again.';
    } else {
        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

        $stmt = $conn->prepare('SELECT id, name, email, password_hash FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: account.php');
            exit;
        } else {
            $message = 'Email or password is incorrect.';
        }
    }
}
// past orders for the signed-in customer
$myOrders = [];
if (!empty($_SESSION['user_id'])) {
    $stmt = $conn->prepare(
        'SELECT id, status, total, created_at
         FROM orders
         WHERE user_id = ?
         ORDER BY created_at DESC'
    );
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $myOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // these are their own orders, so let them open the full order page
    foreach ($myOrders as $o) {
        $_SESSION['confirmation_order_ids'][$o['id']] = true;
    }
}

$pageTitle = 'Account | OBJEK';
include __DIR__ . '/includes/header.php';
?>

<main id="main" class="wrap section">
    <h1 class="page-title">Your account</h1>

    <?php if (!empty($_SESSION['user_id'])): ?>
        <p class="objek-form__message">Welcome, <?= e($_SESSION['user_name'] ?? '') ?>! You are signed in as <?= e($_SESSION['user_email'] ?? '') ?>.</p>

        <h2 class="section__title objek-account__heading">Your orders</h2>
        <?php if (!$myOrders): ?>
            <p>You haven't placed any orders yet. <a class="text-link" href="listing.php">Start shopping &nearr;</a></p>
        <?php else: ?>
            <div class="objek-cart__table" role="region" aria-label="Your orders" tabindex="0">
                <table class="spec-table">
                    <caption class="visually-hidden">Orders placed with this account</caption>
                    <thead>
                        <tr><th scope="col">Order</th><th scope="col">Placed</th><th scope="col">Status</th><th scope="col">Total</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myOrders as $o): ?>
                            <tr>
                                <td><a href="order-confirmation.php?order_id=<?= (int) $o['id'] ?>">#<?= (int) $o['id'] ?></a></td>
                                <td><?= e($o['created_at']) ?></td>
                                <td><?= e(ucfirst($o['status'])) ?></td>
                                <td><?= money($o['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- logout.php only accepts a form post with this token, so other sites can't sign people out -->
        <form class="objek-account__signout" action="logout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <button class="btn btn--outline" type="submit">Sign out</button>
        </form>
    <?php else: ?>
        <?php if ($message !== ''): ?>
            <p class="notice objek-form__message" role="alert"><?= e($message) ?></p>
        <?php endif; ?>

        <div class="objek-checkout__layout">
            <section aria-labelledby="signin-title">
                <h2 class="section__title" id="signin-title">Sign in</h2>
                <form class="objek-form" method="post" action="account.php">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <div class="field">
                        <label class="field__label" for="login-email">Email</label>
                        <input type="email" name="email" id="login-email" autocomplete="username" required>
                    </div>
                    <div class="field">
                        <label class="field__label" for="login-password">Password</label>
                        <input type="password" name="password" id="login-password" autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="btn btn--lime">Sign in</button>
                </form>
            </section>

            <section aria-labelledby="register-title">
                <h2 class="section__title" id="register-title">New to OBJEK?</h2>
                <p class="objek-form__message">Create an account to view your orders in one place.</p>
                <a class="btn btn--outline" href="register.php">Create account</a>
            </section>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
