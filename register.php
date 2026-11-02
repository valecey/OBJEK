<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Signed-in users can return to their existing account
if (!empty($_SESSION['user_id'])) {
    header('Location: account.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'register') {

    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $message = 'Your registration form expired. Refresh and try again.';
    } elseif ($name === '' || mb_strlen($name) > 100) {
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

$pageTitle = 'Create account | OBJEK';
include __DIR__ . '/includes/header.php';
?>

<main id="main" class="wrap section">
    <h1 class="page-title">Create account</h1>
    <?php if ($message !== ''): ?>
        <p class="notice objek-form__message" role="status"><?= e($message) ?></p>
    <?php endif; ?>

    <form class="objek-form" method="post" action="register.php">
        <input type="hidden" name="action" value="register">
        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
        <div class="field">
            <label class="field__label" for="register-name">Name</label>
            <input type="text" id="register-name" name="name" maxlength="100" autocomplete="name" required>
        </div>
        <div class="field">
            <label class="field__label" for="register-email">Email</label>
            <input type="email" id="register-email" name="email" maxlength="255" autocomplete="email" required>
        </div>
        <div class="field">
            <label class="field__label" for="register-password">Password</label>
            <input type="password" id="register-password" name="password" minlength="8" maxlength="72"
                   autocomplete="new-password" required>
        </div>
        <button class="btn btn--lime" type="submit">Create account</button>
    </form>

    <p class="objek-form__message"><a class="text-link" href="account.php">Back to sign in &nearr;</a></p>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
