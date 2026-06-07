# Archery Score Recording
### PHP + MySQL — Swinburne feenix Setup Guide

---

## File structure

```
archery/
├── index.php              ← Single-page app (all screens)
├── api.php                ← JSON REST API (all DB operations)
├── generate_hash.php      ← One-time password setup tool (DELETE after use)
├── test_db.php            ← DB connection tester (DELETE after use)
├── setup_passwords.sql    ← Reference SQL for password updates
├── includes/
│   ├── config.php         ← ★ DB credentials — edit this first
│   ├── auth.php           ← Session login/logout helpers
│   └── helpers.php        ← All PDO queries mapped to your schema
└── assets/
    ├── css/style.css
    └── js/app.js          ← All UI logic and fetch() API calls
```

---

## Step-by-step setup

### 1. Edit database credentials

Open `includes/config.php` and set:

```php
define('DB_HOST', 'feenix-mariadb.swin.edu.au'); // Swinburne MariaDB host
define('DB_NAME', 's106126517_db');               // your student DB
define('DB_USER', 's106126517');                  // your student username
define('DB_PASS', 'YOUR_PASSWORD_HERE');          // your DB password
```

> **Note for Swinburne students:** The host may be `feenix-mariadb.swin.edu.au`
> or `localhost` if the PHP runs on the same feenix server. Check with your tutor.

---

### 2. Upload files

Upload the entire `archery/` folder to your feenix web space, e.g.:
```
/home/s106126517/public_html/archery/
```
Access it at: `https://feenix.swin.edu.au/~s106126517/archery/`

---

### 3. Test the database connection

Visit: `https://feenix.swin.edu.au/~s106126517/archery/test_db.php`

You should see ✓ Connected and row counts for all 7 tables.
If it fails, recheck `config.php` credentials.

**Delete `test_db.php` from the server after confirming.**

---

### 4. Set up user account passwords

Your `user_accounts` table needs bcrypt password hashes in `password_hash`
and `is_active = 1` for each user who will log in.

**Option A — Use the web tool (easiest):**

1. Visit `https://feenix.swin.edu.au/~s106126517/archery/generate_hash.php`
2. Enter a username (e.g. `irene.moser`) and their password
3. Copy the generated SQL `UPDATE` statement
4. Paste it into phpMyAdmin → SQL tab → Go
5. Repeat for each archer/user
6. **Delete `generate_hash.php` from the server when done**

**Option B — PHP CLI (if you have SSH):**
```bash
php -r "echo password_hash('TheirPassword123', PASSWORD_DEFAULT);"
```
Then run in phpMyAdmin:
```sql
UPDATE user_accounts
SET password_hash = '$2y$12$...pastehashhere...',
    is_active = 1
WHERE username = 'irene.moser';
```

**Option C — For testing only (set all users to same password):**
```bash
php -r "echo password_hash('Archery1!', PASSWORD_DEFAULT);"
```
```sql
UPDATE user_accounts SET password_hash = '$2y$12$...', is_active = 1;
```

---

### 5. Verify login works

Go to `index.php`, enter a username and the password you just set.
If login fails, check:
- `is_active = 1` in `user_accounts`
- The `password_hash` was saved correctly (should start with `$2y$`)
- The `archers.username` matches `user_accounts.username` (for archer linking)

---

## How the app maps to your database

| App action | Tables used |
|---|---|
| Login | `user_accounts` JOIN `archers` |
| Choose competition | `competitions` JOIN `clubs` |
| Choose archer | `archers` JOIN `clubs` |
| Choose category | `categories` (filtered by archer gender) |
| Save arrows | `ends` (arrow_1–arrow_6, upsert) |
| Round complete | `rounds` (total_score, x_number, distance) |
| History | `rounds` JOIN `competitions` JOIN `categories` |

---

## Score rules enforced

- Valid values: `X 10 9 8 7 6 5 4 3 2 1 M`
- Within each end, arrows must be entered **highest first** (descending)
- Enforcement happens in **both** the JS keypad (buttons disabled) and the PHP API (server-side validation)
- `X` counts as 10 points, `M` (miss) counts as 0
- `rounds.x_number` counts only true `X` values (not 10s)

---

## API endpoints

All go through `api.php?action=...`

| Action | Method | Auth | Description |
|---|---|---|---|
| `login` | POST | No | Authenticate; starts PHP session |
| `logout` | POST | No | Destroys session |
| `me` | GET | Yes | Current session user |
| `competitions` | GET | Yes | All competitions |
| `competition` | GET | Yes | Single competition by ID |
| `archers` | GET | Yes | All archers with club |
| `categories` | GET | Yes | All/filtered categories |
| `clubs` | GET | Yes | All clubs |
| `session_data` | GET | Yes | Saved ends + rounds for session |
| `save_end` | POST | Yes | Save 6 arrows; recalc round total |
| `history` | GET | Yes | Archer's round history |

---

## Troubleshooting

**Blank page / 500 error**
- Check PHP error logs on feenix
- Verify all files uploaded (especially `includes/` folder)
- Confirm PHP 7.4+ is available on feenix

**"DB connection failed"**
- Double-check `config.php` host/user/pass
- Try `localhost` as host if `feenix-mariadb.swin.edu.au` doesn't work

**Login says "Invalid credentials"**
- Run `test_db.php` to confirm active users exist
- Check `is_active = 1` in `user_accounts`
- Regenerate password hash and update again

**Category dropdown empty after choosing archer**
- Check `categories` table has rows for that archer's gender
- Check `archers.gender` is `'male'` or `'female'` (matches the enum)
