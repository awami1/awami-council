# CLAUDE.md — AI Assistant Guide for Awami Council

## Project Overview

**Awami Council** (مجلس عائلة العوامي) is a full-stack web platform for managing a real-world family council (Majlis). It handles member management, finances, events, committees, family tree visualization, news publishing, stories/biographies, a cinematic gallery ("Riwaq"), and community engagement — all served as an Arabic RTL website.

- **Repository**: `awami1/awami-council`
- **Production hosting**: CranL (configured via `.cranl`)
- **Primary language**: Arabic (RTL layout throughout)

## Tech Stack

| Layer       | Technology                                        |
|-------------|--------------------------------------------------|
| Backend     | PHP 8.1+ (strict types, PDO)                    |
| Database    | MySQL 8.0+ (production) / SQLite (development)  |
| Frontend    | Vanilla JavaScript (ES6+), CSS3 with custom properties |
| Server      | Nginx (production), PHP built-in server (dev)    |
| Auth        | Session-based with bcrypt + CSRF tokens          |
| PWA         | Service Worker for offline fallback              |
| Deps        | Composer (ext-pdo_mysql, ext-pdo, ext-mbstring only) |

## Project Structure

```
awami-council/
├── index.php              # Entry point — delegates to router.php
├── router.php             # Front controller (routes, AJAX detection, standalone pages)
├── migrate.php            # CLI-only auto-migration — creates all DB tables on startup
├── api/                   # REST API endpoints (one PHP file per resource)
│   ├── config.php         # DB connection, .env loader, shared utilities (getPDO, respond, bodyJson, uid)
│   ├── auth.php           # Login/logout
│   ├── auth_guard.php     # Session auth, CSRF verification (requireAuth, verifyCsrf)
│   ├── audit_helper.php   # Audit logging (logAudit)
│   ├── validation.php     # Shared validation (parseId, sanitizeString)
│   ├── members.php        # Member CRUD + committee assignment
│   ├── payments.php       # Payment records
│   ├── transactions.php   # Financial transactions
│   ├── periods.php        # Financial periods
│   ├── events.php         # Events management (auth-protected)
│   ├── public_events.php  # Public events listing (no auth required)
│   ├── committees.php     # Committee CRUD
│   ├── news.php           # News articles (public GET, auth writes)
│   ├── family-tree.php    # Hierarchical family tree
│   ├── media.php          # Photo/video gallery
│   ├── polls.php          # Community polls
│   ├── settings.php       # Website settings
│   ├── meeting.php        # Next meeting management
│   ├── branches.php       # Family branches
│   ├── messages.php       # Contact messages
│   ├── reminders.php      # Payment reminders
│   ├── reports.php        # Saved smart reports with archiving
│   ├── stories.php        # Family member stories/biographies CRUD
│   ├── gallery-stories.php# Riwaq cinematic gallery stories CRUD
│   ├── audit.php          # Audit log viewer
│   ├── export.php         # CSV export
│   ├── diagnostics.php    # PHP environment and DB connection diagnostics
│   └── setup.php          # DB table creation (auth-protected, disableable via SETUP_DISABLED env)
├── admin/                 # Admin panel (SPA)
│   ├── index.php          # Admin dashboard (monolithic PHP+HTML, ~1280 lines)
│   ├── login.php          # Dedicated admin login page
│   ├── admin-overrides.js # Overrides admin functions to use real API calls
│   ├── admin.db.js        # Data layer — replaces localStorage with API calls (DB cache object)
│   ├── css/admin.css      # Admin styles
│   ├── js/
│   │   ├── admin-core.js  # Core admin utilities
│   │   ├── admin-app.js   # Main admin application logic (~2800 lines)
│   │   ├── admin-import.js# CSV/Excel import functionality
│   │   ├── admin-riwaq.js # Riwaq gallery admin management (~230 lines)
│   │   └── admin-stories.js # Stories/biographies admin management (~370 lines)
│   └── pages/modals.php   # Admin modal templates
├── includes/              # Shared PHP templates for public site
│   ├── layout.php         # HTML wrapper (loads helpers, head, header, footer, scripts)
│   ├── helpers.php        # Public data functions (getWS, getFamilyTree, getDynamicStats, getPublishedStories, getActiveGalleryStories, etc.)
│   ├── head.php           # <head> tag content
│   ├── header.php         # Site navigation (includes Riwaq link with data-no-ajax)
│   └── footer.php         # Site footer
├── pages/                 # Public page templates (included by router)
│   ├── home.php           # Landing page
│   ├── council.php        # Council info page
│   ├── tree.php           # Family tree (D3.js visualization)
│   ├── news.php           # News listing
│   ├── events.php         # Events listing
│   ├── gallery.php        # Media gallery
│   ├── contact.php        # Contact form
│   ├── eid.php            # Eid greeting card generator
│   ├── riwaq.php          # Riwaq — cinematic gallery (standalone page, own HTML)
│   ├── stories.php        # Family stories/biographies listing (SSR + JS pagination)
│   ├── story.php          # Single story detail page (SEO-friendly slugs)
│   └── 404.php            # Not found page
├── public/                # Static assets
│   ├── css/
│   │   ├── variables.css  # Design tokens (colors, fonts, spacing, dark mode)
│   │   ├── base.css       # Reset and base styles
│   │   ├── layout.css     # Layout and section styles
│   │   ├── components.css # Reusable component styles
│   │   ├── animations.css # CSS animations
│   │   ├── riwaq.css      # Riwaq cinematic gallery styles (~675 lines)
│   │   └── stories.css    # Stories/biographies page styles (~700 lines)
│   ├── js/
│   │   ├── api.js         # API client layer (all *API objects: MembersAPI, EventsAPI, etc.)
│   │   ├── ajax-nav.js    # SPA-like AJAX page navigation
│   │   ├── navbar.js      # Navigation behavior
│   │   ├── theme.js       # Dark/light mode toggle
│   │   ├── animations.js  # Scroll-based animations
│   │   ├── countdown.js   # Meeting countdown timer
│   │   ├── tree.js        # D3.js family tree renderer
│   │   ├── news.js        # News page logic
│   │   ├── media.js       # Gallery page logic
│   │   ├── eid.js         # Eid greeting card generator
│   │   ├── settings.js    # Settings loader
│   │   ├── riwaq.js       # Riwaq gallery page logic (~166 lines)
│   │   └── stories.js     # Stories page logic (~389 lines)
│   └── fonts/             # Custom Saudi Arabic fonts (.ttf)
├── core/                  # Shared frontend services (not widely used)
│   ├── state.js
│   └── services/          # finance.service.js, member.service.js, poll.service.js
├── data/                  # SQLite database (dev only, gitignored)
├── assets/                # Static images (Eid templates)
├── .env.example           # Environment variable template
├── .cranl                 # CranL hosting config (port 80)
├── composer.json          # PHP extension requirements (pdo_mysql, pdo, mbstring)
├── Procfile               # Process definition: runs migrate.php then starts server
├── start.sh               # Dev server launch script (PORT/HOST env vars)
├── nginx.conf             # Nginx server configuration
├── .htaccess              # Apache rewrite rules
├── manifest.json          # PWA manifest (Arabic, RTL)
├── sw.js                  # Service Worker (offline fallback)
├── sitemap.php            # Dynamic sitemap generator
└── offline.html           # Offline fallback page
```

## Architecture & Patterns

### Routing
- **Front controller**: All requests route through `index.php` → `router.php`
- Routes are defined as a PHP array in `router.php` mapping paths to page files, scripts, and titles
- **Standalone pages**: Routes with `'standalone' => true` (e.g., `/riwaq`) render their own complete HTML without `layout.php`
- AJAX navigation: Requests with `X-Requested-With: XMLHttpRequest` or `?_ajax=1` return page content without the layout wrapper
- The public site behaves like an SPA via `ajax-nav.js` which intercepts link clicks and loads content via XHR
- `router.php` also protects `migrate.php` from web access on the built-in dev server

### API Pattern
Each API endpoint follows the same structure:
1. `require_once config.php` + `auth_guard.php` + `audit_helper.php`
2. Call `requireAuth()` and `verifyCsrf()` for protected endpoints
3. Define handler functions: `handleGetAll()`, `handleGetOne($id)`, `handlePost()`, `handlePut($id)`, `handleDelete($id)`
4. Route via PHP `match` expression on `$_SERVER['REQUEST_METHOD']` + `$_GET['id']`
5. Return JSON via `respond($code, $body)` (uses `never` return type)
6. Log mutations via `logAudit($action, $entityType, $entityId, $entityName)`

Some endpoints use `ensureXxxTable()` for lazy table creation (e.g., `stories.php`, `gallery-stories.php`, `reports.php`).

### Database
- **Dual-mode**: MySQL for production, SQLite for local development (auto-detected via `.env`)
- Connection via singleton `getPDO()` in `api/config.php`
- IDs are UUIDs generated by `uid()` function
- **Auto-migration**: `migrate.php` runs on startup (via Procfile) with `CREATE TABLE IF NOT EXISTS` — safe to re-run
- `migrate.php` is CLI-only (protected from web access)
- `setup.php` still exists for manual table creation (auth-protected, can be disabled via `SETUP_DISABLED=true`)
- SQL must work with **both** MySQL and SQLite — use `isSQLite()` for dialect differences
- Always use prepared statements (PDO named parameters like `:id`)

### Frontend JavaScript
- **No frameworks** — vanilla JS with module-like API objects
- `api.js` defines all API clients (e.g., `MembersAPI`, `EventsAPI`, `NewsAPI`)
- The admin panel inlines `api.js` via `readfile()` and wraps it with CSRF token injection
- Admin data layer: `admin.db.js` provides an in-memory `DB` cache object backed by real API calls (replaces localStorage)
- `admin-overrides.js` redefines admin functions to use the API layer
- Scripts are loaded per-page via the route config in `router.php` and `ajax-nav.js`

### CSS Architecture
- CSS custom properties for theming (defined in `variables.css`)
- Dark mode via `[data-theme="dark"]` selector
- Split into: `variables.css` → `base.css` → `layout.css` → `components.css` → `animations.css`
- Feature-specific CSS: `riwaq.css`, `stories.css`
- Color palette: emerald green (`#1A5C32`) + navy (`#1B3456`) + gold accent (`#c8a84b`)
- Fonts: Cairo (body), Amiri (headings), Saudi (special display)

### Authentication & Security
- Session-based auth with `SameSite=Strict` cookies
- CSRF protection via `X-CSRF-Token` header on all write operations
- Rate limiting on login (5 attempts / 15 minutes)
- Request body size limit: 64KB via `bodyJson()`
- HTML escaping with `esc()` helper (wraps `htmlspecialchars`)
- Security headers: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `HSTS`
- CORS: restricted to same-origin via `setCorsHeaders()` in `config.php`
- Global exception handler returns JSON (never leaks stack traces to API consumers)
- Audit logging for all admin mutations

## Development Workflow

### Local Setup
```bash
# 1. Clone and enter directory
git clone https://github.com/awami1/awami-council.git
cd awami-council

# 2. Copy and configure environment
cp .env.example .env
# Edit .env — leave DB_HOST empty to use SQLite

# 3. Start dev server
bash start.sh
# Or: php -S localhost:8080 router.php

# 4. Database tables are created automatically via migrate.php
# Or manually: visit /api/setup.php (requires auth)
```

### Running the Server
- **Development**: `bash start.sh` (runs `php -S 0.0.0.0:$PORT router.php`, default port 80)
- **Production**: Nginx + PHP-FPM (see `nginx.conf`)
- **CranL hosting**: Configured via `.cranl` and `Procfile` — runs `php migrate.php && php -S 0.0.0.0:80 router.php`

### No Build Step
There is **no build system** — no npm, no Webpack, no compilation. All CSS and JS files are served directly. Cache-busting uses file modification timestamps via the `asset()` PHP helper.

### Testing
There are **no automated tests** currently. Test manually by:
1. Starting the dev server
2. Navigating to public pages (`/`, `/council`, `/tree`, `/news`, `/events`, `/gallery`, `/contact`, `/eid`, `/riwaq`)
3. Logging into the admin panel (`/admin`) and verifying CRUD operations
4. Testing AJAX navigation between pages (click links, use browser back/forward)
5. Note: `/riwaq` is a standalone page — it loads outside the AJAX navigation system

## Key Conventions

### PHP
- Always use `declare(strict_types=1)` at the top of API files
- Use `respond($code, $body)` for all JSON responses — never echo raw JSON
- Use `bodyJson()` to read request bodies (enforces size limit)
- Use `uid()` for generating new record IDs
- Use `parseId()` from `validation.php` for reading `$_GET['id']` safely
- Use `sanitizeString()` for field validation
- Database queries must use PDO prepared statements with named parameters
- Handle MySQL/SQLite differences with `isSQLite()` checks
- Use `logAudit()` for all data mutations in protected endpoints
- Error messages for user-facing API responses should be in Arabic
- New tables should be added to `migrate.php` as well as `setup.php`
- Endpoints that create their own tables use `ensureXxxTable()` pattern

### JavaScript
- Use `var` in `ajax-nav.js` (IIFE, browser compat). Use `const`/`let` in other modern JS files
- All API calls go through the `api` object or specific API clients (e.g., `MembersAPI.getAll()`)
- Admin panel wraps `apiFetch` to add CSRF tokens and handle 401 redirects automatically
- Page-specific JS initializes via inline `<script>` tags in page templates or dedicated `.js` files
- New admin features should be extracted into separate JS files (e.g., `admin/js/admin-riwaq.js`, `admin/js/admin-stories.js`)

### CSS
- Use CSS custom properties (`var(--green)`, `var(--text)`, etc.) for all colors
- Always support both light and dark themes
- Use the defined radius variables (`--radius`, `--radius-lg`, `--radius-xl`)
- Follow the RTL layout — `direction: rtl` is set on `<html>`
- Feature-specific CSS goes in dedicated files (e.g., `public/css/riwaq.css`)

### File Naming
- API endpoints: lowercase, hyphenated (e.g., `family-tree.php`, `gallery-stories.php`)
- Shared PHP: lowercase, underscored (e.g., `auth_guard.php`, `audit_helper.php`)
- Page templates: lowercase, single-word (e.g., `home.php`, `council.php`, `riwaq.php`)
- JS files: lowercase, hyphenated (e.g., `ajax-nav.js`, `admin-core.js`)
- CSS files: lowercase, descriptive (e.g., `variables.css`, `components.css`)

### Arabic Content
- All user-facing strings (error messages, labels, titles) are in Arabic
- Code comments are primarily in Arabic
- Member statuses use Arabic values: `'نشط'` (active), `'معفي'` (exempt), `'غير نشط'` (inactive)
- Event statuses: `'قادم'` (upcoming), etc.
- Story categories: `'biography'` (سيرة ذاتية), `'self_made'` (قصة عصامية), etc.

## Common Tasks

### Adding a New API Endpoint
1. Create `api/new-resource.php`
2. Include `config.php`, `auth_guard.php`, `audit_helper.php`, `validation.php`
3. Call `requireAuth()` and `verifyCsrf()` if protected
4. Implement `handleGetAll()`, `handleGetOne($id)`, `handlePost()`, `handlePut($id)`, `handleDelete($id)`
5. Add a `match` router at the bottom
6. Write SQL for both MySQL and SQLite (use `isSQLite()`)
7. Add table creation SQL to `migrate.php` (both SQLite and MySQL sections)
8. Add the API client in `public/js/api.js`

### Adding a New Public Page
1. Create `pages/new-page.php` with the page content
2. Add a route entry in `router.php` (path, file, page key, title, scripts)
3. Add navigation link in `includes/header.php`
4. If the page needs JS, create `public/js/new-page.js` and add it to the route's scripts array
5. Also register the page scripts in `ajax-nav.js`'s `pageScripts` map
6. For standalone pages (no shared layout), add `'standalone' => true` to the route and use `data-no-ajax` on the nav link

### Adding a New Admin Section
1. Add the section's HTML to `admin/index.php`
2. Add the section's logic to a new file like `admin/js/admin-feature.js` (preferred) or to `admin/js/admin-app.js`
3. Add navigation in the admin sidebar

### Modifying CSS Theming
1. Add/modify custom properties in `public/css/variables.css` (both `:root` and `[data-theme="dark"]`)
2. Reference them via `var(--property-name)` in other CSS files

## Important Notes

### No package manager
- **Current state**: No npm. Composer is present but only declares PHP extension requirements (no library dependencies). External libraries load from CDNs:
  - D3.js v7 from `d3js.org` (loaded in `includes/head.php` for the tree page)
  - XLSX 0.18.5 from `cdnjs.cloudflare.com` (loaded in `admin/index.php` for CSV import)
  - Google Fonts (Cairo, Amiri, Tajawal) from `fonts.googleapis.com`
- **Impact**: If a CDN goes down, the dependent feature breaks. No lockfile guarantees version consistency
- **Recommendation**: Acceptable for this project's scale. If reliability becomes critical, host local copies in `public/vendor/`

### Auto-migration
- **Current state**: `migrate.php` runs automatically before the server starts (defined in `Procfile`). Uses `CREATE TABLE IF NOT EXISTS` for all tables — safe to re-run
- **Impact**: New tables must be added to `migrate.php` to be created automatically on deployment
- **Recommendation**: When adding a new table, add it to both `migrate.php` (for auto-creation) and `setup.php` (for manual setup). Keep `ensureXxxTable()` in endpoints as a fallback

### Single-file admin
- **Current state**: `admin/index.php` (~1280 lines of PHP+HTML) + `admin/js/admin-app.js` (~2800 lines). Recent features have been extracted into separate JS files (`admin-riwaq.js`, `admin-stories.js`). Additional files: `admin-overrides.js` and `admin.db.js` in the admin root. The admin panel has its own `<head>` (does not use `includes/head.php`) and currently has **no CSP**
- **Impact**: Harder to maintain as features grow. Potential for function name collisions in global scope
- **Recommendation**: Continue extracting new admin features into separate JS files. This is the established pattern

### Cache busting
- **Current state**: The `asset()` function in `includes/helpers.php` appends `?v=<filemtime>` to static file URLs. `window.__ASSET_V__` is available for JS-side cache busting
- **Impact**: Works well for direct browser requests. However, `?v=timestamp` may be ignored by some CDNs/proxies (less effective than content-hash based busting)
- **Recommendation**: Sufficient for the current project. No action needed

### Service Worker
- **Current state**: `sw.js` (27 lines) caches only `offline.html`. Does NOT cache CSS, JS, images, or fonts
- **Impact**: The app does not work offline in any meaningful way — only shows a "you are offline" page when navigation fails
- **Recommendation**: For better offline experience in the future, consider adding precache for CSS files and local fonts. Keep the SW simple — avoid caching API responses

### AJAX navigation
- **Current state**: `ajax-nav.js` intercepts link clicks and loads page content via XHR into `#page-content`. Page-specific scripts are dynamically loaded via the `pageScripts` map. Standalone pages (like Riwaq) use `data-no-ajax` attribute to bypass AJAX navigation
- **Impact**: When adding a new page, scripts must be registered in **two places**: the `scripts` array in `router.php` AND the `pageScripts` object in `public/js/ajax-nav.js`. Missing either causes the page to break on direct load or AJAX navigation respectively
- **Recommendation**: Always register page scripts in both locations. Test both: direct URL access and clicking a link from another page. For full-page experiences, use the standalone pattern

### Sensitive files protection
- **Current state**: Protected files and their server config coverage:
  - `.env` (credentials) — blocked via `.htaccess` (`FilesMatch`) and `nginx.conf` (`location ~ /\.env`)
  - `*.db` (SQLite databases) — blocked via `.htaccess` (`FilesMatch`) and `nginx.conf` (`location ~ \.db$`)
  - `.git/` — blocked via `nginx.conf` (`location ~ /\.git`)
  - `.htaccess` — blocked via `nginx.conf` (`location ~ /\.ht`)
  - `api/config.php` — blocked via `nginx.conf` (`location = /api/config.php`)
  - `migrate.php` — blocked via `router.php` (returns 403) and CLI-only sapi check
- **Note**: PHP's built-in dev server does NOT read `.htaccess`. In development, `router.php` handles routing, but direct requests to `.env` are not blocked by server config. Keep `.env` out of the document root or use Nginx/Apache in production
- **Recommendation**: When deploying, verify that sensitive file access returns 403. Test with: `curl -I https://your-domain/.env`
