# Winsun KitLab — Dynamic Teamwear Configurator

A self-hosted, responsive 2D sportswear designer inspired by the *general workflow* of online kit designers, with its own implementation and PHP administration backend. This is an independently created project, **not a copy of OneSport/DYOBuild proprietary code or templates**.

## What is implemented

- Six-step responsive storefront: **Sport → Product → Template/Colours → Branding → Team/Sizes → Quote**.
- Categories and nested subcategories, products, and design templates managed in the admin panel.
- Five built-in garment vector shapes (jersey, rugby jersey, singlet, hoodie, polo) and six pattern styles.
- Four editable garment colours with valid HEX controls, shuffle, and grouped inspiration presets.
- Simultaneous front/back editable SVG mockups; selectable and draggable logo / text / number overlays, scale, rotate, change text colour.
- Secure raster uploads: PNG, JPG, WEBP (3 MB per file); logos remain in saved designs.
- Roster entry and CSV roster import (`name,number,size`), or sizes and quantity mode.
- Self-contained **SVG download**, transparent **PNG download**, browser **Print / Save as PDF** sheet.
- Save/share/reopen a design via generated link.
- Server-side saved designs, quote submissions, and order status management.
- Admin sign-in, edit/create/delete categories, products and design templates, business settings, password change, order status.
- CSRF protection, upload MIME verification, filename sanitization, server-side payload validation, session rate-limits and password hashing.

## Requirements

- PHP 8.1+ and Apache (or nginx configured for PHP). No Composer and no database extension required.
- HTTPS for production; the web server needs write access to `storage/` and `public/uploads/`.
- A web-server document root pointed to the **`public/` directory**, not the project root.

## Local start

```sh
cd winsun-kitlab
php -S 127.0.0.1:8000 -t public
```

Open `http://127.0.0.1:8000/setup.php` and create an administrator with a strong password (minimum 12 characters). Then go to:

- Customer studio: `http://127.0.0.1:8000/`
- Administrator: `http://127.0.0.1:8000/admin.php`

On first boot the app creates example categories, products and templates, but leaves the administrator unconfigured until setup. There is **no default admin password**.

## cPanel / production hosting

1. Upload the extracted `winsun-kitlab` directory outside `public_html` if your host permits, e.g. `/home/account/winsun-kitlab`.
2. Create a subdomain with document root `/home/account/winsun-kitlab/public`.
3. Ensure PHP 8.1+ and writable `/home/account/winsun-kitlab/storage` and `/home/account/winsun-kitlab/public/uploads`.
4. Turn on SSL, visit `/setup.php`, create a unique strong administrator password.
5. Test upload, saving, reopening a design, PDF/PNG/SVG export and quote submission with your real domain.
6. Back up `storage/` and `public/uploads/` regularly. Test restores.

**Security:** Do not point document root at the repository root. The `app/` and `storage/` directories must never be public. The `public/uploads/.htaccess` prevents script execution on Apache; configure an equivalent nginx rule if relevant. Setup locks after the first admin configuration.

## Admin workflow

Open **Catalog manager** to create sports (categories), subcategories (choose a parent), products (assign a category and silhouette), and then design templates (assign to a product, pick one of six patterns, choose four colours). These changes appear on the customer's site when it loads or refreshes.

On the **Orders** tab, you can view customer requests, related editable design links, sizes, roster, and change order status from `New` through `Dispatched`/`Closed`.

On **Settings**, edit the brand name, public contact email and minimum order quantity; change your admin password.

## Important scope limitations

1. This is a **genuine PHP-backed interactive 2D SVG MVP**, not a finished enterprise manufacturing system. Its built-in garment outlines are illustrative mockups, **not factory-certified panel patterns**, seam allowances, print bleed, size grading or production nesting layouts.
2. **There is no 3D GLB renderer** in this release. Three.js GLB/UV mapping, real 3D garment assets, advanced lighting and 360° GIF output require separate assets and implementation. Do not mistake the vector preview for 3D.
3. **PDF** is browser Print / Save as PDF (typically vector output), not an automatically generated PDF download file. The resulting PDF is a quote/visual reference, not a fully editable Illustrator production package.
4. Quote submissions **are saved in the admin dashboard**; **no SMTP email is sent**. Email notifications, payments, delivery automation and full order lifecycle integrations must be wired to your own service.
5. Storage uses **file-locked JSON files**, designed to install on typical shared PHP hosting without database configuration. For high-traffic or multi-admin use, migrate to **MySQL/MariaDB or PostgreSQL**, with scheduled backups, role permissions and a server-side rate limiting service/WAF.
6. Roster import is CSV, not native XLSX. You can export XLSX as CSV before importing.
7. Public share links are unlisted, not private authenticated documents. Avoid putting confidential information in shared designs.
8. SMTP, 3D, production-ready PDF/SVG with precise cut patterns, payment gateways, mockup approvals, admin uploads of arbitrary garment meshes and live fabric rendering have **not** been implemented or tested.

## Data collections

`storage/categories.json`, `products.json`, `templates.json`, `designs.json`, `orders.json`, `settings.json`, `admin.json` are created as needed. They should be backed up together with `public/uploads`.

## Testing completed

- PHP syntax lint completed on all PHP files.
- Node JavaScript syntax parse completed for the editor.
- Test server HTTP flow verified: setup → catalog loaded (6 categories, 5 products, 30 templates) → saved design → reopened saved design → submitted order → admin login → order visible in admin dashboard.
- Full automated cross-browser, mobile touch, print driver and production-hosting compatibility tests **have not** been completed.

## Suggested next build phase

Migrate storage to MySQL; implement professionally UV-mapped model uploads and layered SVG artwork, production pattern validation, precise 3D/2D colour handling, GIF exporter, true PDF generation, email via PHPMailer, customer authentication, private saved drafts, image optimization, order permissions, and role-based users. Obtain your own properly licensed models and garment patterns for accuracy.

## Railway deployment (Docker + persistent volume)

This archive includes `Dockerfile`, `start.sh`, and `router.php`. Upload the contents of the `winsun-kitlab` folder to the root of a GitHub repository. Connect the repo to `winsun-kitlab-web` in the Railway project `winsun-kitlab-demo`. The already-created persistent volume is mounted at `/data`; the start script symlinks `storage/` and `public/uploads/` there. Generate a public domain only after a successful deploy, then visit `/setup.php` and create an admin password before sharing the URL publicly.
