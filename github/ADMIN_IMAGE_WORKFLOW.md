# Admin Image Workflow

Updated: 2026-03-13

## Open The Admin

1. Start the local site.
2. Open `http://127.0.0.1:5000/_admin/` in your browser.
3. If the site is deployed on a domain, open `https://your-domain/_admin/`.

## Log In

- Username: `admin`
- Password: `interesa-admin`

After login you land on the article dashboard.

## Edit An Article

1. Use the search field if you want to find a slug or article title quickly.
2. Click `Upravit` on the article card you want.
3. The edit screen shows:
   - hero preview
   - card preview
   - image fields
   - image brief
   - Canva prompt
   - product image fields if the article has a product file in `public/content/products/`

## Assign Or Replace A Hero Image

1. Open the article edit screen.
2. Paste a local path or external URL into `Hero image path / URL`.
3. Update `Hero alt text` if needed.
4. Click `Ulozit zmeny`.
5. Open `Otvorit frontend` to confirm the hero image on the live article page.

Notes:

- Best local path pattern: `/assets/img/articles/your-image.webp`
- If hero is empty, the site uses a detected article asset or an editorial fallback visual.

## Assign Product Images

1. Stay on the same article edit screen.
2. Go to `Produktove packshoty`.
3. For each product row paste a local asset path or a direct merchant packshot URL into `Packshot path / URL`.
4. Optionally adjust:
   - product name
   - subtitle
   - affiliate code
   - fallback URL
5. Click `Ulozit zmeny`.
6. Re-open the frontend article and confirm the product block.

Notes:

- Product rows appear only for articles that already have a matching file in `public/content/products/<slug>.php`.
- Preferred workflow is real merchant packshots for product rows.

## Use The Image Brief / Canva Prompt Workflow

1. Open the article edit screen.
2. In `Image brief`, write the editorial intent:
   - what the image should communicate
   - what objects should appear
   - what mood/style to use
   - what must be avoided
3. In `Canva prompt`, store the final prompt you actually use in Canva.
4. Save even if the hero image is not ready yet.
5. Generate the image in Canva.
6. Export it to `public/assets/img/articles/`.
7. Paste the final local asset path into `Hero image path / URL`.
8. If you want a different crop for cards, also fill `Card image path / URL`.
9. Save again and verify the frontend preview.

## Practical Recommended Flow

1. Write or update the article.
2. Open the admin article screen.
3. Fill `Image brief`.
4. Create the final Canva prompt.
5. Generate and export the hero visual.
6. Paste the hero path.
7. Paste product packshots for any product rows.
8. Save.
9. Check homepage, article cards, category cards, and the full article page.
