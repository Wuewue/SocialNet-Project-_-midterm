# Student ; Pham Thanh Tung
## id: 1694591
# SocialNet

A full-stack social networking web application built with **PHP**, **MySQL**, **Nginx**, and plain **CSS** — featuring UI with user authentication, profile management, and a clean sidebar navigation.

---

## Screenshots

| Sign In | Home Feed | Profile |
|---|---|---|
| *Login with gradient brand UI* | *User discovery cards* | *Profile with bio* |

---

## Features

- **Secure Authentication** — bcrypt password hashing (`PASSWORD_BCRYPT`, cost 12), session fixation prevention with `session_regenerate_id()`, and auth guards on every protected page
- **Admin Panel** — `/admin/newuser.php` for creating user accounts (no self-registration)
- **Home Feed** — Stories-style user discovery row + card list of all registered users
- **Friends System** — Add and Remove friends directly from their profile pages (`friendship` relationship table)
- **Profile Pages** — View any user's profile via `?owner=username` query string
- **Settings** — Edit your own profile bio/description
- **About Page** — Static project information page
- **Responsive Design** — Full sidebar nav on desktop, compact icon sidebar on tablet, bottom navigation bar on mobile
- **SQL Injection Prevention** — All database queries use MySQLi prepared statements
- **XSS Prevention** — All output escaped with `htmlspecialchars()`

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 8.x (Vanilla, procedural + OOP MySQLi) |
| **Database** | MySQL 8.x |
| **Web Server** | Nginx |
| **OS** | Ubuntu 22.04 LTS |
| **Frontend** | HTML5 + Plain CSS (no framework) |
| **Fonts** | Google Fonts — DM Sans + Playfair Display |

---

## Project Structure

```
/
├── db.sql                      # Database schema
├── db_connect.php              # Shared DB connection (include file)
│
├── admin/
│   └── newuser.php             # Admin: create new users
│
└── socialnet/
    ├── style.css               # Full application stylesheet
    ├── navbar.php              # Shared sidebar navigation component
    ├── signin.php              # Login page
    ├── index.php               # Home / feed page (protected)
    ├── setting.php             # Edit profile bio (protected)
    ├── profile.php             # View profile — self or ?owner=username (protected)
    ├── about.php               # Static about page (protected)
    └── signout.php             # Destroys session + redirects
```

---

## Prerequisites

- Ubuntu 20.04 / 22.04 / 24.04
- Nginx
- PHP 8.x + php-fpm + php-mysql
- MySQL 8.x

---

## Quick Setup

### 1. Install Dependencies

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install nginx php-fpm php-mysql php-mbstring mysql-server -y
```

### 2. Set Up the Database

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE socialnet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'socialnet_user'@'localhost' IDENTIFIED BY 'YourStrongPassword';
GRANT SELECT, INSERT, UPDATE, DELETE ON socialnet.* TO 'socialnet_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
sudo mysql -u root -p socialnet < db.sql
```

### 3. Update Database Credentials

Edit `db_connect.php` and set your credentials:

```php
define('DB_USER', 'socialnet_user');
define('DB_PASS', 'YourStrongPassword');
```

### 4. Deploy Files

```bash
sudo mkdir -p /var/www/socialnet /var/www/admin
sudo cp db_connect.php /var/www/db_connect.php
sudo cp -r socialnet/* /var/www/socialnet/
sudo cp admin/newuser.php /var/www/admin/newuser.php
sudo chown -R www-data:www-data /var/www/
```

### 5. Configure Nginx

```bash
sudo vim /etc/nginx/sites-available/socialnet
```

Paste this server block since php8.3 is used, replace with your current version.

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www;
    index index.php;

    location /socialnet/ { try_files $uri $uri/ =404; }
    location /admin/     { try_files $uri $uri/ =404; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/socialnet /etc/nginx/sites-enabled/
sudo unlink /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
sudo systemctl restart php8.1-fpm
```

### 6. Create the First User

Navigate to `http://localhost/admin/newuser.php` and create an account, then log in at `http://localhost/socialnet/signin.php`.

---

## Pages & Routes

| Route | Description | Auth Required |
|---|---|---|
| `/admin/newuser.php` | Create new users | No (restrict via Nginx in production) |
| `/socialnet/signin.php` | Login | No |
| `/socialnet/index.php` | Home feed | ✅ Yes |
| `/socialnet/profile.php` | Own profile | ✅ Yes |
| `/socialnet/profile.php?owner=alice` | View Alice's profile | ✅ Yes |
| `/socialnet/setting.php` | Edit bio | ✅ Yes |
| `/socialnet/about.php` | About page | ✅ Yes |
| `/socialnet/signout.php` | Log out | ✅ Yes |

---

## Security Notes

- Passwords are hashed with `password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12])`
- All SQL queries use prepared statements — immune to SQL injection
- All HTML output uses `htmlspecialchars()` — immune to XSS
- Session regenerated on login to prevent session fixation
- The `/admin/` route should be **IP-restricted** or protected with HTTP Basic Auth in any non-local environment

---

## Detailed Documentation

| Document | Description |
|---|---|
| [`PART2_CODE_EXPLANATION.md`](./PART2_CODE_EXPLANATION.md) | File-by-file PHP logic breakdown, session management, and security analysis |
| [`PART3_DEPLOYMENT_GUIDE.md`](./PART3_DEPLOYMENT_GUIDE.md) | Full Ubuntu + Nginx deployment guide with vim commands |

---

## License

This project is submitted as a university coursework assignment. All code is original.
