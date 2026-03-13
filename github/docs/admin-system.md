# Admin system

## Architecture
- Route: `/admin`
- Storage: flat-file JSON in `public/storage/admin`
- Articles: one override file per slug in `public/storage/admin/articles/<slug>.json`
- Products: shared catalog overrides in `public/storage/admin/products.json`
- Affiliate links: admin-managed link overrides in `public/storage/admin/affiliate-links.json`
- Images: uploaded hero and product images are stored in the canonical asset paths already used by the frontend
- Auth: lightweight session login via `public/inc/admin-auth.php`

## Login
## Practical daily use
The admin is meant to support the public website, not replace it.

Recommended order for normal work:
1. open the article in `Clanky`
2. update title, intro, sections and comparison content
3. add reusable products
4. generate or upload the hero image
5. upload missing product images
6. finish Dognet affiliate links
7. open the live page and verify the frontend

Shortcut:
- use `Pomoc / quickstart` inside `/admin` for step-by-step guidance
- use `Image briefy` for Canva / AI prompts and final upload paths
- use `Produkty` for reusable product quality, product images and affiliate diagnostics
- use `Affiliate odkazy` only when the final `/go/` target still needs to be connected

- Default local password: `interesa-admin`
- Recommended: create `public/storage/admin/auth.php` based on `public/storage/admin/auth.example.php`
- Real `auth.php` is ignored by Git

## Article management
The admin panel edits structured article data without replacing the existing flat-file architecture.

Editable fields:
- title
- intro
- meta title
- meta description
- sections
- comparison block
- recommended product slugs
- hero image

Frontend behavior:
- if an article has no admin sections, the original HTML article still renders
- if admin structured content exists, the article renderer uses the admin sections, comparison block and recommended products
- article meta and category listings read admin overrides automatically

## Comparison editor
The article screen now supports two comparison workflows:
- visual editor for columns and rows
- advanced JSON fallback for edge cases

Recommended use:
- use the visual editor for normal comparison blocks
- keep JSON only for advanced manual structures

## Product management
Products remain reusable across multiple articles.

Editable fields:
- name
- brand
- merchant
- merchant slug
- category
- affiliate code
- fallback URL
- rating
- summary
- pros / cons
- remote image URL
- local product image upload

Uploaded product images are stored in:
- `public/assets/img/products/<merchant-slug>/<product-slug>/main.webp`

## Image workflow
1. Open `/admin?section=images&slug=<article-slug>`
2. Copy the generated brief / prompt
3. Create the visual in Canva or AI tool
4. Export as WebP in `1200x800`
5. Upload the hero image in admin
6. The article automatically uses the new hero asset

Practical guide:
- `docs/admin-images-workflow-sk.md`

## Canva / AI prompt generation
The brief generator uses the hero prompt registry and article metadata.
It outputs:
- prompt
- target filename
- alt text
- dimensions
- target asset path

## Import / export tools
The admin tools section now supports:
- full admin bundle export to JSON
- full admin bundle import from JSON
- product feed import from XML or CSV
- batch hero brief export to CSV

## Feed import
Feed import uploads XML or CSV locally through the admin panel.
The importer:
- parses merchant feed rows
- creates admin product overrides
- preserves the existing frontend architecture
- prepares products for later affiliate and image enrichment

## Fallback rules
- article hero: admin hero asset -> media registry asset -> canonical article hero asset -> category visual fallback
- product image: local mirrored product image -> approved remote merchant image -> product placeholder
- affiliate links: admin link override -> PHP registries -> CSV fallback

## Reset and delete actions
- article overrides can be reset without touching the original HTML article
- product overrides can be deleted without removing the base catalog
- affiliate link overrides can be deleted without touching the PHP registries
- all destructive admin actions now use browser confirmation before submit

## Admin dashboard
- authenticated admin now shows summary cards for article overrides, products, affiliate codes and final hero WebP coverage
- the images section now includes a backlog queue of articles that still need a final hero WebP

## AI status dashboard
- Root status file: `AGENT_STATUS.md`
- Admin route: `/admin/ai-status`
- Reads current branch, current task, next task, last completed task, modified files and progress bars
- Helper functions live in `public/inc/agent-status.php`
- CLI milestone updater: `php tools/update-agent-status.php --current-task="..." --last-completed="..."`

## Status update rule
Update `AGENT_STATUS.md` only when:
- a major feature starts
- a milestone is completed
- the branch changes
- a significant set of modified files changes

## Quick create workflows
- Products section now includes a quick-create form for new reusable products
- Affiliate section now includes a quick-create form for new `/go/` codes
- Article creation now includes starter profiles for `guide`, `comparison` and `review`
- These flows create lightweight admin overrides first, then open the full editor for enrichment

## Admin image previews
- Products section shows the current product image preview, source type and target asset path
- Images section shows the current hero preview, source type and target output path
- This makes it easier to verify whether the site is using local WebP, remote merchant image or fallback

## Article recommendation picker and preview
- Article editor supports checkbox selection of reusable products in addition to the manual slug textarea
- Manual textarea remains available as a fallback for advanced use
- The editor now also renders `Preview odporucanych produktov`, so selected products can be checked visually before saving
- Each preview card can jump directly to the product editor or open the current target URL

## Comparison and recommendation helpers
- Article editor includes ready-to-use comparison presets for `top picks` and `duel`
- The comparison editor can generate rows directly from selected recommended products
- This reduces repetitive manual setup when building money pages and comparison articles

## Product diagnostics and product-image workflow
- Product editor shows affiliate diagnostics, registry source and current target link
- Product editor includes a queue of products that still need a final local product image
- Product editor shows `Kde sa produkt pouziva`, which lists articles that reference the selected product through admin recommendations or commerce boxes
- Product image preview now includes quick copy actions for the target asset path and remote source URL
- Queue rows now include direct copy actions for canonical product-image asset paths
- If a product already has an approved remote merchant image, the admin can now mirror it locally via `Zrkadlit remote`
- This creates a canonical local product-image asset without forcing a manual download / upload round trip first

## Hero workflow helpers
- Hero image backlog is filterable and exports correctly to CSV
- Backlog rows now show target filename, alt text and dimensions directly in the queue
- Both the hero detail card and backlog rows now support direct prompt/path copy actions
- Image workflow is bridged directly from the article editor and can jump straight to `/hero-helper`

## Current editing bridges
- Article editor can jump directly to the image workflow, hero helper and live article preview
- Recommended product preview cards can jump directly to the product editor, affiliate editor and current live target
- Recommended product selector cards now include direct actions for product edit, affiliate edit, live target and slug copy
- Product editor can jump directly to the affiliate editor for the selected product code
- Product-image preview and queue rows support copy actions for canonical asset paths

## Richer comparison helpers
- Article editor now includes a `Preset katalog` option for product-driven comparison tables
- `Riadky z odporucanych produktov` now fills comparison rows from selected reusable products with merchant and rating metadata where available
- This makes it much faster to assemble initial money-page comparison blocks from the shared product catalog
## Affiliate-gap queue and quick create
- Product management now includes a queue of products with missing or unresolved affiliate wiring
- Rows can jump directly to the product editor, the affiliate editor, or a prefilled affiliate create flow
- The affiliate quick-create form accepts prefilled code, merchant, merchant slug and product slug from admin workflow links

## Product quick-create ergonomics
- Product quick-create now supports fallback URL, remote image URL and short summary seeding
- Name and merchant inputs can auto-fill slug and merchant slug in the admin UI
- Product quick-create also accepts prefilled values through admin workflow links for future bridge extensions

## Recommendation workflow diagnostics
- Article editor now shows a `Workflow odporucanych produktov` panel with summary counts for reusable catalog coverage, affiliate readiness, image readiness, money-page-ready products and fully reusable-card-ready products
- The same panel surfaces action rows for anything that is still incomplete and now also shows readiness percentages plus missing editorial/commercial areas for each product
- Missing product slugs can be turned into reusable products directly from the article workflow and returned back into the editor

## Product-to-article round trip
- Product editor can open the article editor with `add_product=<slug>` so a reusable product can be inserted into recommendations without manual copying
- Article editor shows a save notice when a product was injected through this bridge and still needs a normal article save
- Quick-create product flows can now return back to the originating article editor automatically

## Ready-only comparison helper
- Comparison tools now include `Len money-page ready`, which fills rows only from selected reusable products that already have both affiliate wiring and a usable product image
- This makes it easier to build cleaner money-page comparisons from the shared product catalog without pulling in unfinished product cards
## Product quality queue
- Products section now includes `Queue nedokoncenych produktov`
- This queue surfaces reusable products that are still missing editorial or commerce essentials such as summary, rating, pros, cons, affiliate wiring or a final product image
- Queue rows expose quick jumps back to product editing, affiliate setup and image workflow, so weak product cards can be completed faster

## Faster comparison scaffolds
- Article comparison tools now include `Top 3 hotove vybery`
- This helper builds a short top-picks comparison only from selected reusable products that already have both affiliate wiring and a usable product image
- Recommended product selector cards now also show affiliate and image readiness directly in the picker itself
## Direct product-image uploads in image workflow
- The image workflow for a selected article now supports direct product-image uploads for recommended products that are still missing a final local image
- This works both from the queue of missing product images for the article and from the product preview cards in the article image workflow
- The goal is to close article-specific image gaps without forcing a detour through the full product editor first
- When a recommended product already has a remote merchant image, the same workflow can now use `Zrkadlit remote` to pull that image into the canonical local asset path in one click
## Cross-screen return bridges
- Product and affiliate editors can now be opened from the article image workflow with return context for the selected article
- Product save and affiliate save/create flows can redirect back to the originating article workflow when return context is present
- Product usage diagnostics now link directly into the image workflow of articles where the selected product is used
## Richer comparison and recommendation helpers
- Article comparison tools now include Money-page scaffold, which can build a top-picks comparison from selected reusable products and fill basic comparison/article copy when fields are still empty
- Article comparison tools now also include Porovnanie -> produkty, which syncs product slugs from the current comparison table back into the recommended-products workflow
- This reduces duplicate manual editing between comparison blocks and article recommendation lists on money pages
## Faster reusable-product drafting
- Product editor now includes quick scaffold buttons for Starter summary, Starter plusy, Starter minusy, Iba doplnit prazdne and Vyplnit vsetko
- The same editor now includes rating preset buttons plus an Auto rating helper for quicker first-pass scoring
- A live checklist shows whether summary, rating, pros, cons, affiliate wiring and the product image are already ready for the selected reusable product
- These drafts are generated from the current product name, brand, merchant and category so reusable products can be prepared much faster before manual refinement
- The goal is to reduce repetitive typing when building product cards for comparisons, top picks and money pages
## Live money-page polish
- Money-page scaffold now prefers fully card-ready reusable products first, then falls back to money-page-ready ones if needed
- Recommended-product diagnostics in the article editor now distinguish between basic money-page readiness and a fully reusable-card-ready state
- Live affiliate product boxes now use richer product summaries and can render rating plus real-image status when the reusable product record contains that data


## In-admin help
- Admin now includes `Pomoc / quickstart` directly in the sidebar
- The help screen explains the normal order of work:
  - article first
  - then reusable products and comparison blocks
  - then hero images and product images
  - then Dognet affiliate links
  - finally the live frontend check
- The goal is to make daily usage possible without remembering internal file paths

## Money-page image gap report
- Tools now include a direct `Money page image gaps` overview
- It groups the remaining missing real product images by article, so you can close image debt article-by-article instead of searching through the whole catalog
- Each row links back to the article image workflow and the live article preview
- Rows also expose the target asset path and direct product access, so the gap report can be used as a practical work queue
- The same report now supports merchant filters directly in Tools, so you can work merchant-by-merchant when you want to close all remaining image debt for GymBeam, Aktin, Myprotein or another shop in one pass
- Tools now also expose merchant batch cards with one-click `Otvorit vyrez` and `Export CSV` actions, so the image work can be tackled merchant-by-merchant without manually setting up the filter every time
- When a merchant filter is active, Tools also show a `Batch brief pack` textarea that can be copied in one click and pasted into Canva / AI workflow as a whole merchant batch
- Tools can also export the currently filtered money-page image gaps as CSV, including the packshot brief fields:
  - target asset path
  - filename
  - alt text
  - dimensions
  - prompt
- The packshot brief is now generated even for lightweight product references that do not yet have a full reusable catalog record, so every missing row in the report stays actionable

## Product packshot brief
- Product editing now includes a lightweight `Packshot brief` block whenever the selected product still has no real product image
- The brief provides:
  - prompt
  - target filename
  - alt text
  - dimensions
  - canonical target path
- It now also includes a direct reference-product URL and a short merchant-specific note, so the fallback packshot can stay closer to the visual style of the real merchant product page
- This is meant as a fallback workflow after checking whether a real merchant packshot can be mirrored first

## Article image workflow bridge
- The article-level `Images` workflow now also surfaces packshot briefs for recommended products that still miss a real product image
- This means image work can now be closed article-by-article from one screen:
  - hero prompt
  - missing product-image queue
  - packshot prompt copy
  - reference product link
  - direct product jump
  - direct upload
- The article-level image workflow and the tools gap report now use the same packshot-brief data, so there is a single fallback prompt/path workflow no matter where you start

## Automatic merchant-page enrichment
- Product editing now supports `Zistit data z produktu`, which pulls basic product data from the partner product page referenced in `fallback_url`
- The same workflow can populate missing:
  - product name
  - brand
  - short summary
  - remote product image URL
- Product editing also supports `Automaticky doplnit`, which does the same enrichment and then tries to mirror the discovered remote image into the canonical local asset path
- `Money page image gaps` now supports a batch action `Skusit doplnit z produktu` for the current merchant filter or current filtered gap list
- Merchant batch cards in Tools also expose `Auto doplnit`, so remaining image gaps can be handled merchant-by-merchant instead of only product-by-product

## Practical operator docs
- Daily admin usage is documented in `docs/admin-pouzitie-sk.md`
- Product-image workflow is documented in `docs/admin-images-workflow-sk.md`
- Dognet workflow is documented in `docs/admin-dognet-workflow-sk.md`
- CSV batch import can start from `docs/affiliate-import-template.csv`

