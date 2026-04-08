# City-Wide Event Tracking System

Minimalist PHP/MySQL app for discovering and managing city-wide events.  
Stack: **Core PHP**, **Vanilla JavaScript**, **CSS3**, **MySQL (PDO)**.

## 1. Requirements

- PHP 8+ with `pdo_mysql` enabled
- MySQL 5.7+ / MariaDB
- A web server or PHP built-in server

## 2. Setup Steps

1. **Create the database**

   ```sql
   CREATE DATABASE city_events CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Import the schema**

   ```bash
   mysql -u your_user -p city_events < sql/schema.sql
   ```

3. **Configure database credentials**

   Edit `config/config.php` and update:

   ```php
   const DB_HOST = 'localhost';
   const DB_NAME = 'city_events';
   const DB_USER = 'your_user';
   const DB_PASS = 'your_password';
   ```

4. **Run the app (PHP built-in server)**

   From the project root:

   ```bash
   php -S localhost:8000 -t public
   ```

   Then open `http://localhost:8000/index.php` in your browser.

## 3. High-Level Structure

- `public/index.php` – single entry point and simple router.
- `config/` – app config and PDO connection (`db.php` uses PDO).
- `includes/` – session, auth guards, and small helpers.
- `models/` – database access functions for users, events, RSVPs, and comments.
- `views/` – layout + pages (feed, detail, auth, dashboard, admin).
- `actions/` – small POST handlers (auth, event CRUD, RSVP, comments).
- `public/assets/` – single `style.css`, `main.js`, and images.
- `sql/schema.sql` – the MySQL schema you can import directly.

This layout is intentionally simple so a student can explain it file-by-file in a viva.

## 4. Database Security Notes (Class Checklist)

This project already uses prepared statements (PDO), password hashing, CSRF protection, output escaping, and secure session cookies.

Additional security controls included:

1. Enforce HTTPS/TLS (configurable)

- Set environment variable `FORCE_HTTPS=1` in production to redirect all HTTP requests to HTTPS.
- Browser security headers are enabled by default (`ENABLE_SECURITY_HEADERS=1`) and include HSTS when HTTPS is active.

2. SQL monitoring and anomaly logging (configurable)

- Set `ENABLE_SQL_QUERY_LOGGING=1` to log slow/failed SQL statements through PHP error logs.
- Tune slow-query threshold using `SQL_SLOW_QUERY_MS` (default `500`).

3. Password hash lifecycle management

- Passwords are hashed using `password_hash(..., PASSWORD_DEFAULT)`.
- On successful login, hashes are automatically rehashed if algorithm/work-factor settings become outdated.

4. Least-privilege database account

Use a dedicated MySQL user for the web app instead of root.

Example (adapt names for your environment):

```sql
CREATE USER 'city_events_app'@'localhost' IDENTIFIED BY 'ChangeThisStrongPassword!';
GRANT SELECT, INSERT, UPDATE, DELETE ON city_events.* TO 'city_events_app'@'localhost';
FLUSH PRIVILEGES;
```

Then set `DB_USER` and `DB_PASS` environment variables for deployment.

