# Visual System Audit

## Short Audit Summary

Before this pass, the site already had a functional content and affiliate architecture, but the visual layer still had several production-facing weaknesses:
- article hero images were technically organized, but not yet documented as a clean editorial workflow
- product cards often fell back to generic placeholders when no real merchant packshot was available
- top picks and affiliate product blocks had inconsistent visual hierarchy between image, copy, rating and CTA
- comparison tables were structurally usable, but visually too plain for high-trust affiliate content
- product image handling was not yet clearly separated into real merchant packshots vs editorial/generated visuals

## Improvements Implemented

### Product Visual System
- Added canonical target paths for mirrored product packshots.
- Product image handling now prefers a clean local mirror workflow and falls back in this order:
  1. explicit local asset
  2. canonical local mirror target path
  3. approved remote merchant image
  4. product fallback state
- Product metadata now exposes image mode, remote source and target asset path.
- Added a generated manifest for all product image targets in `docs/product-image-manifest.csv`.

### Product Card Design
- Refined top picks cards with stronger visual hierarchy and better spacing.
- Switched product imagery to contained packshot framing instead of generic cover-style cropping.
- Added cleaner merchant pill, rank badge, rating row and packshot status label.
- Improved pros/cons boxes and CTA alignment.
- Reworked affiliate product box layout to better balance image, copy and CTA.
- Improved comparison table support for product-image cells and CTA cells.

### Hero Image Workflow
- Hero prompt metadata now exposes dimensions, target folder, target filename, alt text and style brief.
- Added generated visual briefs for articles in `docs/article-visual-briefs.csv`.
- Existing hero helper remains the interactive UI for prompt copying and review.

### Graphic Consistency
- Unified real packshot framing styles across top picks, product boxes and future comparison blocks.
- Reduced the placeholder look in production commerce areas by using intentional fallback media states instead of awkward image blocks.
- Kept generated/editorial visuals focused on article covers and category visuals, not product packshots.
