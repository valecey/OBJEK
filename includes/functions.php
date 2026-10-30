<?php
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// formatting prices
function money($amount) {
    return 'S$' . number_format((float) $amount, 2);
}

// stock status
function stock_status($stock) {
    if ($stock <= 0) {
        return ['Out of stock', 'is-out'];
    }
    if ($stock <= 5) {
        return ['Only ' . $stock . ' left', 'is-low'];
    }
    return ['In stock / Ready to ship', 'is-in'];
}

// pulls stock of each category
function get_categories($conn) {
    $sql = "SELECT c.id, c.name, c.slug, c.blurb, COUNT(p.id) AS product_count, MIN(p.price) AS min_price
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            GROUP BY c.id, c.name, c.slug, c.blurb, c.sort_order
            ORDER BY c.sort_order";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// cart quantity
function bag_count() {
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    return array_sum($_SESSION['cart']);
}

// product tag
function file_tag($name) {
    $slug = strtoupper(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
    return $slug . '.IMG';
}

// for "shop by what you do"
function get_uses() {
    return [
        'study'   => ['Study',   'Laptops, tablets and quiet headphones for lectures and late nights.', 'images/uses/study.svg',   'images/uses/study-grey.svg'],
        'commute' => ['Commute', 'Light, long-lasting gear for the MRT and the bus ride home.',          'images/uses/commute.svg', 'images/uses/commute-grey.svg'],
        'create'  => ['Create',  'Cameras, lenses and screens for photos, video and design work.',       'images/uses/create.svg',  'images/uses/create-grey.svg'],
        'play'    => ['Play',    'Controllers, handhelds and speakers for downtime.',                    'images/uses/play.svg',    'images/uses/play-grey.svg'],
    ];
}

// tilt effect
function render_stage($p, $alt = '', $lazy = true) {
    ?>
    <div class="stage">
      <div class="stage__tilt">
        <img class="stage__img" src="images/products/<?= e($p['image']) ?>" alt="<?= e($alt) ?>"
             width="400" height="400"<?= $lazy ? ' loading="lazy"' : '' ?>>
      </div>
    </div>
    <?php
}

// for product.php and listing.php
function render_product_card($p) {
    [$stockText, $stockClass] = stock_status((int) $p['stock']);
    ?>
    <article class="product-card tint-<?= e($p['category_slug'] ?? '') ?>">
      <a class="product-card__link" href="product.php?id=<?= (int) $p['id'] ?>">
        <div class="product-card__media">
          <span class="product-card__tag"><?= e(file_tag($p['name'])) ?></span>
          <?php render_stage($p, $p['name']); ?>
        </div>
        <div class="product-card__body">
          <h3 class="product-card__name"><?= e($p['name']) ?></h3>
          <p class="product-card__variant"><?= e($p['variant']) ?></p>
          <p class="product-card__price"><?= money($p['price']) ?></p>
          <p class="stock <?= $stockClass ?>"><?= e($stockText) ?></p>
        </div>
      </a>
    </article>
    <?php
}
