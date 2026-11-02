<?php
function send_email($conn, $to, $subject, $body, $orderId = null) {
    $headers = "From: OBJEK <no-reply@objek.sg>\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";

    // missing/faulty mail server must still produce a failed email-log entry
    $sent = false;
    try {
        $sent = @mail($to, $subject, $body, $headers);
    } catch (Throwable $error) {
        error_log('OBJEK email attempt failed: ' . $error->getMessage());
    }

    $sentFlag = $sent ? 1 : 0;
    $stmt = $conn->prepare(
        'INSERT INTO email_log (order_id, to_email, subject, body, sent) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('isssi', $orderId, $to, $subject, $body, $sentFlag);
    $stmt->execute();
    $stmt->close();

    return $sent;
}

// read the receipt from the committed order, so the email matches saved totals
function send_order_acknowledgement($conn, $orderId) {
    $stmt = $conn->prepare('SELECT email, first_name, subtotal, shipping, total FROM orders WHERE id = ?');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$order) {
        throw new RuntimeException('Saved order not found for acknowledgement.');
    }

    $stmt = $conn->prepare('SELECT product_name, variant, unit_price, quantity FROM order_items WHERE order_id = ? ORDER BY id');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $body = 'Hi ' . $order['first_name'] . ",\n\n"
        . 'Thank you. Your OBJEK order #' . $orderId . " is confirmed.\n\n";
    foreach ($items as $item) {
        $body .= $item['product_name'] . ' (' . $item['variant'] . ') x ' . $item['quantity']
            . ' - S$' . number_format((float) $item['unit_price'] * (int) $item['quantity'], 2) . "\n";
    }
    $body .= "\nSubtotal: S$" . number_format((float) $order['subtotal'], 2)
        . "\nDelivery: S$" . number_format((float) $order['shipping'], 2)
        . "\nTotal: S$" . number_format((float) $order['total'], 2)
        . "\n\nDemo payment: no money was charged."
        . "\nTrack your order on the OBJEK tracking page using your order number and this email address.\n";

    // acknowledgements are transactional; email_updates controls future updates
    return send_email($conn, $order['email'], 'OBJEK order #' . $orderId . ' confirmed', $body, $orderId);
}
