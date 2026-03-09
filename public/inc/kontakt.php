<?php
$page = [
  'title'       => 'Kontakt – Interessa',
  'description' => 'Napíš nám cez kontaktný formulár alebo email.',
  'og_type'     => 'article',
];
include __DIR__ . '/inc/head.php';
?>

<section class="content-main">
  <div class="container">
    <h1>Kontaktujte nás</h1>
    <p>Máte otázku, tip na článok alebo spätnú väzbu? Napíšte nám cez formulár nižšie.</p>

    <form action="/odoslat.php" method="post" class="form-kontakt">
      <label for="meno">Meno</label>
      <input type="text" id="meno" name="meno" required>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" required>

      <label for="sprava">Správa</label>
      <textarea id="sprava" name="sprava" rows="6" required></textarea>

      <button type="submit" class="btn">Odoslať</button>
    </form>
  </div>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>
