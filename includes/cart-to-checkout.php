<?php
// read the session cart without changing it.
function load_cart_items($conn, $cart) {
    $items = [];
    $issues = [];

    if (!is_array($cart)) {
        return ['items' => [], 'issues' => ['Your bag data is invalid. Please start a new shopping session.']];
    }
    if (!$cart) {
        return ['items' => [], 'issues' => []];
    }

    // prepare once, then look up each product using its session ID.
    $stmt = $conn->prepare('SELECT id, name, variant, price, stock FROM products WHERE id = ?');
    foreach ($cart as $productId => $quantity) {
        $id = filter_var($productId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $qty = filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        if ($id === false || $qty === false) {
            $issues[] = 'An item in your bag has an invalid product ID or quantity.';
            continue;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if (!$product) {
            $issues[] = 'Product #' . $id . ' is no longer available. It must be removed before checkout.';
            continue;
        }

        //convert to cents
        $priceParts = explode('.', $product['price']);
        $unitPriceCents = (int) $priceParts[0] * 100
            + (int) str_pad($priceParts[1] ?? '', 2, '0');
        $stock = (int) $product['stock'];
        $issue = '';
        if ($stock <= 0) {
            $issue = 'This product is out of stock.';
        } elseif ($qty > $stock) {
            $issue = 'Only ' . $stock . ' available; your bag contains ' . $qty . '.';
        }
        if ($issue !== '') {
            $issues[] = $product['name'] . ': ' . $issue;
        }

        $items[] = [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'variant' => $product['variant'],
            'quantity' => $qty,
            'unit_price_cents' => $unitPriceCents,
            'line_subtotal_cents' => $unitPriceCents * $qty,
            'issue' => $issue,
        ];
    }
    $stmt->close();
    return ['items' => $items, 'issues' => $issues];
}

function calculate_cart_totals($items) {
    $subtotalCents = 0;
    foreach ($items as $item) {
        $subtotalCents += $item['line_subtotal_cents'];
    }
    // havent do shipping yet, currently 100 dollar
    return ['subtotal_cents' => $subtotalCents];
}
