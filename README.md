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

