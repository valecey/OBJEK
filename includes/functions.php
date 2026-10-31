<?php
function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// formatting prices
function money($amount) {
    return 'S$' . number_format((float) $amount, 2);
}

// stock status
const PRODUCT_STOCK_SQL = '(SELECT COALESCE(SUM(pc.stock), 0) FROM product_colours pc WHERE pc.product_id = p.id)';
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
        'create'  => ['Create',  'Cameras, laptops and desk gear for photos, video and design work.',       'images/uses/create.svg',  'images/uses/create-grey.svg'],
        'play'    => ['Play',    'Controllers, headsets and speakers for downtime.',                    'images/uses/play.svg',    'images/uses/play-grey.svg'],
    ];
}

// colours for each product 
function get_colours($conn, $productIds) {
    $ids = array_filter(array_map('intval', $productIds));
    if (!$ids) {
        return [];
    }
    $rows = $conn->query(
        'SELECT id, product_id, name, hex, image, stock FROM product_colours
         WHERE product_id IN (' . implode(',', $ids) . ') ORDER BY product_id, sort_order, id'
    )->fetch_all(MYSQLI_ASSOC);

    $byProduct = [];
    foreach ($rows as $row) {
        $byProduct[(int) $row['product_id']][] = $row;
    }
    return $byProduct;
}

function attach_colours($conn, $products) {
    $colours = get_colours($conn, array_column($products, 'id'));
    foreach ($products as $i => $p) {
        $products[$i]['colours'] = $colours[(int) $p['id']] ?? [];
    }
    return $products;
}

function pick_colour($colours, $wantedId = null) {
    foreach ($colours as $c) {
        if ((int) $c['id'] === (int) $wantedId) {
            return $c;
        }
    }
    foreach ($colours as $c) {
        if ((int) $c['stock'] > 0) {
            return $c;
        }
    }
    return $colours[0] ?? null;
}

// colour dots under product card
function render_swatch_links($p) {
    if (count($p['colours'] ?? []) < 2) {   // no dots if product only has one colour
        return;
    }
    ?>
    <ul class="swatches" aria-label="Colours">
      <?php foreach ($p['colours'] as $c): ?>
        <li>
          <a class="swatch<?= (int) $c['stock'] <= 0 ? ' is-sold-out' : '' ?>" style="--swatch: <?= e($c['hex']) ?>"
             href="product.php?id=<?= (int) $p['id'] ?>&amp;colour=<?= (int) $c['id'] ?>"
             data-image="images/products/<?= e($c['image']) ?>"
             title="<?= e($c['name']) ?><?= (int) $c['stock'] <= 0 ? ' (sold out)' : '' ?>">
            <span class="visually-hidden"><?= e($c['name']) ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php
}

// product colour picker inside add to cart page
function render_colour_picker($colours, $selected, $prefix = 'colour') {
    ?>
    <fieldset class="colour-picker" data-colour-picker>
      <legend class="field__label">Colour: <span class="colour-picker__chosen" data-colour-name><?= e($selected['name']) ?></span></legend>
      <div class="colour-picker__options">
        <?php foreach ($colours as $c):
            $inputId = $prefix . '-' . (int) $c['id']; ?>
          <input class="colour-picker__input" type="radio" name="colour_id" id="<?= e($inputId) ?>"
                 value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $selected['id'] ? 'checked' : '' ?>
                 data-image="images/products/<?= e($c['image']) ?>"
                 data-name="<?= e($c['name']) ?>" data-stock="<?= (int) $c['stock'] ?>">
          <label class="swatch swatch--lg<?= (int) $c['stock'] <= 0 ? ' is-sold-out' : '' ?>" for="<?= e($inputId) ?>"
                 style="--swatch: <?= e($c['hex']) ?>" title="<?= e($c['name']) ?><?= (int) $c['stock'] <= 0 ? ' (sold out)' : '' ?>">
            <span class="visually-hidden"><?= e($c['name']) ?><?= (int) $c['stock'] <= 0 ? ' (sold out)' : '' ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </fieldset>
    <?php
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
    $shown = pick_colour($p['colours'] ?? []);
    if ($shown) {
        $p['image'] = $shown['image'];
    }
    ?>
    <article class="product-card tint-<?= e($p['category_slug'] ?? '') ?>" data-card>      
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
      <?php render_swatch_links($p); ?>
    </article>
    <?php
}
