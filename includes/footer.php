  <footer class="site-footer">
    <div class="site-footer__inner">
      <div class="site-footer__brand">
        <a class="logo" href="index.php" aria-label="OBJEK home">OBJEK<sup aria-hidden="true">™</sup></a>
        <p>Electronics chosen for daily use, delivered across Singapore.</p>
      </div>

      <div class="site-footer__col">
        <h2>Shop</h2>
        <ul>
          <?php foreach ($navCategories as $cat): ?>
            <li><a href="listing.php?category=<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="site-footer__col">
        <h2>Help</h2>
        <ul>
          <li><a href="listing.php">All products</a></li>
          <li><a href="cart.php">Your bag</a></li>
          <li><a href="account.php">Track an order</a></li>
        </ul>
      </div>

      <div class="site-footer__col">
        <h2>Visit</h2>
        <p>50 Nanyang Avenue<br>Singapore 639798<br>Mon to Sat, 11am to 8pm</p>
      </div>
    </div>
    <p class="site-footer__legal">&copy; <?= date('Y') ?> OBJEK. IE4727 project, not a real store.</p>
  </footer>
</body>
</html>
