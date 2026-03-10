<?php declare(strict_types=1); ?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col footer-branding">
      <a class="brand brand-footer" href="/" aria-label="Domovská stránka Interesa">
        <img class="brand-icon" src="<?= asset('img/logo-icon.svg') ?>" alt="" width="44" height="44" aria-hidden="true" />
        <span class="brand-copy">
          <strong>Interesa</strong>
          <span>Výživa a doplnky bez chaosu</span>
        </span>
      </a>
      <p class="footer-note">Interesa prináša prehľadné články, porovnania a praktické návody, aby sa človek vedel rýchlo zorientovať vo výžive a doplnkoch.</p>
    </div>

    <div class="footer-col">
      <h3>Obsah</h3>
      <ul class="link-list">
        <li><a href="/clanky/">Články</a></li>
        <li><a href="/kategorie/">Kategórie</a></li>
        <li><a href="/kategorie/proteiny">Proteíny</a></li>
        <li><a href="/kategorie/mineraly">Vitamíny a minerály</a></li>
      </ul>
    </div>

    <div class="footer-col">
      <h3>Dôvera a kontakt</h3>
      <ul class="link-list">
        <li><a href="/o-nas">O nás</a></li>
        <li><a href="/affiliate">Affiliate a financovanie</a></li>
        <li><a href="/ochrana-osobnych-udajov">Ochrana osobných údajov</a></li>
        <li><a href="/kontakt">Kontakt</a></li>
      </ul>
    </div>
  </div>

  <div class="container footer-bottom">
    <p>&copy; <span id="year"></span> Interesa. Všetky práva vyhradené.</p>
    <p>Niektoré odkazy môžu byť affiliate. Na cene pre návštevníka sa nič nemení.</p>
  </div>
</footer>

<script async src="//serve.affiliate.heurekashopping.sk/js/trixam.min.js"></script>
<script src="<?= asset('js/app.js') ?>" defer></script>

</body>
</html>