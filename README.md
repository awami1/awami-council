# Awami Council Platform

A full-stack web platform for managing a real-world family council (Majlis), built with PHP and vanilla JavaScript.

## Features

- **Member Management** -- CRUD for family members with status tracking
- **Financial System** -- Payment tracking, transactions (income/expense), and periods
- **Committees** -- Create and manage committees with member assignments
- **Events** -- Event planning with status, budget, and participant tracking
- **Family Tree** -- Hierarchical family tree with parent-child relationships
- **News** -- Publish articles with categories, drafts, and rich content
- **Public Website** -- Dynamic API-driven pages (RTL Arabic layout)
- **Admin Panel** -- Secure dashboard for council administrators
- **Audit Logging** -- Track all administrative actions
- **Media Gallery** -- Photo/video gallery with YouTube embed support
- **Polls & Reminders** -- Community engagement tools

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8+ (PDO, strict types) |
| Database | MySQL (production) / SQLite (development) |
| Frontend | Vanilla JavaScript (modular), CSS3 |
| Server | Nginx |
| Auth | Session-based with bcrypt password hashing |

## Project Structure

```
awami-council/
  api/            # REST API endpoints (PHP)
    config.php    # Database & shared utilities
    auth.php      # Login/logout
    auth_guard.php
    validation.php # Shared validation helpers
    members.php, payments.php, events.php, ...
  admin/          # Admin panel (SPA)
  includes/       # Shared templates & helpers
    helpers.php   # Public site data functions
    header.php, footer.php, layout.php
  pages/          # Public page templates
  public/         # Static assets (CSS, JS, images)
  data/           # SQLite database (dev only)
```

## Getting Started

### Prerequisites

- PHP 8.1+
- MySQL 8.0+ (or SQLite for local dev)
- Nginx or Apache

### Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/awami1/awami-council.git
   cd awami-council
   ```

2. Create a `.env` file:
   ```env
   DB_HOST=localhost
   DB_NAME=awami_council
   DB_USER=root
   DB_PASS=your_password
   DB_PORT=3306

   ADMIN_USERNAME=admin
   ADMIN_PASSWORD_HASH=$2y$10$...   # Generate with: php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"
   ```

3. Run database setup:
   ```
   Visit /api/members.php?setup=1
   ```

4. Point your web server to the project root.

### Local Development (SQLite)

If no `DB_HOST` is set in `.env`, the app automatically uses SQLite at `data/awami.db`.

## API Endpoints

| Endpoint | Methods | Auth | Description |
|----------|---------|------|-------------|
| `/api/auth.php` | GET, POST | No | Login/logout/check |
| `/api/members.php` | CRUD | Yes | Member management |
| `/api/payments.php` | CRUD | Yes | Payment records |
| `/api/transactions.php` | CRUD | Yes | Financial transactions |
| `/api/events.php` | CRUD | Yes | Events |
| `/api/committees.php` | CRUD | Yes | Committees |
| `/api/news.php` | CRUD | Mixed | News (GET public, write auth) |
| `/api/family-tree.php` | CRUD | Mixed | Family tree (GET public) |
| `/api/settings.php` | GET, PUT | Yes | Website settings |

## Security

- Session-based authentication with `SameSite=Strict` cookies
- bcrypt password hashing (no default passwords)
- Rate limiting on login (5 attempts per 15 minutes)
- Request body size limits (64KB)
- Prepared statements (PDO) for SQL injection prevention
- HTML escaping (`htmlspecialchars`) for XSS prevention
- Security headers: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`
- Audit logging for all admin actions

## License

All rights reserved.
