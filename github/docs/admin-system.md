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
