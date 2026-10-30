<?php
function send_email($conn, $to, $subject, $body, $orderId = null) {
    $headers = "From: OBJEK <no-reply@objek.sg>\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";

    $sent = @mail($to, $subject, $body, $headers);

    $sentFlag = $sent ? 1 : 0;
    $stmt = $conn->prepare(
        'INSERT INTO email_log (order_id, to_email, subject, body, sent) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('isssi', $orderId, $to, $subject, $body, $sentFlag);
    $stmt->execute();

    return $sent;
}
