<?php
function checkout_errors($values) {
    $errors = [];
    $limits = ['email' => 255, 'first_name' => 50, 'last_name' => 50,
        'address' => 255, 'postal_code' => 6, 'phone' => 20];
    foreach ($limits as $field => $limit) {
        if ($values[$field] === '' || mb_strlen($values[$field], 'UTF-8') > $limit) {
            $errors[] = 'Please enter a valid ' . str_replace('_', ' ', $field) . '.';
        }
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^\d{6}$/D', $values['postal_code'])) {
        $errors[] = 'Please enter a six-digit Singapore postal code.';
    }
    return array_unique($errors);
}

function place_demo_order($conn, $sessionCart, $values, $userId, $expectedSubtotal) {
    $conn->begin_transaction();
    try {
        if (!is_array($sessionCart) || !$sessionCart) {
            throw new DomainException('Your bag is empty.');
        }
        // lock ID order to prevent out of stock sales
        ksort($sessionCart, SORT_NUMERIC);
        $lock = $conn->prepare('SELECT id FROM products WHERE id = ? FOR UPDATE');
        foreach ($sessionCart as $id => $quantity) {
            if (filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                throw new DomainException('Your bag contains an invalid product.');
            }
            $lock->execute([$id]);
            $lock->get_result()->free();
        }
        $lock->close();
        //update cart session after lock
        $loaded = load_cart_items($conn, $sessionCart);
        if ($loaded['issues'] || !$loaded['items']) {
            throw new DomainException('Your bag has changed. Return to your bag to review stock and quantities.');
        }
        $items = $loaded['items'];
        $subtotalCents = calculate_cart_totals($items)['subtotal_cents'];
        if ($subtotalCents !== $expectedSubtotal) {
            throw new DomainException('Product prices have changed. Review the updated summary and submit again.');
        }
        $subtotal = number_format($subtotalCents / 100, 2, '.', '');
        $shipping = '0.00';
        $total = $subtotal;
        $order = $conn->prepare(
            "INSERT INTO orders (user_id, email, first_name, last_name, address, postal_code, phone,
             email_updates, subtotal, shipping, total, payment_status, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'confirmed')"
        );
        $order->execute([$userId, $values['email'], $values['first_name'], $values['last_name'],
            $values['address'], $values['postal_code'], $values['phone'], $values['email_updates'],
            $subtotal, $shipping, $total]);
        $orderId = (int) $conn->insert_id;
        $order->close();

        $line = $conn->prepare('INSERT INTO order_items
            (order_id, product_id, product_name, variant, unit_price, quantity) VALUES (?, ?, ?, ?, ?, ?)');
        $stock = $conn->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
        foreach ($items as $item) {
            $price = number_format($item['unit_price_cents'] / 100, 2, '.', '');
            $line->execute([$orderId, $item['id'], $item['name'], $item['variant'], $price, $item['quantity']]);
            $stock->execute([$item['quantity'], $item['id'], $item['quantity']]);
            if ($stock->affected_rows !== 1) {
                throw new DomainException('Stock changed. Please review your bag and try again.');
            }
        }
        $line->close();
        $stock->close();
        $history = $conn->prepare("INSERT INTO order_status_history (order_id, status, note)
            VALUES (?, 'confirmed', 'Demo payment successful; no money was charged.')");
        $history->execute([$orderId]);
        $history->close();
        $conn->commit();
        return $orderId;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}
