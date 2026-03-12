# Image Backlog

Every article already has its own SVG fallback in `public/assets/img/articles/heroes/`.
The next step is to replace the first 10 commercially important articles with final WebP heroes.

## Priority 1
- `protein-na-chudnutie.webp`
- `kreatin-porovnanie.webp`
- `kolagen-recenzia.webp`
- `horcik-ktory-je-najlepsi-a-preco.webp`
- `imunita-prirodne-latky-ktore-funguju.webp`
- `pre-workout-ako-vybrat.webp`
- `probiotika-ako-vybrat.webp`
- `veganske-proteiny-top-vyber-2025.webp`
- `najlepsie-proteiny-2025.webp`
- `najlepsi-protein-na-chudnutie-wpc-vs-wpi.webp`

## Working files
- `docs/hero-priority-batch-sk.md`
- `docs/hero-priority-batch.csv`
- `public/inc/hero-prompts.php`
- `public/content/media/article-hero-prompts.php`
- `tools/build-hero-priority-batch.php`

## Workflow
1. Open the priority batch.
2. Generate or edit the WebP using the prompt.
3. Save the file into `public/assets/img/articles/heroes/` using the exact slug name.
4. Refresh the article or `hero-helper` and check that the SVG fallback is gone.
