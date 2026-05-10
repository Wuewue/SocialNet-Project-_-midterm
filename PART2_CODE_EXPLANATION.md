# Part 2: File-by-File Code Explanation

> A detailed breakdown of every file in the SocialNet project — the PHP logic, session management strategy, database query patterns, and security decisions.

---

## Project File Tree

```
/var/www/
├── db_connect.php              ← Shared DB connection
├── db.sql                      ← Database schema
│
├── admin/
│   └── newuser.php             ← Admin: create users
│
└── socialnet/
    ├── style.css               ← Full UI stylesheet
    ├── navbar.php              ← Shared nav component
    ├── signin.php              ← Login page
    ├── index.php               ← Home / feed page
    ├── setting.php             ← Edit profile bio
    ├── profile.php             ← View any user's profile
    ├── about.php               ← Static about page
    └── signout.php             ← Destroy session
```

---

## 1. `db.sql` — Database Schema

```sql
CREATE DATABASE IF NOT EXISTS socialnet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE `account` (
    `Id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `username`    VARCHAR(50)  NOT NULL,
    `fullname`    VARCHAR(100) NOT NULL,
    `password`    VARCHAR(255) NOT NULL,
    `description` TEXT         DEFAULT NULL,
    PRIMARY KEY (`Id`),
    UNIQUE KEY `unique_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key Decisions:**
- `utf8mb4` charset supports full Unicode including emoji.
- `UNIQUE KEY` on `username` enforces uniqueness at the database level — a safety net even if PHP validation is bypassed.
- `password` is `VARCHAR(255)` because PHP's `password_hash()` with `PASSWORD_BCRYPT` produces 60-character strings, but the column is sized generously to accommodate future algorithm changes (e.g., Argon2id can exceed 95 chars).
- `description` is `TEXT` (up to 65,535 bytes) rather than `VARCHAR` because bios can be long and their length is unpredictable.
- `InnoDB` engine enables foreign key constraints and ACID-compliant transactions if the schema grows.

---

## 2. `db_connect.php` — Database Connection

```php
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) { die(...); }
$conn->set_charset("utf8mb4");
```

**How it works:**
- Uses the **MySQLi** (MySQL Improved) extension in **object-oriented style** for a clean API.
- `set_charset("utf8mb4")` prevents a class of SQL injection attacks that exploit multi-byte encoding issues — always set this **after** connecting.
- Credentials are defined as `define()` constants, not variables, so they cannot be accidentally overwritten later in the script.
- In production, move credentials to a `.env` file or server-level environment variables, and ensure `db_connect.php` is **outside the web root** (e.g., `/var/www/includes/`).
- `require_once` (not `include`) is used by calling pages; this causes a fatal error if the file is missing, which is the correct behaviour — the app must not run without a DB connection.

---

## 3. `admin/newuser.php` — Create User

### Flow Diagram
```
POST Request
     │
     ▼
 Validate inputs ──(fail)──► Display error
     │
     ▼
 password_hash($password, PASSWORD_BCRYPT, ['cost'=>12])
     │
     ▼
 Prepared INSERT statement
     │
     ├──(errno 1062)──► "Username taken" error
     │
     └──(success)──► "User created" success message
```

### PHP Functions Used

| Function | Purpose |
|---|---|
| `password_hash($pw, PASSWORD_BCRYPT, ['cost'=>12])` | Hashes password with bcrypt. Cost 12 means 2^12 rounds — roughly 250ms per hash, making brute-force expensive. |
| `$conn->prepare()` | Creates a **prepared statement** — the SQL structure is compiled once, then parameters are bound separately. SQL injection is structurally impossible. |
| `$stmt->bind_param('ssss', ...)` | Binds four `string` parameters to the `?` placeholders. The type string `'ssss'` tells MySQLi to treat all as strings. |
| `$stmt->execute()` | Runs the prepared statement with the bound values. |
| `$conn->errno` | After a failed execute, this holds the MySQL error code. `1062` = duplicate entry, which maps to the UNIQUE constraint on `username`. |
| `htmlspecialchars()` | Escapes output to prevent **Cross-Site Scripting (XSS)**. Used on all user-supplied data rendered in HTML. |

---

## 4. `socialnet/signin.php` — Login

### Session Management: Login Flow

```
User submits form
     │
     ▼
 SELECT user WHERE username = ?  ← Prepared statement
     │
     ├── 0 rows ──► Generic error ("Incorrect username or password")
     │
     └── 1 row ──► password_verify($submitted, $stored_hash)
                        │
                        ├── false ──► Generic error
                        │
                        └── true
                              │
                              ▼
                        session_regenerate_id(true)   ← Prevents session fixation
                              │
                              ▼
                        $_SESSION['user_id']  = $user['Id']
                        $_SESSION['username'] = $user['username']
                        $_SESSION['fullname'] = $user['fullname']
                              │
                              ▼
                        header('Location: /socialnet/index.php')
```

### Key Security Decisions

**Generic error messages:** Both "user not found" and "wrong password" return the same message: *"Incorrect username or password."* This prevents **username enumeration** — an attacker cannot use the error message to determine which usernames are registered.

**`password_verify()`:** PHP's built-in function uses a **timing-safe comparison** internally. It prevents timing attacks where an attacker infers password correctness from response time differences.

**`session_regenerate_id(true)`:** Called immediately after successful authentication. This generates a new session ID, making any previously stolen session token invalid. The `true` argument deletes the old session file from the server. This is the standard defence against **session fixation attacks**.

**`session_start()`:** Must be the **first thing** called in any page that uses sessions — before any HTML output. A stray `echo` or whitespace before `session_start()` will cause "headers already sent" errors.

---

## 5. `socialnet/index.php` — Home Page

### Auth Guard Pattern

Every protected page begins with this identical block:

```php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /socialnet/signin.php');
    exit; // ← CRITICAL: always exit after a redirect header
}
```

**Why `exit` is mandatory:** `header()` in PHP does **not** stop script execution. Without `exit`, the rest of the page renders and is sent to the browser before the redirect fires. An attacker using a tool like `curl` could read the page content even without a valid session. `exit` immediately terminates the script.

### Querying Other Users

```php
$stmt = $conn->prepare(
    "SELECT Id, username, fullname, description
     FROM account WHERE Id != ? ORDER BY fullname ASC"
);
$stmt->bind_param('i', $currentUserId);  // 'i' = integer type
$stmt->execute();
$result     = $stmt->get_result();
$otherUsers = $result->fetch_all(MYSQLI_ASSOC);
```

- `bind_param('i', ...)` — `'i'` specifies an integer bind type, which adds an extra layer of type safety beyond the prepared statement itself.
- `fetch_all(MYSQLI_ASSOC)` — returns the entire result set as an associative array at once. This is appropriate when the result set is small (a list of users). For very large result sets, iterate with `fetch_assoc()` in a loop to avoid loading everything into memory.

---

## 6. `socialnet/setting.php` — Edit Bio

### Update Flow

```php
$stmtUp = $conn->prepare("UPDATE account SET description = ? WHERE Id = ?");
$stmtUp->bind_param('si', $description, $userId);
// 's' = string (description), 'i' = integer (Id)
$stmtUp->execute();
```

- The `WHERE Id = ?` clause uses the **session's** `user_id`, not anything from `$_POST`. This means users can **only** update their own record — they cannot tamper with the `POST` body to modify another user's bio.
- After a successful update, `$user['description']` is updated in memory so the textarea reflects the new value without a second database query.

---

## 7. `socialnet/profile.php` — View Profile

### Owner Resolution Logic

```php
$requestedOwner = trim($_GET['owner'] ?? '');

if (!empty($requestedOwner)) {
    // View someone else's profile by username
    $stmt = $conn->prepare("SELECT ... FROM account WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $requestedOwner);
} else {
    // View own profile by session user_id
    $stmt = $conn->prepare("SELECT ... FROM account WHERE Id = ? LIMIT 1");
    $stmt->bind_param('i', $currentUserId);
}
```

The `?owner=` parameter is user-supplied and could be anything. Using a **prepared statement** here is essential — inserting `$requestedOwner` directly into a query string would create a SQL injection vulnerability.

`LIMIT 1` is added as a defensive measure even though `username` has a UNIQUE constraint — it signals intent and slightly optimises the query plan.

**`$isOwnProfile` flag:**
```php
$isOwnProfile = !$notFound && ($profile['username'] === $_SESSION['username']);
```
This is computed **server-side** from trusted session data. It controls whether the "Edit Profile" button is shown. Never trust client-supplied flags for access control decisions.

---

## 8. `socialnet/about.php` — About Page

A simple authenticated static page. The only logic is the auth guard and `$activePage = 'about'`. The `date('F j, Y')` call dynamically renders today's date as the submission date.

---

## 9. `socialnet/signout.php` — Sign Out

```php
session_start();           // Must start session before manipulating it
$_SESSION = [];            // Clear all session variables (in memory)
setcookie(session_name(), '', time() - 42000, ...);  // Expire client cookie
session_destroy();         // Delete session file from server
header('Location: /socialnet/signin.php');
exit;
```

**Three-step session destruction** is the PHP security best practice:
1. **Clear the data** (`$_SESSION = []`) — erases session variables.
2. **Expire the cookie** (`setcookie(...)`) — tells the browser to delete its session cookie immediately.
3. **Destroy server data** (`session_destroy()`) — removes the session file from `/var/lib/php/sessions/` (or wherever PHP stores them), making the token permanently invalid.

---

## 10. `socialnet/navbar.php` — Shared Navigation Component

```php
$activePage = $activePage ?? '';
$initial    = strtoupper(substr($uname, 0, 1));
```

- `$activePage` is set by the **parent page** before the `include 'navbar.php'` call (e.g., `$activePage = 'home'`). The navbar uses it to add the `active` CSS class to the correct link.
- The user avatar is a CSS gradient circle with the **first letter of the username**, providing a personalised visual without requiring photo uploads.
- All user data output through `htmlspecialchars()` to prevent XSS.

---

## Session Data Architecture Summary

| Key | Type | Set in | Used in |
|---|---|---|---|
| `$_SESSION['user_id']` | int | `signin.php` | Auth guard on all pages; profile queries; setting updates |
| `$_SESSION['username']` | string | `signin.php` | Navbar display; `$isOwnProfile` check in `profile.php` |
| `$_SESSION['fullname']` | string | `signin.php` | Navbar display; home page greeting |

---

## Security Summary

| Threat | Defence |
|---|---|
| SQL Injection | Prepared statements (`$conn->prepare()` + `bind_param`) everywhere |
| XSS | `htmlspecialchars()` on all output |
| Password cracking | `password_hash()` with bcrypt cost=12; `password_verify()` |
| Session Fixation | `session_regenerate_id(true)` on login |
| Username enumeration | Generic "incorrect username or password" error |
| Unauthorised access | Auth guard at top of every protected page + `exit` after redirect |
| Mass assignment | Only `description` is user-editable; `WHERE Id = $_SESSION['user_id']` |
