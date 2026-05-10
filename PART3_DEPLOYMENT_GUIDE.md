# Part 3: Ubuntu & Nginx Deployment Guide

> **Role:** Linux System Administrator
> **Environment:** Ubuntu 22.04 LTS (compatible with 20.04 / 24.04)
> **Editor:** `vim` throughout
> **Stack:** Nginx + PHP-FPM + MySQL

---

## Architecture Overview

```
Browser Request
      │
      ▼
   Nginx :80
      │
      ├── /socialnet/*  ──► /var/www/socialnet/*.php
      ├── /admin/*      ──► /var/www/admin/*.php
      │
      └── *.php  ──► PHP-FPM (Unix socket) ──► MySQL
```

**URL-to-file mapping after deployment:**

| URL | File on disk |
|---|---|
| `http://localhost/socialnet/signin.php` | `/var/www/socialnet/signin.php` |
| `http://localhost/socialnet/index.php` | `/var/www/socialnet/index.php` |
| `http://localhost/admin/newuser.php` | `/var/www/admin/newuser.php` |

---

## Step 1 — System Update

Always start fresh:

```bash
sudo apt update && sudo apt upgrade -y
```

---

## Step 2 — Install Nginx

```bash
sudo apt install nginx -y
sudo systemctl start nginx
sudo systemctl enable nginx
```

Verify it is running:

```bash
sudo systemctl status nginx
# Look for: Active: active (running)
```

Open `http://localhost` in a browser — you should see the **Nginx default welcome page**.

---

## Step 3 — Install PHP-FPM and Extensions

Nginx cannot execute PHP on its own; it hands `.php` files to **PHP-FPM** via a Unix socket.

```bash
sudo apt install php-fpm php-mysql php-mbstring php-xml -y
```

Check the installed PHP version (you need it for Step 7):

```bash
php -v
# Example output: PHP 8.1.2
```

Start and enable PHP-FPM (substitute your version number):

```bash
sudo systemctl start php8.1-fpm
sudo systemctl enable php8.1-fpm
sudo systemctl status php8.1-fpm
# Look for: Active: active (running)
```

---

## Step 4 — Install MySQL Server

```bash
sudo apt install mysql-server -y
sudo systemctl start mysql
sudo systemctl enable mysql
```

### Harden MySQL with the Secure Installation Script

```bash
sudo mysql_secure_installation
```

Answer the interactive prompts:

| Prompt | Recommended Answer |
|---|---|
| Set up VALIDATE PASSWORD component? | `Y` |
| Password strength level | `2` (Strong) |
| Change the root password? | `Y` (set a strong root password) |
| Remove anonymous users? | `Y` |
| Disallow root login remotely? | `Y` |
| Remove test database? | `Y` |
| Reload privilege tables now? | `Y` |

---

## Step 5 — Create the Database and Application User

Log in to MySQL as root:

```bash
sudo mysql -u root -p
```

Inside the MySQL shell, run:

```sql
-- 1. Create the database
CREATE DATABASE socialnet
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- 2. Create a dedicated application user
--    NEVER use root inside your application
CREATE USER 'socialnet_user'@'localhost'
  IDENTIFIED BY 'StrongPass!23';

-- 3. Grant only the permissions the app needs
GRANT SELECT, INSERT, UPDATE, DELETE
  ON socialnet.*
  TO 'socialnet_user'@'localhost';

-- 4. Apply changes
FLUSH PRIVILEGES;

EXIT;
```

### Import the Schema

```bash
sudo mysql -u root -p socialnet < /var/www/db.sql
```

Verify the table exists:

```bash
sudo mysql -u socialnet_user -p socialnet -e "DESCRIBE account;"
```

Expected output:

```
+-------------+--------------+------+-----+---------+----------------+
| Field       | Type         | Null | Key | Default | Extra          |
+-------------+--------------+------+-----+---------+----------------+
| Id          | int(11)      | NO   | PRI | NULL    | auto_increment |
| username    | varchar(50)  | NO   | UNI | NULL    |                |
| fullname    | varchar(100) | NO   |     | NULL    |                |
| password    | varchar(255) | NO   |     | NULL    |                |
| description | text         | YES  |     | NULL    |                |
+-------------+--------------+------+-----+---------+----------------+
```

---

## Step 6 — Deploy Application Files

### Create the web root directories

```bash
sudo mkdir -p /var/www/socialnet
sudo mkdir -p /var/www/admin
```

### Copy files from your project

```bash
# Shared files at the web root level
sudo cp db_connect.php  /var/www/db_connect.php
sudo cp db.sql          /var/www/db.sql

# SocialNet app
sudo cp socialnet/style.css    /var/www/socialnet/style.css
sudo cp socialnet/navbar.php   /var/www/socialnet/navbar.php
sudo cp socialnet/signin.php   /var/www/socialnet/signin.php
sudo cp socialnet/index.php    /var/www/socialnet/index.php
sudo cp socialnet/setting.php  /var/www/socialnet/setting.php
sudo cp socialnet/profile.php  /var/www/socialnet/profile.php
sudo cp socialnet/about.php    /var/www/socialnet/about.php
sudo cp socialnet/signout.php  /var/www/socialnet/signout.php

# Admin panel
sudo cp admin/newuser.php /var/www/admin/newuser.php
```

### Set ownership and permissions

PHP-FPM and Nginx both run as `www-data` on Ubuntu:

```bash
# Give www-data ownership of all files
sudo chown -R www-data:www-data /var/www/

# Directories: rwxr-xr-x (755) — owner can write, others can read/execute
sudo find /var/www/ -type d -exec chmod 755 {} \;

# Files: rw-r--r-- (644) — owner can write, others can read only
sudo find /var/www/ -type f -exec chmod 644 {} \;
```

---

## Step 7 — Create the Nginx Server Block

### Open the config file in vim

```bash
sudo vim /etc/nginx/sites-available/socialnet
```

**vim keystroke sequence:**
1. The file opens empty. Press **`i`** to enter **Insert Mode** (you will see `-- INSERT --` at the bottom).
2. Type or paste the configuration below.
3. Press **`Esc`** to exit Insert Mode.
4. Type **`:wq`** and press **`Enter`** to Write (save) and Quit.

### The server block

```nginx
server {
    listen 80;
    server_name localhost;

    # Document root — parent of both /admin and /socialnet
    root /var/www;
    index index.php index.html;

    # ─── /socialnet/* route ────────────────────────────────────
    location /socialnet/ {
        try_files $uri $uri/ =404;
    }

    # ─── /admin/* route ────────────────────────────────────────
    # WARNING: Secure this in production (IP restriction shown below)
    location /admin/ {
        try_files $uri $uri/ =404;

        # Uncomment to allow only local machine access:
        # allow 127.0.0.1;
        # deny all;
    }

    # ─── PHP-FPM handler ───────────────────────────────────────
    # All .php requests are passed to PHP-FPM via Unix socket
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;

        # IMPORTANT: replace 8.1 with your actual PHP version
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;

        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # ─── Block access to dotfiles (.env, .git, .htaccess) ──────
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # ─── Security headers ──────────────────────────────────────
    add_header X-Frame-Options        "SAMEORIGIN"                    always;
    add_header X-Content-Type-Options "nosniff"                       always;
    add_header Referrer-Policy        "strict-origin-when-cross-origin" always;

    # ─── Logging ───────────────────────────────────────────────
    access_log /var/log/nginx/socialnet_access.log;
    error_log  /var/log/nginx/socialnet_error.log;
}
```

> **Finding your PHP-FPM socket path:**
> ```bash
> ls /var/run/php/
> # Example output: php8.1-fpm.pid  php8.1-fpm.sock
> ```
> Use the `.sock` filename in your `fastcgi_pass` directive.

---

## Step 8 — Enable the Site

### Create a symlink from sites-available to sites-enabled

```bash
sudo ln -s /etc/nginx/sites-available/socialnet \
           /etc/nginx/sites-enabled/socialnet
```

### Disable the default Nginx site (recommended)

```bash
sudo unlink /etc/nginx/sites-enabled/default
```

### Test the configuration for syntax errors

```bash
sudo nginx -t
```

**Expected output (success):**
```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

**If you see errors**, re-open the config file:
```bash
sudo vim /etc/nginx/sites-available/socialnet
# Press i → fix the error → Esc → :wq
```

---

## Step 9 — Reload Services

```bash
# Apply the new Nginx config without downtime
sudo systemctl reload nginx

# Restart PHP-FPM to apply any PHP config changes
sudo systemctl restart php8.1-fpm
```

---

## Step 10 — (Optional) Tune PHP Configuration

```bash
sudo vim /etc/php/8.1/fpm/php.ini
```

**vim tip for searching:** In Normal Mode (after `Esc`), type `/session.cookie_httponly` and press `Enter` to jump to that line. Press `n` for next match.

Find and update these values:

```ini
; Display errors — set On for development, Off for production
display_errors = On
error_reporting = E_ALL

; Session hardening
session.cookie_httponly = 1
session.use_strict_mode = 1
session.cookie_samesite = "Lax"

; File upload limits (for future photo upload feature)
upload_max_filesize = 10M
post_max_size       = 12M
```

After saving (`:wq`), restart PHP-FPM:

```bash
sudo systemctl restart php8.1-fpm
```

---

## Step 11 — Create Your First User

The app has no registration page — users are created via the admin panel.

1. Open `http://localhost/admin/newuser.php`
2. Fill in Username, Full Name, and Password
3. Click **Create User**
4. Navigate to `http://localhost/socialnet/signin.php` and log in

---

## Step 12 — Verify the Full Stack

Run through this checklist:

```bash
# ✅ Nginx is running
sudo systemctl is-active nginx

# ✅ PHP-FPM is running
sudo systemctl is-active php8.1-fpm

# ✅ MySQL is running
sudo systemctl is-active mysql

# ✅ Nginx config is valid
sudo nginx -t

# ✅ PHP-FPM socket exists
ls -la /var/run/php/php8.1-fpm.sock

# ✅ Database and table exist
sudo mysql -u socialnet_user -p socialnet -e "SHOW TABLES;"

# ✅ Files have correct ownership
ls -la /var/www/socialnet/
```

---

## Troubleshooting Reference

### 502 Bad Gateway
**Cause:** Nginx cannot reach the PHP-FPM socket.
```bash
# Confirm socket path
ls /var/run/php/
# Update fastcgi_pass in nginx config to match, then:
sudo nginx -t && sudo systemctl reload nginx
```

### 403 Forbidden
**Cause:** File permissions are wrong.
```bash
sudo chown -R www-data:www-data /var/www/
sudo chmod -R 755 /var/www/
```

### Blank white page (PHP errors suppressed)
**Cause:** PHP error is occurring but display_errors = Off.
```bash
sudo tail -f /var/log/nginx/socialnet_error.log
sudo tail -f /var/log/php8.1-fpm.log
```

### "Database connection error"
**Cause:** Wrong credentials in `db_connect.php`.
```bash
sudo vim /var/www/db_connect.php
# Press i → fix DB_USER / DB_PASS / DB_NAME → Esc → :wq
sudo systemctl restart php8.1-fpm
```

### Session not persisting (redirect loop)
**Cause:** `session_start()` missing or headers already sent.
```bash
# Check for BOM or whitespace before <?php in your files
file /var/www/socialnet/signin.php
# Check PHP error log for "headers already sent"
sudo tail -f /var/log/php8.1-fpm.log
```

---

## Quick Service Reference

```bash
# Nginx
sudo systemctl start|stop|restart|reload|status nginx

# PHP-FPM (replace 8.1 with your version)
sudo systemctl start|stop|restart|status php8.1-fpm

# MySQL
sudo systemctl start|stop|restart|status mysql

# View Nginx error log live
sudo tail -f /var/log/nginx/socialnet_error.log

# View MySQL error log
sudo tail -f /var/log/mysql/error.log
```

---

## Directory Structure After Deployment

```
/var/www/
├── db_connect.php              ← Shared DB connection (included by all pages)
├── db.sql                      ← Schema backup
│
├── admin/
│   └── newuser.php             ← http://localhost/admin/newuser.php
│
└── socialnet/
    ├── style.css               ← http://localhost/socialnet/style.css
    ├── navbar.php              ← PHP include (not accessed directly)
    ├── signin.php              ← http://localhost/socialnet/signin.php
    ├── index.php               ← http://localhost/socialnet/index.php
    ├── setting.php             ← http://localhost/socialnet/setting.php
    ├── profile.php             ← http://localhost/socialnet/profile.php
    ├── about.php               ← http://localhost/socialnet/about.php
    └── signout.php             ← http://localhost/socialnet/signout.php
```
