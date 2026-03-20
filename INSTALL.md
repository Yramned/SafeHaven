# SafeHaven – Installation Guide

## Quick Start (localhost)

### Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.3+
- Apache with mod_rewrite (XAMPP/WAMP/MAMP)

### Steps

1. **Clone / extract** the `safehaven` folder into your web root:
   - XAMPP: `C:/xampp/htdocs/safehaven/`
   - Linux:  `/var/www/html/safehaven/`

2. **Create the database**:
   - Open phpMyAdmin → Create database named `safehaven`
   - Import `database/database.sql`

3. **Configure** (if needed):
   - `config/database.php` → set `$this->dbname` if using a different DB name
   - `config/config.php` → update `BASE_URL` if project is not at `/safehaven/`

4. **Access the site**: `http://localhost/safehaven/`

### Default Login Credentials

| Email | Password | Role |
|---|---|---|
| admin@safehaven.com | password | Admin |
| user@example.com | password | Evacuee |
| maria@safehaven.com | password | Evacuee |

---

## HelioHost Deployment

1. Upload all files to the **root of your domain** (e.g. `public_html/` or root).
2. Import `database/database.sql` via phpMyAdmin on HelioHost.
3. `config/database.php` already has the correct HelioHost credentials.
4. Ensure `config/config.php` has `BASE_URL = 'https://safehaven.helioho.st/'`

---

## Features

- ✅ User registration & login (role-based: Admin / Evacuee)
- ✅ Evacuation request submission (GPS location, priority, special needs)
- ✅ Admin approval/denial of evacuation requests
- ✅ Real-time capacity management
- ✅ Situational alerts (CRUD for admins, read-only for evacuees)
- ✅ Evacuation centers with live occupancy stats
- ✅ User management (add/edit/delete users via AJAX)
- ✅ Profile editing
- ✅ Contact form (saves to DB)

---

## Directory Structure

```
safehaven/
├── config/          # DB config, app config
├── controllers/     # MVC controllers
├── models/          # MVC models (DB queries)
├── views/           # MVC views (HTML/PHP templates)
│   ├── auth/        # Login, register
│   ├── dashboard/   # Dashboard
│   ├── pages/       # All page views
│   └── shared/      # Header, footer, dashboard-header
├── assets/
│   ├── css/         # Stylesheets
│   ├── js/          # JavaScript files
│   └── images/      # Static images
├── storage/         # JSON fallback files (auto-created)
├── database/        # database.sql schema
└── index.php        # Main router (all requests go here)
```
