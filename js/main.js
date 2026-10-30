<?php
require 'includes/db.php';
require 'includes/functions.php';
require 'includes/mail.php';

$topics = [
    'order'   => 'An order I placed',
    'product' => 'A product question',
    'returns' => 'Returns or warranty',
    'other'   => 'Something else',
];

// faq: questions + answer
$faqs = [
    'How much is delivery, and how long does it take?' =>
        'Delivery is free on orders above S$100, and S$8 below that. We deliver anywhere in Singapore in 1 to 2 working days. Orders placed before 2pm on a working day leave our warehouse the same day.',
    'How do I track my order?' =>
        'Go to Track your order and enter your order number and the email address you used at checkout. You will see every step, from confirmed to delivered. We also email you each time the status changes.',
    'Can I change my address or cancel my order?' =>
        'Yes, as long as the order has not been packed yet. Send us a message with your order number and we will update or cancel it. Once an order is packed, it can no longer be changed.',
    'What is your returns policy?' =>
        'You can return any unused item in its original packaging within 14 days of delivery for a full refund. Send us a message with your order number to start a return.',
    'What does the one-year warranty cover?' =>
        'Every product is covered for one year from delivery against faults in materials and workmanship. If something stops working, we repair or replace it. Accidental damage, such as drops or water damage, is not covered.',
    'Which payment methods do you accept?' =>
        'We accept credit and debit cards and online payment, handled securely by our payment partner. We never see or store your full card details.',
    'Do I need an account to order?' =>
        'No, you can check out as a guest. With an account, your details are filled in for you and you can see all your past orders in one place.',
    'What does "Only 3 left" mean?' =>
        'It shows how many we have in stock right now. Stock is only set aside for you once your order is paid, so popular items can sell out while they are in your bag.',
];

// terms and conditions
$terms = [
    'About these terms' =>
        'These terms apply to every order placed on objek.sg. By placing an order, you agree to them. OBJEK is an online electronics shop based in Singapore.',
    'Prices and payment' =>
        'All prices are in Singapore dollars (S$) and include GST. Payment is taken when you place your order. If your payment is not successful, the order is cancelled and nothing is charged.',
    'Orders and stock' =>
        'Your order is confirmed once payment is successful, and we email you a confirmation. If an item turns out to be unavailable, we will contact you and refund that item in full.',
    'Delivery' =>
        'We deliver within Singapore only. Delivery times are estimates, and we are not responsible for delays caused by events outside our control. Please check your order when it arrives and tell us about any damage within 48 hours.',
    'Returns and refunds' =>
        'Unused items in their original packaging can be returned within 14 days of delivery. Refunds are made to your original payment method within 7 working days of us receiving the item. Delivery charges are refunded only if the item was faulty or wrong.',
    'Warranty' =>
        'All products come with a one-year warranty from the date of delivery. It covers faults in materials and workmanship, not accidental damage, misuse or normal wear.',
    'Your account' =>
        'If you create an account, keep your password private. You are responsible for orders placed using your account.',
    'Your information' =>
        'We use your name, address, phone number and email only to process and deliver your order and to send you order updates. We do not sell or share your details with anyone except our delivery and payment partners.',
    'Changes to these terms' =>
        'We may update these terms from time to time. The version shown here applies to orders placed from its last updated date.',
];

$values = ['name' => '', 'email' => '', 'topic' => '', 'order_id' => '', 'message' => ''];
$errors = [];

// fills in name and email if logged in
if (!empty($_SESSION['user_id'])) {
    $values['name']  = $_SESSION['user_name']  ?? '';
    $values['email'] = $_SESSION['user_email'] ?? '';
}

// form validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $unused) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    if ($values['name'] === '') {
        $errors['name'] = 'Enter your name.';
    } elseif (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 100) {
        $errors['name'] = 'Your name should be 2 to 100 characters.';
    }

    if ($values['email'] === '') {
        $errors['email'] = 'Enter your email address.';
    } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email']) > 255) {
        $errors['email'] = 'Enter an email address like name@example.com.';
    }

    if (!array_key_exists($values['topic'], $topics)) {
        $errors['topic'] = 'Choose what your message is about.';
    }

    if ($values['order_id'] !== '' && !preg_match('/^\d{4,7}$/', $values['order_id'])) {
        $errors['order_id'] = 'Order numbers are 4 to 7 digits, e.g. 1004.';
    } elseif ($values['topic'] === 'order' && $values['order_id'] === '') {
        $errors['order_id'] = 'Enter your order number so we can find your order.';
    }

    if (mb_strlen($values['message']) < 10) {
        $errors['message'] = 'Write at least 10 characters so we know how to help.';
    } elseif (mb_strlen($values['message']) > 1000) {
        $errors['message'] = 'Keep your message under 1000 characters.';
    }

    if (!$errors) {
        $orderId = $values['order_id'] === '' ? null : (int) $values['order_id'];

        $stmt = $conn->prepare(
            'INSERT INTO contact_messages (name, email, topic, order_id, message) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssis', $values['name'], $values['email'], $values['topic'], $orderId, $values['message']);
        $stmt->execute();
        $messageId = $conn->insert_id;

        $body = "Hi {$values['name']},\n\n"
              . "Thanks for getting in touch. We have received your message (reference #{$messageId}) "
              . "and will reply to this email address within one working day.\n\n"
              . "Your message:\n{$values['message']}\n\n"
              . "OBJEK";
        send_email($conn, $values['email'], "We've received your message (#{$messageId})", $body);

        header('Location: contact.php?sent=' . $messageId);
        exit;
    }
}

$sentId = filter_input(INPUT_GET, 'sent', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$pageTitle = 'Contact us | OBJEK';
$pageDescription = 'Questions about an order, a product or a return? Send OBJEK a message and we will reply within one working day.';
$hideDeliveryNote = true;
include 'includes/header.php';
?>
<main id="main" class="wrap">

  <div class="contact">

    <section class="contact__info" aria-labelledby="contact-title">
      <p class="eyebrow">Help / Contact</p>
      <h1 class="display display--md" id="contact-title">Get in touch <span>Replies in a day</span></h1>
      <p class="description">
        Questions about an order, a product or a return? Send us a message and a real person
        will get back to you by email within one working day.
      </p>

      <figure class="window contact__visual">
        <span class="window__tag">SUPPORT.IMG</span>
        <img src="images/contact.svg" alt="Illustration of a shop counter with a message bubble" width="600" height="400">
      </figure>

      <dl class="contact__details">
        <div>
          <dt>Visit</dt>
          <dd>50 Nanyang Avenue, Singapore 639798<br>Mon to Sat, 11am to 8pm</dd>
        </div>
        <div>
          <dt>Email</dt>
          <dd>hello@objek.sg</dd>
        </div>
        <div>
          <dt>Already ordered?</dt>
          <dd><a class="text-link" href="track-order.php">Track your order &nearr;</a></dd>
        </div>
        <div>
          <dt>Quick answers</dt>
          <dd class="contact__jump">
            <a class="text-link" href="#faq">FAQ &darr;</a>
            <a class="text-link" href="#terms">Terms &amp; conditions &darr;</a>
          </dd>
        </div>
      </dl>
    </section>

    <section class="panel contact__panel" aria-label="Send us a message">
      <div class="panel__bar"><span>NEW MESSAGE</span><span><?= $sentId ? 'SENT' : 'DRAFT' ?></span></div>

      <?php if ($sentId): ?>
        <div class="panel__body contact__sent" role="status">
          <h2>Message sent.</h2>
          <p>Thanks for writing to us. Your reference number is <strong>#<?= $sentId ?></strong>.
             We've emailed you a copy and will reply within one working day.</p>
          <div class="contact__sent-actions">
            <a class="btn btn--lime" href="listing.php">Keep shopping</a>
            <a class="btn btn--outline" href="contact.php">Send another message</a>
          </div>
        </div>
      <?php else: ?>

        <?php if ($errors): ?>
          <p class="notice contact__notice" role="alert">Please fix the highlighted fields and send again.</p>
        <?php endif; ?>

        <form class="panel__body contact__form" id="contact-form" action="contact.php" method="post" novalidate>

          <div class="field">
            <label class="field__label" for="c-name">Your name</label>
            <input id="c-name" type="text" name="name" value="<?= e($values['name']) ?>"
                   required minlength="2" maxlength="100" autocomplete="name">
            <p class="field-error" id="c-name-error" <?= isset($errors['name']) ? '' : 'hidden' ?>><?= e($errors['name'] ?? '') ?></p>
          </div>

          <div class="field">
            <label class="field__label" for="c-email">Email address</label>
            <input id="c-email" type="email" name="email" value="<?= e($values['email']) ?>"
                   required maxlength="255" autocomplete="email" placeholder="you@example.com">
            <p class="field-error" id="c-email-error" <?= isset($errors['email']) ? '' : 'hidden' ?>><?= e($errors['email'] ?? '') ?></p>
          </div>

          <div class="contact__row">
            <div class="field">
              <label class="field__label" for="c-topic">What's it about?</label>
              <select id="c-topic" name="topic" required>
                <option value="">Choose a topic</option>
                <?php foreach ($topics as $value => $label): ?>
                  <option value="<?= e($value) ?>" <?= $values['topic'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <p class="field-error" id="c-topic-error" <?= isset($errors['topic']) ? '' : 'hidden' ?>><?= e($errors['topic'] ?? '') ?></p>
            </div>

            <div class="field">
              <label class="field__label" for="c-order">Order number <span class="field__optional">(if you have one)</span></label>
              <input id="c-order" type="text" name="order_id" value="<?= e($values['order_id']) ?>"
                     inputmode="numeric" pattern="\d{4,7}" maxlength="7" placeholder="e.g. 1004">
              <p class="field-error" id="c-order-error" <?= isset($errors['order_id']) ? '' : 'hidden' ?>><?= e($errors['order_id'] ?? '') ?></p>
            </div>
          </div>

          <div class="field">
            <label class="field__label" for="c-message">Message</label>
            <textarea id="c-message" name="message" rows="6" required minlength="10" maxlength="1000"><?= e($values['message']) ?></textarea>
            <p class="field__hint"><span id="c-count"><?= mb_strlen($values['message']) ?></span> / 1000</p>
            <p class="field-error" id="c-message-error" <?= isset($errors['message']) ? '' : 'hidden' ?>><?= e($errors['message'] ?? '') ?></p>
          </div>

          <button class="btn btn--lime contact__submit" type="submit">Send message</button>
        </form>
      <?php endif; ?>
    </section>

  </div>

  <!-- faq -->
  <section class="help-section" id="faq" aria-labelledby="faq-title">
    <div class="help-section__head">
      <p class="eyebrow">Help / FAQ</p>
      <h2 class="display display--md display--split" id="faq-title">Common <span>questions</span></h2>
      <p class="description">Can't find your answer? <a class="text-link" href="#contact-title">Send us a message &uarr;</a></p>
    </div>

    <div class="faq">
      <?php foreach ($faqs as $question => $answer): ?>
        <details class="faq__item">
          <summary><?= e($question) ?></summary>
          <p><?= e($answer) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- t&c -->
  <section class="help-section" id="terms" aria-labelledby="terms-title">
    <div class="help-section__head">
      <p class="eyebrow">Help / Terms</p>
      <h2 class="display display--md display--split" id="terms-title">Terms &amp; <span>conditions</span></h2>
      <p class="description">Last updated 29 October 2026.</p>
    </div>

    <ol class="terms">
      <?php foreach ($terms as $heading => $text): ?>
        <li>
          <h3><?= e($heading) ?></h3>
          <p><?= e($text) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>

</main>
<?php include 'includes/footer.php'; ?>
