# Images and Assets Guide

## Visual Direction

Use one clear visual language across the website:
- bright minimal backgrounds
- soft pastel greens and neutrals
- realistic editorial or modern stock-photo look
- calm, premium health and fitness styling
- no text inside article hero images

Use generated or editorial visuals mainly for:
- article hero images
- category visuals
- homepage editorial sections

Use real merchant or Dognet-approved packshots mainly for:
- product recommendation cards
- comparison tables
- top picks sections
- affiliate product boxes

## Canonical Asset Structure

The project keeps the existing `public/assets/` root.

Preferred structure:
- `public/assets/img/brand/`
- `public/assets/img/articles/heroes/`
- `public/assets/img/categories/`
- `public/assets/img/products/{merchant_slug}/{product_slug}/main.webp`
- `public/assets/img/placeholders/`
- `public/content/media/articles.php`
- `public/content/media/categories.php`
- `public/content/media/article-hero-prompts.php`

## Naming Rules

Rules:
- lowercase only
- no diacritics
- words separated by hyphens
- filename based on article slug or product slug
- prefer one canonical filename per asset role

Examples:
- `kreatin-porovnanie.webp`
- `kolagen-recenzia.webp`
- `imunita-prirodne-latky-ktore-funguju.webp`
- `public/assets/img/products/gymbeam/gymbeam-true-whey/main.webp`

## Product Image Workflow

Canonical target path for mirrored product images:
- `public/assets/img/products/{merchant_slug}/{product_slug}/main.webp`

Runtime preference order:
1. explicit local asset from catalog metadata
2. canonical local mirrored packshot path
3. approved remote merchant image (`remote_src`)
4. fallback product media state

This means the site can work immediately with merchant feed images, but once you save a local optimized WebP to the canonical mirror path, the site will use it automatically without changing templates.

Generated manifest:
- `docs/product-image-manifest.csv`

Builder:
- `tools/build-product-image-manifest.php`

Recommended product image spec:
- format: WebP
- canvas: 1000x1000 to 1200x1200
- display style: clean packshot with transparent or soft neutral background
- crop: keep the whole pack visible
- compression target: ideally below 250 KB for the final mirrored site asset

## Article Hero Workflow

Hero assets are driven by:
- `public/content/media/articles.php`
- `public/content/media/article-hero-prompts.php`
- `public/inc/hero-prompts.php`
- `public/hero-helper.php`

Generated brief export:
- `docs/article-visual-briefs.csv`

Builder:
- `tools/build-article-visual-briefs.php`

Recommended hero spec:
- format: WebP
- dimensions: 1200x800
- no text in the image
- editorial, realistic and category-specific
- keep a calm premium look, not a noisy collage

Each article hero brief should include:
- article title
- category
- target filename
- target folder
- alt text
- style brief
- prompt text

## Canva / AI Workflow

Use this lightweight workflow:
1. Open `docs/article-visual-briefs.csv` or `/hero-helper`.
2. Copy the prompt and style brief.
3. Generate the image in Canva or another AI tool.
4. Export as `1200x800` WebP.
5. Save it to `public/assets/img/articles/heroes/{slug}.webp`.
6. The site will start using it automatically.

For product images:
1. Check `docs/product-image-manifest.csv`.
2. If a merchant feed image exists, mirror it locally.
3. Save it to the canonical product path.
4. Rebuild the manifest if needed.

## Alt Text Rules

Article hero alt text:
- use the article title or a close descriptive version of it
- keep it relevant to the page topic

Product image alt text:
- use the real product name
- include merchant context only if helpful
- do not stuff keywords unnaturally

## Fallback Rules

If an article hero does not exist yet:
- use the current article hero registry and SVG fallback workflow

If a product packshot does not exist yet:
- prefer remote approved merchant image if available
- otherwise use the intentional fallback media state in the UI
- do not upload random editorial illustrations into product recommendation cards
