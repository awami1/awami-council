# CLAUDE.md — AI Assistant Guide for Awami Council

## Project Overview

**Awami Council** (مجلس عائلة العوامي) is a full-stack web platform for managing a real-world family council (Majlis). It handles member management, finances, events, committees, family tree visualization, news publishing, and community engagement — all served as an Arabic RTL website.

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

## Project Structure

```
awami-council/
├── index.php              # Entry point — delegates to router.php
├── router.php             # Front controller (routes, AJAX detection)
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
│   ├── events.php         # Events management
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
│   ├── audit.php          # Audit log viewer
│   ├── export.php         # CSV export
│   └── setup.php          # DB table creation
├── admin/                 # Admin panel (SPA)
│   ├── index.php          # Admin dashboard (single monolithic PHP+HTML file, ~1000 lines)
│   ├── css/admin.css      # Admin styles
│   ├── js/
│   │   ├── admin-core.js  # Core admin utilities
│   │   ├── admin-app.js   # Main admin application logic (~2350 lines)
│   │   └── admin-import.js
│   └── pages/modals.php   # Admin modal templates
├── includes/              # Shared PHP templates for public site
│   ├── layout.php         # HTML wrapper (loads helpers, head, header, footer, scripts)
│   ├── helpers.php        # Public data functions (getWS, getFamilyTree, getDynamicStats, etc.)
│   ├── head.php           # <head> tag content
│   ├── header.php         # Site navigation
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
│   └── 404.php            # Not found page
├── public/                # Static assets
│   ├── css/
│   │   ├── variables.css  # Design tokens (colors, fonts, spacing, dark mode)
│   │   ├── base.css       # Reset and base styles
│   │   ├── layout.css     # Layout and section styles
│   │   ├── components.css # Reusable component styles
│   │   └── animations.css # CSS animations
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
│   │   └── settings.js    # Settings loader
│   └── fonts/             # Custom Saudi Arabic fonts (.ttf)
├── core/                  # Shared frontend services (not widely used)
│   ├── state.js
│   └── services/          # finance.service.js, member.service.js, poll.service.js
├── data/                  # SQLite database (dev only, gitignored)
├── assets/                # Static images (Eid templates)
├── .env.example           # Environment variable template
├── .cranl                 # CranL hosting config
├── Procfile               # Process definition for hosting
├── start.sh               # Dev server launch script
├── nginx.conf             # Nginx server configuration
├── .htaccess              # Apache rewrite rules
├── manifest.json          # PWA manifest (Arabic, RTL)
├── sw.js                  # Service Worker (offline fallback)
├── sitemap.php            # Dynamic sitemap generator
├── offline.html           # Offline fallback page
├── .claude/               # Claude Code configuration
│   ├── settings.json      # Project permissions & hooks
│   ├── hooks/
│   │   └── check-page-sync.sh  # Auto-validates router↔ajax-nav sync
│   └── skills/
│       ├── new-api-endpoint/SKILL.md  # Skill: generate full API endpoint
│       ├── new-page/SKILL.md          # Skill: generate page + dual-register
│       └── code-review/SKILL.md       # Skill: review code per project standards
└── docs/                  # Architecture documentation
    ├── architecture.md    # System architecture overview
    ├── decisions/         # Architecture Decision Records (ADR)
    │   └── 001-template.md
    └── runbooks/          # Operational guides
        ├── add-feature.md
        └── database-changes.md
```

## Architecture & Patterns

### Routing
- **Front controller**: All requests route through `index.php` → `router.php`
- Routes are defined as a PHP array in `router.php` mapping paths to page files, scripts, and titles
- AJAX navigation: Requests with `X-Requested-With: XMLHttpRequest` or `?_ajax=1` return page content without the layout wrapper
- The public site behaves like an SPA via `ajax-nav.js` which intercepts link clicks and loads content via XHR

### API Pattern
Each API endpoint follows the same structure:
1. `require_once config.php` + `auth_guard.php` + `audit_helper.php`
2. Call `requireAuth()` and `verifyCsrf()` for protected endpoints
3. Define handler functions: `handleGetAll()`, `handleGetOne($id)`, `handlePost()`, `handlePut($id)`, `handleDelete($id)`
4. Route via PHP `match` expression on `$_SERVER['REQUEST_METHOD']` + `$_GET['id']`
5. Return JSON via `respond($code, $body)` (uses `never` return type)
6. Log mutations via `logAudit($action, $entityType, $entityId, $entityName)`

### Database
- **Dual-mode**: MySQL for production, SQLite for local development (auto-detected via `.env`)
- Connection via singleton `getPDO()` in `api/config.php`
- IDs are UUIDs generated by `uid()` function
- Schema creation: Each endpoint has a `?setup=1` route or uses `setup.php`
- SQL must work with **both** MySQL and SQLite — use `isSQLite()` for dialect differences
- Always use prepared statements (PDO named parameters like `:id`)

### Frontend JavaScript
- **No frameworks** — vanilla JS with module-like API objects
- `api.js` defines all API clients (e.g., `MembersAPI`, `EventsAPI`, `NewsAPI`)
- The admin panel inlines `api.js` via `readfile()` and wraps it with CSRF token injection
- Scripts are loaded per-page via the route config in `router.php` and `ajax-nav.js`

### CSS Architecture
- CSS custom properties for theming (defined in `variables.css`)
- Dark mode via `[data-theme="dark"]` selector
- Split into: `variables.css` → `base.css` → `layout.css` → `components.css` → `animations.css`
- Color palette: emerald green (`#1A5C32`) + navy (`#1B3456`) + gold accent (`#c8a84b`)
- Fonts: Cairo (body), Amiri (headings), Saudi (special display)

### Authentication & Security
- Session-based auth with `SameSite=Strict` cookies
- CSRF protection via `X-CSRF-Token` header on all write operations
- Rate limiting on login (5 attempts / 15 minutes)
- Request body size limit: 64KB via `bodyJson()`
- HTML escaping with `esc()` helper (wraps `htmlspecialchars`)
- Security headers: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `HSTS`
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

# 4. Initialize database tables
# Visit /api/setup.php or individual endpoints with ?setup=1
```

### Running the Server
- **Development**: `php -S 0.0.0.0:80 router.php` (or use `start.sh`)
- **Production**: Nginx + PHP-FPM (see `nginx.conf`)
- **CranL hosting**: Configured via `.cranl` and `Procfile`

### No Build Step
There is **no build system** — no npm, no Webpack, no compilation. All CSS and JS files are served directly. Cache-busting uses file modification timestamps via the `asset()` PHP helper.

### Testing
There are **no automated tests** currently. Test manually by:
1. Starting the dev server
2. Navigating to public pages (`/`, `/council`, `/tree`, `/news`, `/events`, `/gallery`, `/contact`, `/eid`)
3. Logging into the admin panel (`/admin`) and verifying CRUD operations
4. Testing AJAX navigation between pages (click links, use browser back/forward)

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

### JavaScript
- Use `var` in `ajax-nav.js` (IIFE, browser compat). Use `const`/`let` in other modern JS files
- All API calls go through the `api` object or specific API clients (e.g., `MembersAPI.getAll()`)
- Admin panel wraps `apiFetch` to add CSRF tokens and handle 401 redirects automatically
- Page-specific JS initializes via inline `<script>` tags in page templates or dedicated `.js` files

### CSS
- Use CSS custom properties (`var(--green)`, `var(--text)`, etc.) for all colors
- Always support both light and dark themes
- Use the defined radius variables (`--radius`, `--radius-lg`, `--radius-xl`)
- Follow the RTL layout — `direction: rtl` is set on `<html>`

### File Naming
- API endpoints: lowercase, hyphenated (e.g., `family-tree.php`, `auth_guard.php`)
- Page templates: lowercase, single-word (e.g., `home.php`, `council.php`)
- JS files: lowercase, hyphenated (e.g., `ajax-nav.js`, `admin-core.js`)
- CSS files: lowercase, descriptive (e.g., `variables.css`, `components.css`)

### Arabic Content
- All user-facing strings (error messages, labels, titles) are in Arabic
- Code comments are primarily in Arabic
- Member statuses use Arabic values: `'مشترك'` (subscriber), `'منقطع'` (lapsed), `'غير مشترك'` (non-subscriber)
- Event statuses: `'قادم'` (upcoming), etc.

## Common Tasks

### Adding a New API Endpoint
**Quick way**: Use `/new-api-endpoint` skill in Claude Code (see [SKILL.md](.claude/skills/new-api-endpoint/SKILL.md))

Manual steps:
1. Create `api/new-resource.php`
2. Include `config.php`, `auth_guard.php`, `audit_helper.php`, `validation.php`
3. Call `requireAuth()` and `verifyCsrf()` if protected
4. Implement `handleGetAll()`, `handleGetOne($id)`, `handlePost()`, `handlePut($id)`, `handleDelete($id)`
5. Add a `match` router at the bottom
6. Write SQL for both MySQL and SQLite (use `isSQLite()`)
7. Add the API client in `public/js/api.js`

### Adding a New Public Page
**Quick way**: Use `/new-page` skill in Claude Code (see [SKILL.md](.claude/skills/new-page/SKILL.md)) — handles dual-registration automatically

Manual steps:
1. Create `pages/new-page.php` with the page content
2. Add a route entry in `router.php` (path, file, page key, title, scripts)
3. Add navigation link in `includes/header.php`
4. If the page needs JS, create `public/js/new-page.js` and add it to the route's scripts array
5. Also register the page scripts in `ajax-nav.js`'s `pageScripts` map

### Adding a New Admin Section
1. Add the section's HTML to `admin/index.php`
2. Add the section's logic to `admin/js/admin-app.js`
3. Add navigation in the admin sidebar

### Modifying CSS Theming
1. Add/modify custom properties in `public/css/variables.css` (both `:root` and `[data-theme="dark"]`)
2. Reference them via `var(--property-name)` in other CSS files

## Important Notes

### No package manager
- **Current state**: No npm, no Composer dependencies. External libraries load from CDNs:
  - D3.js v7 from `d3js.org` (loaded in `includes/head.php` for the tree page)
  - XLSX 0.18.5 from `cdnjs.cloudflare.com` (loaded in `admin/index.php` for CSV import)
  - Google Fonts (Cairo, Amiri, Tajawal) from `fonts.googleapis.com`
- **Impact**: If a CDN goes down, the dependent feature breaks. No lockfile guarantees version consistency
- **Recommendation**: Acceptable for this project's scale. If reliability becomes critical, host local copies in `public/vendor/`

### Single-file admin
- **Current state**: `admin/index.php` (~1036 lines of PHP+HTML) + `admin/js/admin-app.js` (~2350 lines with all functions in global scope). The admin panel has its own `<head>` (does not use `includes/head.php`) and currently has **no CSP**
- **Impact**: Harder to maintain as features grow. Potential for function name collisions in global scope
- **Recommendation**: When adding new admin features, consider extracting sections into separate JS files (e.g., `admin/js/admin-finance.js`). This is an incremental improvement — no need for a full rewrite

### Cache busting
- **Current state**: The `asset()` function in `includes/helpers.php` appends `?v=<filemtime>` to static file URLs. `window.__ASSET_V__` is available for JS-side cache busting
- **Impact**: Works well for direct browser requests. However, `?v=timestamp` may be ignored by some CDNs/proxies (less effective than content-hash based busting)
- **Recommendation**: Sufficient for the current project. No action needed

### Service Worker
- **Current state**: `sw.js` (27 lines) caches only `offline.html`. Does NOT cache CSS, JS, images, or fonts
- **Impact**: The app does not work offline in any meaningful way — only shows a "you are offline" page when navigation fails
- **Recommendation**: For better offline experience in the future, consider adding precache for CSS files and local fonts. Keep the SW simple — avoid caching API responses

### AJAX navigation
- **Current state**: `ajax-nav.js` intercepts link clicks and loads page content via XHR into `#page-content`. Page-specific scripts are dynamically loaded via the `pageScripts` map
- **Impact**: When adding a new page, scripts must be registered in **two places**: the `scripts` array in `router.php` AND the `pageScripts` object in `public/js/ajax-nav.js`. Missing either causes the page to break on direct load or AJAX navigation respectively
- **Recommendation**: Always register page scripts in both locations. Test both: direct URL access and clicking a link from another page

### Sensitive files protection
- **Current state**: Protected files and their server config coverage:
  - `.env` (credentials) — blocked via `.htaccess` (`FilesMatch`) and `nginx.conf` (`location ~ /\.env`)
  - `*.db` (SQLite databases) — blocked via `.htaccess` (`FilesMatch`) and `nginx.conf` (`location ~ \.db$`)
  - `.git/` — blocked via `nginx.conf` (`location ~ /\.git`)
  - `.htaccess` — blocked via `nginx.conf` (`location ~ /\.ht`)
  - `api/config.php` — blocked via `nginx.conf` (`location = /api/config.php`)
- **Note**: PHP's built-in dev server does NOT read `.htaccess`. In development, `router.php` handles routing, but direct requests to `.env` are not blocked by server config. Keep `.env` out of the document root or use Nginx/Apache in production
- **Recommendation**: When deploying, verify that sensitive file access returns 403. Test with: `curl -I https://your-domain/.env`

## Claude Code Tools

### Skills (slash commands)
| Command | Purpose |
|---------|---------|
| `/new-api-endpoint` | Generate a complete API endpoint with DB schema, validation, CRUD handlers, and JS client |
| `/new-page` | Generate a public page with automatic dual-registration (router.php + ajax-nav.js) |
| `/code-review` | Review code against project standards (strict_types, CSRF, audit, Arabic, RTL, dark mode) |

### Hooks (automatic)
| Hook | Trigger | Action |
|------|---------|--------|
| `check-page-sync.sh` | After file edit/write | Validates that all routes in `router.php` have matching `pageScripts` entries in `ajax-nav.js` |

### Documentation
| File | Purpose |
|------|---------|
| `docs/architecture.md` | System architecture overview with request flow diagrams |
| `docs/decisions/001-template.md` | ADR template for documenting architectural decisions |
| `docs/runbooks/add-feature.md` | Step-by-step guide for adding pages, APIs, admin sections |
| `docs/runbooks/database-changes.md` | Guide for modifying schema across MySQL and SQLite |
