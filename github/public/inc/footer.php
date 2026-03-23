<?php declare(strict_types=1); ?>
<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <a class="footer-brand-mark" href="/" aria-label="Interesa domov">
        <?= interessa_render_image(interessa_brand_image_meta('logo-icon'), ['alt' => 'Interesa symbol', 'width' => '28', 'height' => '28', 'class' => 'footer-brand-icon']) ?>
        <span class="footer-brand-title">Interesa.sk</span>
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
<?php $localBuildMeta = function_exists('interessa_local_build_meta') ? interessa_local_build_meta() : []; ?>
<button class="dev-reload-fab" type="button" data-dev-reload>Nacitat znova lokalnu verziu</button>
<div class="dev-build-badge" aria-live="polite">
  <strong>Lokalna verzia</strong>
  <span>Marker <?= esc(interessa_dev_build_label()) ?></span>
  <?php if (($localBuildMeta['started_at_display'] ?? '') !== ''): ?>
    <span>Start <?= esc((string) $localBuildMeta['started_at_display']) ?></span>
  <?php endif; ?>
  <?php if (($localBuildMeta['git_short'] ?? '') !== ''): ?>
    <span>Git <?= esc((string) $localBuildMeta['git_short']) ?></span>
  <?php endif; ?>
</div>
<?php endif; ?>

<script src="<?= asset('js/app.js') ?>" defer></script>
<?= script_tags(page_script_urls()) ?>

</body>
</html>
