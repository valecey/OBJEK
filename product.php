<?php
require 'includes/db.php';
require 'includes/functions.php';

// validate product id
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$product = null;

if ($id) {
    $stmt = $conn->prepare(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug
         FROM products p JOIN categories c ON c.id = p.category_id
         WHERE p.id = ?"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
}

// product not found
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found | OBJEK';
    include 'includes/header.php';
    ?>
    <main id="main" class="wrap not-found">
      <p class="eyebrow">Error 404</p>
      <h1 class="display">We couldn't find that product.</h1>
      <p class="description">It may have been removed, or the link may be wrong. Everything we currently sell is on the products page.</p>
      <a class="btn btn--lime" href="listing.php">Browse all products &rarr;</a>
    </main>
    <?php
    include 'includes/footer.php';
    exit;
}

// specifications information table
$stmt = $conn->prepare("SELECT label, value FROM product_specs WHERE product_id = ? ORDER BY sort_order");
$stmt->bind_param('i', $product['id']);
$stmt->execute();
$specs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// list related product from same category
$stmt = $conn->prepare(
    "SELECT p.*, c.slug AS category_slug FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.category_id = ? AND p.id <> ? ORDER BY p.featured DESC, p.id LIMIT 4"
);
$stmt->bind_param('ii', $product['category_id'], $product['id']);
$stmt->execute();
$related = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

[$stockText, $stockClass] = stock_status((int) $product['stock']);
$maxQty = max(0, min(10, (int) $product['stock']));   // at most 10 per order
$justAdded = isset($_GET['added']) ? (int) $_GET['added'] : 0;
$addError  = $_GET['error'] ?? '';

$pageTitle = $product['name'] . ' | OBJEK';
$pageDescription = $product['tagline'];
$activeCategory = $product['category_slug'];
include 'includes/header.php';
?>
<main id="main" class="wrap">
  <nav aria-label="Breadcrumb">
    <ol class="crumbs">
      <li><a href="listing.php">Shop</a></li>
      <li><a href="listing.php?category=<?= e($product['category_slug']) ?>"><?= e($product['category_name']) ?></a></li>
      <li aria-current="page"><?= e($product['name']) ?></li>
    </ol>
  </nav>
  <div class="pdp">
    <figure class="window pdp__media tint-<?= e($product['category_slug']) ?>">
      <span class="window__tag"><?= e(file_tag($product['name'])) ?></span>
      <?php render_stage($product, $product['name'], false); ?>
    </figure>
    
    <div>
      <p class="eyebrow"><?= e($product['category_name']) ?></p>
      <h1 class="pdp__name"><?= e($product['name']) ?></h1>
      <p class="pdp__variant"><?= e($product['variant']) ?></p>
      <p class="pdp__price"><?= money($product['price']) ?></p>
      <p class="stock <?= $stockClass ?>"><?= e($stockText) ?></p>
      <p class="pdp__desc"><?= e($product['description']) ?></p>

      <?php if ($justAdded > 0): ?>
        <p class="added" role="status">
          <span>Added <?= $justAdded ?> to your bag.</span>
          <a class="text-link" href="cart.php">View bag &nearr;</a>
        </p>
      <?php elseif ($addError !== ''): ?>
        <p class="notice" role="alert" style="margin-top:28px">
          <?= $addError === 'stock' ? 'We don\'t have that many in stock. Choose a smaller quantity.' : 'Choose a quantity between 1 and 10.' ?>
        </p>
      <?php endif; ?>

      <form class="buy" action="add-to-cart.php" method="post" id="buy-form" novalidate>
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <label class="field__label" for="qty">Quantity</label>
        <div class="buy__row">
          <div class="qty" data-max="<?= $maxQty ?>">
            <button type="button" data-step="-1" aria-label="Decrease quantity">&minus;</button>
            <input id="qty" type="number" name="qty" value="1" min="1" max="<?= max(1, $maxQty) ?>" step="1"
                   inputmode="numeric" <?= $maxQty === 0 ? 'disabled' : '' ?>>
            <button type="button" data-step="1" aria-label="Increase quantity">+</button>
          </div>
          <?php if ($maxQty > 0): ?>
            <button class="btn btn--lime" type="submit">Add to bag &rarr;</button>
          <?php else: ?>
            <button class="btn btn--lime" type="button" disabled>Out of stock</button>
          <?php endif; ?>
        </div>
        <p class="field-error" id="qty-error" hidden></p>
        <ul class="buy__notes">
          <li>Free delivery on orders above S$100</li>
          <li>One-year warranty, 14-day returns</li>
        </ul>
      </form>
    </div>
  </div>

  <?php if ($specs): ?>
    <section class="section section--line">
      <table class="spec-table">
        <caption>Specifications</caption>
        <tbody>
          <?php foreach ($specs as $spec): ?>
            <tr>
              <th scope="row"><?= e($spec['label']) ?></th>
              <td><?= e($spec['value']) ?></td>
            </tr>
          <?php endforeach; ?>
          <tr>
            <th scope="row">Price</th>
            <td><?= money($product['price']) ?> (incl. GST)</td>
          </tr>
        </tbody>
      </table>
    </section>
  <?php endif; ?>

  <?php if ($related): ?>
    <section class="section section--line" aria-labelledby="related-title">
      <div class="section__head">
        <h2 class="section__title" id="related-title">More in <?= e($product['category_name']) ?></h2>
        <a class="text-link" href="listing.php?category=<?= e($product['category_slug']) ?>">See all &nearr;</a>
      </div>
      <div class="product-grid product-grid--four">
        <?php foreach ($related as $p) { render_product_card($p); } ?>
      </div>
    </section>
  <?php endif; ?>

</main>
<?php include 'includes/footer.php'; ?>
