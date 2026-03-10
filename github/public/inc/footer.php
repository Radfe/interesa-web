<?php declare(strict_types=1); ?>
<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-col footer-branding">
      <img src="<?= asset('img/logo-full.svg') ?>" alt="Interesa" width="148" height="32" />
      <p class="footer-note">Interesa je obsahový web o výžive, doplnkoch a výbere produktov. Staviame na zrozumiteľnom vysvetlení, praktických porovnaniach a poctivých odporúčaniach.</p>
    </div>

    <div class="footer-col">
      <h3>Najdôležitejšie sekcie</h3>
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
    <p>Niektoré odkazy môžu byť affiliate. Pri nákupe cez ne sa cena pre teba nemení, ale web tým získava províziu.</p>
  </div>
</footer>

<script async src="//serve.affiliate.heurekashopping.sk/js/trixam.min.js"></script>
<script src="<?= asset('js/app.js') ?>" defer></script>

</body>
</html>