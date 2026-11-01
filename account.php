<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare(
    'SELECT id, name, email, password_hash
     FROM users
     WHERE email = ?'
);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'register') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || mb_strlen($name) > 100) {
        $message = 'Enter a name of up to 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 255) {
        $message = 'Enter a valid email address.';
    } elseif (strlen($password) < 8 || strlen($password) > 72) {
        $message = 'Use a password between 8 and 72 bytes.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare(
                'INSERT INTO users
                 (name, email, password_hash, is_admin)
                 VALUES (?, ?, ?, 0)'
            );

            $stmt->bind_param('sss', $name, $email, $passwordHash);
            $stmt->execute();
            $stmt->close();

            $message = 'Account created. You can now sign in.';
        } catch (mysqli_sql_exception $error) {
            if ($error->getCode() === 1062) {
                $message = 'That email is already registered.';
            } else {
                error_log($error->getMessage());
                $message = 'Could not create the account. Please try again.';
            }
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
        <p>Welcome, <?= e($_SESSION['user_name'] ?? '') ?>!</p>
        <p>You are signed in.</p>
    <?php else: ?>
    <?php if ($message !== ''): ?>
    <p><?= e($message) ?></p>
    <?php endif; ?>

    <h2>Sign in</h2>
    <form method="post" action="account.php" >
        <input type="hidden" name="action" value="login">

        <p>
            <label for="login-email">Email</label>
            <input type="email" name="email" id="login-email" autocomplete="username" required>
        </p>
        <p>
            <label for="login-password">Password</label>
            <input type="password" name="password" id="login-password" autocomplete="current-password" required>
        </p>
        <p>
            <button type="submit" class="btn btn--lime">Sign in</button>
        </p>   
    </form>

    <h2>Create account</h2>
    <form method="post" action="account.php">
        <input type="hidden" name="action" value="register">

        <p>
            <label for="register-name">Name</label>
            <input id="register-name" name="name"
                   maxlength="100" autocomplete="name" required>
        </p>
        <p>
            <label for="register-email">Email</label>
            <input type="email" id="register-email" name="email"
                   maxlength="255" autocomplete="email" required>
        </p>
        <p>
            <label for="register-password">Password</label>
            <input type="password" id="register-password" name="password"
                   minlength="8" maxlength="72"
                   autocomplete="new-password" required>
        </p>
        <p>
            <button class="btn btn--lime" type="submit">Create account</button>
        </p>
    </form>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
