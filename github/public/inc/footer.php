<?php declare(strict_types=1); ?>
<footer class="site-footer">
  <div class="container footer-inner">
    <p>&copy; <span id="year"></span> Interesa. Všetky práva vyhradené. Niektoré odkazy môžu viesť na partnerské obchody.</p>
    <nav aria-label="Sekundárna navigácia">
      <a href="/clanky/">Články</a>
      <a href="/kategorie/imunita">Imunita</a>
      <a href="/kategorie/klby-koza">Kĺby a koža</a>
    </nav>
  </div>
</footer>

<script src="<?= asset('js/app.js') ?>" defer></script>
<?= script_tags(page_script_urls()) ?>

</body>
</html>