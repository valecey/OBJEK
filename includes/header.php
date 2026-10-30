<?php
$pageTitle       = $pageTitle       ?? 'OBJEK';
$pageDescription = $pageDescription ?? 'Phones, computers, audio, gaming and cameras, delivered across Singapore.';
$activeCategory  = $activeCategory  ?? '';
$navCategories   = get_categories($conn);
$searchValue     = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$hideDeliveryNote = $hideDeliveryNote ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <link rel="stylesheet" href="css/style.css">
  <script src="js/main.js" defer></script>
</head>
<body>
  <header class="site-header"> 
    <!-- navigation left side, search + account right side -->
    <div class="tabs-bar">
      <nav class="tabs" aria-label="Product categories">
        <ul>
          <li><a href="listing.php" <?= basename($_SERVER['PHP_SELF']) === 'listing.php' && $activeCategory === '' ? 'aria-current="page"' : '' ?>>All</a></li>
          <?php foreach ($navCategories as $cat): ?>
            <li>
              <a href="listing.php?category=<?= e($cat['slug']) ?>"
                 <?= $activeCategory === $cat['slug'] ? 'aria-current="page"' : '' ?>><?= e($cat['name']) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <div class="tabs-bar__utility">
        <!-- <details> open and close the search box -->
        <details class="search-toggle" <?= $searchValue !== '' ? 'open' : '' ?>>
          <summary>Search</summary>
          <form class="search-form" action="listing.php" method="get" role="search">
            <label class="visually-hidden" for="site-search">Search products</label>
            <input id="site-search" type="search" name="q" maxlength="50"
                   placeholder="Search headphones, laptops…" value="<?= e($searchValue) ?>"
                   required pattern=".*\S.*" title="Type at least one letter or number">
            <button type="submit">Go</button>
          </form>
        </details>
        <a href="account.php">Account</a>
      </div>
    </div>

    <!-- left side logo, right side delivery note + cart button -->
    <div class="brand-bar">
      <a class="logo" href="index.php" aria-label="OBJEK home">OBJEK<sup aria-hidden="true">™</sup></a>
      <?php if (empty($hideDeliveryNote)): ?>
        <p class="brand-bar__meta">
          <span class="square" aria-hidden="true"></span>
          Free delivery over S$100<br>
          <span>Ships across Singapore in 1 to 2 days</span>
        </p>
      <?php endif; ?>
      <a class="btn btn--lime" href="cart.php">Your bag (<?= bag_count() ?>)</a>
    </div>
  </header>
