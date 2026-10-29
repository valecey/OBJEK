<?php
require 'includes/db.php';
require 'includes/functions.php';

$categories = get_categories($conn);
$notices = [];

// validating the filters
// seach bar (max 50 char)
$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
if (isset($_GET['q']) && $q === '' && $_GET['q'] !== '') {
    $notices[] = 'Your search was empty, so all products are shown.';
}
if (mb_strlen($q) > 50) {
    $q = mb_substr($q, 0, 50);
    $notices[] = 'Searches are limited to 50 characters, so yours was shortened.';
}

// category types corresponding to databass
$category = null;
$categorySlug = isset($_GET['category']) ? (string) $_GET['category'] : '';
if ($categorySlug !== '') {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $categorySlug) {
            $category = $cat;
        }
    }
    if ($category === null) {
        $notices[] = 'We don\'t have a category called "' . $categorySlug . '". Showing all products instead.';
        $categorySlug = '';
    }
}

// price filter, matches "shop by budget"
$priceBands = [
    ''          => ['Any price', null, null],
    'under-200' => ['Under S$200', 0, 200],
    '200-800'   => ['S$200 to S$800', 200, 800],
    'over-800'  => ['Over S$800', 800, null],
];
$price = isset($_GET['price']) ? (string) $_GET['price'] : '';
if (!array_key_exists($price, $priceBands)) {
    $notices[] = 'That price range isn\'t available, so the price filter was cleared.';
    $price = '';
}

// sort by filter
$sorts = [
    'featured'   => ['Featured first',      'p.featured DESC, p.id ASC'],
    'price-asc'  => ['Price, low to high',  'p.price ASC'],
    'price-desc' => ['Price, high to low',  'p.price DESC'],
    'name'       => ['Name, A to Z',        'p.name ASC'],
];
$sort = isset($_GET['sort']) ? (string) $_GET['sort'] : 'featured';
if (!array_key_exists($sort, $sorts)) {
    $sort = 'featured';
}

$inStockOnly = isset($_GET['instock']) && $_GET['instock'] === '1';

// made for filter, matches "shop by what you do"
$uses = get_uses();
$use = isset($_GET['use']) ? (string) $_GET['use'] : '';
if ($use !== '' && !array_key_exists($use, $uses)) {
    $notices[] = 'That "made for" option isn\'t available, so it was cleared.';
    $use = '';
}

// query structure for the filter
$sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM products p
        JOIN categories c ON c.id = p.category_id";
$where  = [];
$types  = '';
$params = [];

if ($category !== null) {
    $where[] = 'p.category_id = ?';
    $types  .= 'i';
    $params[] = (int) $category['id'];
}
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.tagline LIKE ? OR p.variant LIKE ? OR c.name LIKE ?)';
    $like = '%' . $q . '%';
    $types .= 'ssss';
    array_push($params, $like, $like, $like, $like);
}
[, $min, $max] = $priceBands[$price];
if ($min !== null) {
    $where[] = 'p.price >= ?';
    $types  .= 'd';
    $params[] = $min;
}
if ($max !== null) {
    $where[] = 'p.price < ?';
    $types  .= 'd';
    $params[] = $max;
}
if ($inStockOnly) {
    $where[] = 'p.stock > 0';
}
if ($use !== '') {
    $where[] = '(p.uses = ? OR p.uses LIKE ? OR p.uses LIKE ? OR p.uses LIKE ?)';
    $types  .= 'ssss';
    array_push($params, $use, $use . ',%', '%,' . $use, '%,' . $use . ',%');
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY ' . $sorts[$sort][1];

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$count = count($products);

// texts and design
$heading = $category ? $category['name'] : 'Everything';
$intro   = $category ? $category['blurb'] : 'Every product in the store. Filter by category, price or what it\'s made for.';
if ($use !== '' && $category === null) {
    $heading = 'For ' . $uses[$use][0];
    $intro   = $uses[$use][1];
}
$hasFilters = $q !== '' || $category !== null || $price !== '' || $inStockOnly || $use !== '';

$pageTitle = ($category ? $category['name'] : 'All products') . ' | OBJEK';
if ($q !== '') {
    $pageTitle = 'Search: ' . $q . ' | OBJEK';
}
$pageDescription = $intro;
$activeCategory = $categorySlug;
include 'includes/header.php';
?>


<main id="main" class="wrap">

  <div class="catalogue-head">
    <div>
      <p class="eyebrow">Browse the catalogue</p>
      <h1 class="display"><?= e($heading) ?> <span><?= $count ?> <?= $count === 1 ? 'object' : 'objects' ?></span></h1>
      <p class="description"><?= e($intro) ?></p>
    </div>
    <?php if ($category): ?>
      <a class="text-link" href="listing.php">View all products &nearr;</a>
    <?php endif; ?>
  </div>

  <div class="catalogue">
    <aside class="panel filters" aria-label="Filter products">
      <div class="panel__bar"><span>FILTER.SYS</span><span><?= $count ?> found</span></div>
      <form class="panel__body" id="filter-form" action="listing.php" method="get" novalidate>
        <div class="field">
          <label class="field__label" for="f-q">Search</label>
          <input id="f-q" type="search" name="q" maxlength="50" value="<?= e($q) ?>" placeholder="e.g. headphones">
          <p class="field-error" id="f-q-error" hidden></p>
        </div>
        <fieldset>
          <legend>Category</legend>
          <label class="choice">
            <input type="radio" name="category" value="" <?= $categorySlug === '' ? 'checked' : '' ?>>
            <span>All</span>
          </label>
          <?php foreach ($categories as $cat): ?>
            <label class="choice">
              <input type="radio" name="category" value="<?= e($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'checked' : '' ?>>
              <span><?= e($cat['name']) ?></span>
              <span class="choice__count"><?= (int) $cat['product_count'] ?></span>
            </label>
          <?php endforeach; ?>
        </fieldset>
        <div class="field">
          <label class="field__label" for="f-price">Price</label>
          <select id="f-price" name="price">
            <?php foreach ($priceBands as $value => $band): ?>
              <option value="<?= e($value) ?>" <?= $price === $value ? 'selected' : '' ?>><?= e($band[0]) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="field__label" for="f-use">Made for</label>
          <select id="f-use" name="use">
            <option value="">Anything</option>
            <?php foreach ($uses as $value => $u): ?>
              <option value="<?= e($value) ?>" <?= $use === $value ? 'selected' : '' ?>><?= e($u[0]) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="field__label" for="f-sort">Sort by</label>
          <select id="f-sort" name="sort">
            <?php foreach ($sorts as $value => $s): ?>
              <option value="<?= e($value) ?>" <?= $sort === $value ? 'selected' : '' ?>><?= e($s[0]) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <label class="choice" style="margin-top:16px">
          <input type="checkbox" name="instock" value="1" <?= $inStockOnly ? 'checked' : '' ?>>
          <span>In stock only</span>
        </label>
        <div class="filters__actions">
          <button class="btn btn--lime" type="submit">Apply filters</button>
          <?php if ($hasFilters || $sort !== 'featured'): ?>
            <a class="text-link" href="listing.php">Clear</a>
          <?php endif; ?>
        </div>
      </form>
    </aside>

    <section aria-labelledby="results-title">
      <h2 class="visually-hidden" id="results-title">Products</h2>
      <?php foreach ($notices as $notice): ?>
        <p class="notice" role="status"><?= e($notice) ?></p>
      <?php endforeach; ?>
      <p class="results-bar">
        <span><strong><?= $count ?></strong> <?= $count === 1 ? 'product' : 'products' ?></span>
        <?php if ($q !== ''): ?><span>matching "<strong><?= e($q) ?></strong>"</span><?php endif; ?>
        <?php if ($price !== ''): ?><span><?= e($priceBands[$price][0]) ?></span><?php endif; ?>
        <?php if ($use !== ''): ?><span>For <?= e(strtolower($uses[$use][0])) ?></span><?php endif; ?>
        <?php if ($inStockOnly): ?><span>In stock only</span><?php endif; ?>
        <span>Sorted by <?= e(strtolower($sorts[$sort][0])) ?></span>
      </p>
      <?php if ($count > 0): ?>
        <div class="product-grid">
          <?php foreach ($products as $p) { render_product_card($p); } ?>
        </div>
      <?php else: ?>
        <div class="empty">
          <h2>Nothing matches those filters.</h2>
          <p>Try a shorter search, a different price range, or turn off "In stock only".</p>
          <a class="btn btn--lime" href="listing.php">Show all products</a>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php include 'includes/footer.php'; ?>
