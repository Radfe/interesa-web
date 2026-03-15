<?php declare(strict_types=1); ?>
<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <a class="footer-brand-mark" href="/" aria-label="Interesa domov">
        <?= interessa_render_image(interessa_brand_image_meta('logo-full'), ['alt' => 'Interesa.sk logo', 'width' => '148', 'height' => '32']) ?>
      </a>
      <p class="footer-brand-copy">Prakticke porovnania, navody a odporucania pre ludi, ktori sa chcu vo vyzive zorientovat bez chaosu.</p>
      <p class="footer-note">&copy; <span id="year"></span> Interesa. Niektore odkazy mozu viest na partnerske obchody. Ak cez ne nakupis, cena pre teba ostava rovnaka.</p>
    </div>

    <div class="footer-links">
      <nav aria-label="Sekundarna navigacia">
        <a href="/clanky/">Clanky</a>
        <a href="/kategorie/">Kategorie</a>
        <a href="/kategorie/proteiny">Proteiny</a>
        <a href="/kategorie/mineraly">Vitaminy a mineraly</a>
        <a href="/kategorie/sila">Sila a vykon</a>
        <a href="/kategorie/klby-koza">Klby a koza</a>
      </nav>
    </div>
  </div>
</footer>

<?php if (function_exists('interessa_is_local_dev') && interessa_is_local_dev()): ?>
<button class="dev-reload-fab" type="button" data-dev-reload>Obnovit verziu</button>
<?php endif; ?>

<script src="<?= asset('js/app.js') ?>" defer></script>
<?= script_tags(page_script_urls()) ?>

</body>
</html>
