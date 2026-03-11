# Image Backlog

Aktuálne má každý článok vlastný SVG hero fallback v `public/assets/img/articles/heroes/`.
Finálny stav je nahradiť najdôležitejšie články realistickými WebP vizuálmi z Canvy alebo iného grafického nástroja.

## Priorita 1
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

## Workflow
1. Vytvor hero obrázok podľa promptu zo shotlistu.
2. Exportuj WebP približne 1200x800 a pod 350 KB.
3. Ulož súbor do `public/assets/img/articles/heroes/` presne podľa slug názvu.
4. Obnov článok v prehliadači. WebP automaticky nahradí dočasný SVG fallback.