<?php
require 'includes/db.php';
require 'includes/functions.php';

// home page feature area 
$featured = $conn->query(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug
     FROM products p JOIN categories c ON c.id = p.category_id
     WHERE p.featured = 1
     ORDER BY p.id"
)->fetch_all(MYSQLI_ASSOC);

// picks out top three specifications for each respective product to put around the product image 
$specs = [];
if ($featured) {
    $ids = implode(',', array_map('intval', array_column($featured, 'id')));
    $rows = $conn->query(
        "SELECT product_id, label, value FROM product_specs
         WHERE product_id IN ($ids) ORDER BY product_id, sort_order"
    )->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as $row) {
        if (count($specs[$row['product_id']] ?? []) < 3) {
            $specs[$row['product_id']][] = $row;
        }
    }
}

// select which product gets featured
$pick = filter_input(INPUT_GET, 'pick', FILTER_VALIDATE_INT);
$activeId = $featured ? (int) $featured[0]['id'] : 0;
foreach ($featured as $f) {
    if ((int) $f['id'] === $pick) {
        $activeId = $pick;
    }
}

// counts how many products for each "shop by" section
$all = $conn->query(
    "SELECT p.id, p.name, p.image, p.price, p.uses, c.slug AS category_slug
     FROM products p JOIN categories c ON c.id = p.category_id
     ORDER BY p.featured DESC, p.id"
)->fetch_all(MYSQLI_ASSOC);

$uses = get_uses();
$useGroups = [];
foreach ($uses as $slug => $u) {
    $useGroups[$slug] = [];
}
foreach ($all as $p) {
    foreach (explode(',', $p['uses']) as $slug) {
        if (isset($useGroups[$slug])) {
            $useGroups[$slug][] = $p;
        }
    }
}

// matches price information from listing.php to "shop by budget"
$budgets = [
    'under-200' => ['Under S$200',    'Under 200', 0,   200],
    '200-800'   => ['S$200 to S$800', '200–800',   200, 800],
    'over-800'  => ['Over S$800',     '800+',      800, null],
];
$budgetGroups = [];
foreach ($budgets as $key => $b) {
    $budgetGroups[$key] = array_values(array_filter($all, function ($p) use ($b) {
        return $p['price'] >= $b[2] && ($b[3] === null || $p['price'] < $b[3]);
    }));
    usort($budgetGroups[$key], function ($a, $b) { return $a['price'] <=> $b['price']; });
}

$pageTitle = 'OBJEK | Electronics for the everyday';
$pageDescription = 'Phones, laptops, audio, gaming and cameras chosen for daily use. Free delivery in Singapore on orders above S$100.';
include 'includes/header.php';
?>


<main id="main">
  <h1 class="visually-hidden">OBJEK: electronics for the everyday</h1>
  <!-- featured product area -->
  <section class="stage-hero" id="stage" aria-label="Featured products">
    <div class="wrap">
      <?php foreach ($featured as $i => $p):
          $isActive = (int) $p['id'] === $activeId;
          [$stockText, $stockClass] = stock_status((int) $p['stock']); ?>
        <article class="slide tint-<?= e($p['category_slug']) ?>" id="slide-<?= (int) $p['id'] ?>"
                 data-slide="<?= (int) $p['id'] ?>" <?= $isActive ? '' : 'hidden' ?>>
          <div class="slide__info">
            <p class="eyebrow">On the stage / <?= e($p['category_name']) ?></p>
            <h2 class="display slide__name"><?= e($p['name']) ?></h2>
            <p class="slide__tagline"><?= e($p['tagline']) ?></p>
            <div class="slide__buy">
              <p class="slide__price"><?= money($p['price']) ?></p>
              <p class="stock <?= $stockClass ?>"><?= e($stockText) ?></p>
            </div>
            <div class="slide__actions">
              <?php if ((int) $p['stock'] > 0): ?>
                <form action="add-to-cart.php" method="post">
                  <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                  <input type="hidden" name="qty" value="1">
                  <button class="btn btn--lime btn--lg" type="submit">Add to bag</button>
                </form>
              <?php endif; ?>
              <a class="btn btn--outline btn--lg" href="product.php?id=<?= (int) $p['id'] ?>">See full specs</a>
            </div>
          </div>
          <figure class="window slide__stage">
            <span class="window__tag"><?= e(file_tag($p['name'])) ?></span>
            <?php render_stage($p, $p['name'], !$isActive); ?>
            <?php if (!empty($specs[$p['id']])): ?>
              <ul class="callouts" aria-label="Key specs">
                <?php foreach ($specs[$p['id']] as $n => $spec): ?>
                  <li class="callout callout--<?= $n + 1 ?>">
                    <span class="callout__label"><?= e($spec['label']) ?></span>
                    <span class="callout__value"><?= e($spec['value']) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </figure>
        </article>
      <?php endforeach; ?>
      <nav class="picker" aria-label="Choose a featured product">
        <?php foreach ($featured as $i => $p): ?>
          <a class="picker__item tint-<?= e($p['category_slug']) ?>" href="index.php?pick=<?= (int) $p['id'] ?>#stage"
             data-pick="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === $activeId ? 'aria-current="true"' : '' ?>>
            <img src="images/products/<?= e($p['image']) ?>" alt="" width="56" height="56">
            <span class="picker__text">
              <span class="picker__num"><?= sprintf('%02d', $i + 1) ?></span>
              <span class="picker__name"><?= e($p['name']) ?></span>
              <span class="picker__price"><?= money($p['price']) ?></span>
            </span>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
  </section>

  <!-- "made for what you do" area -->
  <section class="light" aria-labelledby="uses-title">
    <div class="wrap">
      <div class="meta-row">
        <span>Shop by what you do</span>
        <span><?= count($all) ?> objects / <?= count($uses) ?> routines</span>
        <span><?= date('Y') ?></span>
      </div>
      <h2 class="display display--split" id="uses-title">Made for <span>what you do</span></h2>
      <!-- hovering effect -->
      <ul class="uses">
        <?php foreach ($uses as $slug => $u):
            $group = $useGroups[$slug]; ?>
          <li>
            <a class="use" href="listing.php?use=<?= e($slug) ?>">
              <span class="use__word"><?= e($u[0]) ?></span>
              <span class="use__blurb"><?= e($u[1]) ?></span>
              <span class="use__visual">
                <img class="use__img use__img--grey" src="<?= e($u[3]) ?>" alt="" width="600" height="400" loading="lazy">
                <img class="use__img use__img--colour" src="<?= e($u[2]) ?>" alt="" width="600" height="400" loading="lazy">
              </span>
              <span class="use__meta"><?= count($group) ?> objects &nearr;</span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- "shop by budget" area -->
  <section class="section" aria-labelledby="budget-title">
    <div class="wrap">
      <div class="section__head">
        <div>
          <p class="eyebrow">Shop by budget</p>
          <h2 class="display display--md display--split" id="budget-title">Start from <span>what you can spend</span></h2>
        </div>
        <a class="text-link" href="listing.php?sort=price-asc">All, lowest price first &nearr;</a>
      </div>
      <ul class="budgets">
        <?php foreach ($budgets as $key => $b):
            $group = $budgetGroups[$key];
            $extra = count($group) - 4; ?>
          <li>
            <a class="window budget" href="listing.php?price=<?= e($key) ?>&amp;sort=price-asc">
              <span class="window__tag"><?= e(strtoupper($key)) ?>.SGD</span>
              <span class="budget__shelf" aria-hidden="true">
                <?php foreach (array_slice($group, 0, 4) as $p): ?>
                  <span class="budget__item tint-<?= e($p['category_slug']) ?>">
                    <img src="images/products/<?= e($p['image']) ?>" alt="" width="120" height="120" loading="lazy">
                    <span class="budget__price">S$<?= number_format((float) $p['price']) ?></span>
                  </span>
                <?php endforeach; ?>
              </span>
              <span class="budget__info">
                <span class="budget__range"><span class="budget__currency">S$</span><?= e($b[1]) ?></span>
                <span class="budget__count">
                  <?= count($group) ?> <?= count($group) === 1 ? 'object' : 'objects' ?><?= $extra > 0 ? ', showing the 4 cheapest' : '' ?>
                </span>
                <span class="budget__cta">Shop this range &nearr;</span>
              </span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <!-- free delivery, warranty, returns -->
  <section class="section section--tight" aria-labelledby="promise-title">
    <div class="wrap">
      <h2 class="visually-hidden" id="promise-title">Why shop with us</h2>
      <ul class="promises">
        <li>
          <h3>Free delivery over S$100</h3>
          <p>Orders placed before 2pm leave our warehouse the same day.</p>
        </li>
        <li>
          <h3>One-year warranty</h3>
          <p>If something stops working, we repair or replace it.</p>
        </li>
        <li>
          <h3>14-day returns</h3>
          <p>Return unused items within 14 days for a full refund.</p>
        </li>
      </ul>
    </div>
  </section>
</main>
<?php include 'includes/footer.php'; ?>
