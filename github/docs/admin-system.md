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
- These flows create lightweight admin overrides first, then open the full editor for enrichment

## Admin image previews
- Products section shows the current packshot preview, source type and target asset path
- Images section shows the current hero preview, source type and target output path
- This makes it easier to verify whether the site is using local WebP, remote merchant image or fallback

## Article recommendation picker
- Article editor now supports checkbox selection of reusable products in addition to manual slug textarea entry
- Manual textarea remains available as a fallback for advanced use

## Comparison and recommendation helpers
- Article editor now includes ready-to-use comparison presets for `top picks` and `duel`
- The comparison editor can also generate rows directly from selected recommended products
- This reduces repetitive manual setup when building money pages and comparison articles

## Product diagnostics and packshot workflow
- Product editor now shows affiliate diagnostics, registry source and current target link
- Product editor also includes a queue of products that still need a final local packshot
- This helps prioritize which merchant images should be mirrored into the canonical local asset paths first

## New admin helpers

- Article editor now shows `Preview odporucanych produktov` so money pages can preview resolved products from admin recommendations or the fallback commerce box.
- Product editor now shows `Kde sa produkt pouziva`, which lists articles that reference the selected product through admin recommendations or commerce boxes.
- Product image workflow now includes `Rychly upload packshotu`, so a local WebP packshot can be uploaded without re-saving the whole product form.
- Product image and hero backlogs are filterable directly in admin, and both CSV exports are working again.`r`n- New article creation now supports a `Typ clanku` starter selector (guide, comparison, review) that prebuilds sections, meta description, and a starter comparison structure.
- Article editor now previews resolved recommended products, while product editor shows article usage diagnostics and a quick packshot-only upload form.
## Current milestone additions
- article creation now supports starter profiles for `guide`, `comparison` and `review`
- save article flow now merges manual recommended products and checkbox-selected products safely
- image backlog export now uses the full hero and packshot queues correctly
- products section now shows where the selected product is used across admin recommendations and commerce boxes
- hero image queue now shows target filename, alt text and dimensions directly in the backlog list