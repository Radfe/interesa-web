# Affiliate / Dognet Guide

## Goal

Affiliate links should stay centralized and the visible links on the site should stay clean.
That is why the website uses this flow:
- product catalog
- affiliate link registry
- internal route `/go/<code>`
- reusable CTA and product components

The visitor only sees your internal site link structure.
The real Dognet deeplink lives in the central registry and can be changed without editing articles.

## Main files

- `public/content/products/catalog.php`
- `public/content/affiliates/links.php`
- `public/content/affiliates/merchants.php`
- `public/inc/affiliates.php`
- `public/inc/products.php`
- `public/inc/affiliate-ui.php`
- `public/inc/top-products.php`
- `public/go.php`
- `public/tools/import-dognet-feed.php`
- `public/tools/import-dognet-links.php`

## Clean link strategy

Frontend CTA should point to internal links only, for example:
- `/go/protein-na-chudnutie-gymbeam`
- `/go/kreatin-porovnanie-gymbeam`

That keeps the article HTML clean and lets you replace Dognet links later without touching content.

## Product model

Each product can carry at least:
- `name`
- `slug`
- `merchant`
- `merchant_slug`
- `category`
- `affiliate_code`
- `fallback_url`
- `summary`
- `pros`
- `cons`
- `image`
- `feed_source`
- `merchant_product_id`
- `image_source`

## Merchant / campaign layer

Merchant-level metadata lives in `public/content/affiliates/merchants.php`.
This is the right place for:
- network (`dognet`)
- public campaign page
- cookie window
- validation window
- feed availability
- notes and restrictions

### GymBeam note

Based on Dognet's public campaign page, GymBeam has a public campaign landing page and Dognet lists campaign assets such as XML feeds and promo materials.
I used that to prepare the merchant layer so GymBeam can be treated as a first-class Dognet merchant in the project.

Public source:
- https://www.dognet.sk/kampane/kampan-gymbeam-sk/

Inference:
- the private app campaign link you sent most likely corresponds to the public GymBeam campaign page above

## Link workflow

### Option A: manual update

Add or edit real Dognet deeplinks in:
- `public/content/affiliates/links.php`

This is the simplest path when you only add a few links.

### Option B: CSV import

Use the template:
- `public/storage/dognet/link-import-template.csv`

Then run:

```bash
php public/tools/import-dognet-links.php public/storage/dognet/link-import-template.csv gymbeam
```

The tool outputs a ready PHP array that can be pasted into `public/content/affiliates/links.php`.

Recommended CSV columns:
- `code`
- `url`
- `merchant_slug`
- `product_slug`
- `merchant`

## Feed workflow

Place raw exports in:
- `public/storage/dognet/raw/`

Normalize feed exports with:

```bash
php public/tools/import-dognet-feed.php path/to/feed.csv gymbeam
```

This prepares normalized product rows with fields such as:
- product name
- slug
- merchant slug
- image url
- affiliate url
- merchant product id

## CTA and reusable components

Available helpers:
- `interessa_affiliate_cta_html()`
- `interessa_render_affiliate_disclosure()`
- `interessa_render_product_box()`
- `interessa_render_recommended_product()`
- `interessa_render_comparison_table()`

`public/inc/top-products.php` still handles shortlist rendering, but it now stands on the same centralized affiliate layer.

## Disclosure and SEO

Recommended rules:
- show disclosure on commercial blocks and comparison sections
- keep `/go/` routes `noindex, nofollow`
- avoid hardcoded third-party widgets in article bodies
- keep Dognet URLs out of article HTML where possible

## Practical recommendation

When you get a new approved Dognet campaign:
1. add merchant metadata to `public/content/affiliates/merchants.php`
2. add or import deeplinks into `public/content/affiliates/links.php`
3. connect selected products in `public/content/products/catalog.php`
4. use only internal `/go/<code>` links in content and UI