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
- product image: local mirrored packshot -> approved remote merchant image -> product placeholder
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
- Products section shows the current packshot preview, source type and target asset path
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

## Product diagnostics and packshot workflow
- Product editor shows affiliate diagnostics, registry source and current target link
- Product editor includes a queue of products that still need a final local packshot
- Product editor shows `Kde sa produkt pouziva`, which lists articles that reference the selected product through admin recommendations or commerce boxes
- Packshot preview now includes quick copy actions for the target asset path and remote source URL
- Queue rows now include direct copy actions for canonical packshot asset paths

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
- Packshot preview and queue rows support copy actions for canonical asset paths

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
- Article editor now shows a `Workflow odporucanych produktov` panel with summary counts for reusable catalog coverage, affiliate readiness, packshot readiness and money-page-ready products
- The same panel surfaces action rows for anything that is still incomplete and can jump directly to product edit, affiliate wiring or packshot workflow
- Missing product slugs can be turned into reusable products directly from the article workflow and returned back into the editor

## Product-to-article round trip
- Product editor can open the article editor with `add_product=<slug>` so a reusable product can be inserted into recommendations without manual copying
- Article editor shows a save notice when a product was injected through this bridge and still needs a normal article save
- Quick-create product flows can now return back to the originating article editor automatically

## Ready-only comparison helper
- Comparison tools now include `Len money-page ready`, which fills rows only from selected reusable products that already have both affiliate wiring and a usable packshot
- This makes it easier to build cleaner money-page comparisons from the shared product catalog without pulling in unfinished product cards
## Product quality queue
- Products section now includes `Queue nedokoncenych produktov`
- This queue surfaces reusable products that are still missing editorial or commerce essentials such as summary, rating, pros, cons, affiliate wiring or a final packshot
- Queue rows expose quick jumps back to product editing, affiliate setup and packshot workflow, so weak product cards can be completed faster

## Faster comparison scaffolds
- Article comparison tools now include `Top 3 ready shortlist`
- This helper builds a short top-picks comparison only from selected reusable products that already have both affiliate wiring and a usable packshot
- Recommended product selector cards now also show affiliate and packshot readiness directly in the picker itself
## Direct packshot uploads in image workflow
- The image workflow for a selected article now supports direct packshot uploads for recommended products that are still missing a final local image
- This works both from the Packshot medzery pre tento clanok queue and from the Produkty v tomto clanku preview cards
- The goal is to close article-specific image gaps without forcing a detour through the full product editor first
## Cross-screen return bridges
- Product and affiliate editors can now be opened from the article image workflow with return context for the selected article
- Product save and affiliate save/create flows can redirect back to the originating article workflow when return context is present
- Product usage diagnostics now link directly into the image workflow of articles where the selected product is used