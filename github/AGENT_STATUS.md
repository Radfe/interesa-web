# AGENT STATUS

Updated: 2026-03-13

## Current State

- Frontend is a file-based PHP site under `public/`.
- Shared rendering helpers live mainly in `public/inc/functions.php`.
- Homepage uses a mix of hardcoded hero/card images and helper-driven category/article links.
- Article cards and category article cards use `article_img($slug)`.
- `article_img($slug)` currently falls back to the same `placeholder-16x9.svg` for most articles.
- Existing article image discovery only matches exact filenames like `{slug}.webp`, but current assets already include responsive files like `najlepsi-protein-na-chudnutie-wpc-vs-wpi-1600.webp`.

## Admin/Image Workflow Findings

- `public/_admin/` currently contains only `info.php` and `tree.php`.
- There is no working login flow, article edit screen, or image assignment UI in this branch.
- Product image data exists only as per-article PHP arrays in `public/content/products/`.
- Product rendering helpers exist in `public/inc/top-products.php`, but the current article flow is not yet using an admin-managed image source.

## Priority Direction

1. Add a shared media layer without rebuilding the architecture.
2. Remove duplicate placeholder behavior on homepage and article/category cards.
3. Make article, category, and homepage card visuals resolve through consistent helpers.
4. Add a practical lightweight admin flow for hero images, product images, and Canva/image briefs.
5. Update this file again after implementation milestones are complete.
