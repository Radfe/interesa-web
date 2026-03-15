<?php declare(strict_types=1); ?>
<footer class="site-footer">
  <div class="container footer-inner">
    <p>&copy; <span id="year"></span> Interesa. Vsetky prava vyhradene. Niektore odkazy mozu viest na partnerske obchody.</p>
    <nav aria-label="Sekundarna navigacia">
      <a href="/clanky/">Clanky</a>
      <a href="/kategorie/imunita">Imunita</a>
      <a href="/kategorie/klby-koza">Klby a koza</a>
    </nav>
  </div>
</footer>

<?php if (function_exists('interessa_is_local_dev') && interessa_is_local_dev()): ?>
<button class="dev-reload-fab" type="button" data-dev-reload>Obnovit verziu</button>
<?php endif; ?>

<script src="<?= asset('js/app.js') ?>" defer></script>
<?= script_tags(page_script_urls()) ?>

</body>
</html>
