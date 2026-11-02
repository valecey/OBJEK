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

    // prepare once, then look up each colour (and its product) using its session ID.
    $stmt = $conn->prepare(
        'SELECT p.id, p.name, p.variant, p.price, pc.id AS colour_id, pc.name AS colour_name, pc.stock
         FROM product_colours pc JOIN products p ON p.id = pc.product_id
         WHERE pc.id = ?'
    );
    foreach ($cart as $colourId => $quantity) {
        $id = filter_var($colourId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);        
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
            $issue = 'This colour is out of stock.';
        } elseif ($qty > $stock) {
            $issue = 'Only ' . $stock . ' available; your bag contains ' . $qty . '.';
        }
        if ($issue !== '') {
            $issues[] = $product['name'] . ' (' . $product['colour_name'] . '): ' . $issue;            
        }

        $items[] = [
            'id' => (int) $product['id'],
            'colour_id' => (int) $product['colour_id'],
            'name' => $product['name'],
            'variant' => $product['colour_name'] . ' / ' . $product['variant'],           
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
    // Calculate in cents: free at S$100 or more, otherwise S$8.
    // An empty bag has no delivery charge.
    $shippingCents = $items && $subtotalCents < 10000 ? 800 : 0;
    return [
        'subtotal_cents' => $subtotalCents,
        'shipping_cents' => $shippingCents,
        'total_cents' => $subtotalCents + $shippingCents,
    ];
}
