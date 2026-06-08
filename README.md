# 🏹 Archery Score Recording
**COS20031 | Toan Nguyen (s106126517) | PHP + MySQL**

---

## Overview

A web application for recording archery scores during competitions. Archers log in, select a competition and category, and enter arrow scores end-by-end. Scores are saved to a MySQL database and round totals are calculated automatically.

---

## File Structure

```
archery/
├── index.php              ← Main app
├── login.php              ← Login page
├── api.php                ← JSON REST API
├── test_db.php            ← DB connection test (DELETED)
├── generate_hash.php      ← Password hash tool (delete after use)
├── includes/
│   ├── config.php         ← ★ Edit this — DB credentials
│   ├── auth.php           ← Login / session helpers
│   └── helpers.php        ← All database queries
└── assets/
    ├── css/style.css
    └── js/app.js          ← All UI logic
```

---

## Requirements

- XAMPP (Apache + MySQL)
- PHP 5.5+

---

## Setup

### 1. Copy files
Drop the `archery/` folder into:
```
C:\xampp\htdocs\archery\
```

### 2. Start XAMPP
Open XAMPP Control Panel → start **Apache** and **MySQL**

### 3. Create the database
Go to `http://localhost/phpmyadmin` → click **New** → name it `archery_db` → click **Create**

### 4. Edit config.php
Open `includes/config.php` and set:
```php
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'archery_db');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');
```

### 5. Import mock data
See [Importing Mock Data](#importing-mock-data) below

### 6. Set up passwords
Go to `http://localhost/archery/generate_hash.php` → enter a username and password → copy the SQL → paste into phpMyAdmin SQL tab → click **Go**. Repeat for each user.



### 7. Test the connection
```
http://localhost/archery/test_db.php
```
Should show ✅ Connected with row counts for all 7 tables. (DELETED)

### 8. Open the app
```
http://localhost/archery/login.php
```

---

## Importing Mock Data

### Step 1 — Clear all tables
Go to `http://localhost/phpmyadmin` → click `archery_db` → SQL tab → run:

```sql
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `ends`;
DELETE FROM `rounds`;
DELETE FROM `archers`;
DELETE FROM `user_accounts`;
DELETE FROM `competitions`;
DELETE FROM `clubs`;
DELETE FROM `categories`;
SET FOREIGN_KEY_CHECKS = 1;
```

### Step 2 — Import SQL files in order

| Order | File | Table | Rows |
|---|---|---|---|
| 1st | `01_clubs.sql` | clubs | 10 |
| 2nd | `02_categories.sql` | categories | 12 |
| 3rd | `03_user_accounts.sql` | user_accounts | 39 |
| 4th | `04_archers.sql` | archers | 39 |
| 5th | `05_competitions.sql` | competitions | 65 |
| 6th | `06_rounds.sql` | rounds | 578 |
| 7th | `07_ends.sql` | ends | 6276 |

For each file:
1. Click `archery_db` in the left sidebar
2. Click **Import** tab
3. Click **Choose File** → select the SQL file
4. Click **Go**

### Step 3 — Reset user passwords
The mock data passwords cannot be verified by PHP. Reset them using the hash generator:

```
http://localhost/archery/generate_hash.php
```

Or run this in phpMyAdmin SQL tab to set all users to `Test1234!` — replace the hash with one generated from the tool above:

```sql
UPDATE user_accounts
SET password_hash = 'PASTE_HASH_HERE', is_active = 1;
```

### Step 4 — Verify
```sql
SELECT COUNT(*) FROM clubs;        -- 10
SELECT COUNT(*) FROM categories;   -- 12
SELECT COUNT(*) FROM archers;      -- 39
SELECT COUNT(*) FROM competitions; -- 65
SELECT COUNT(*) FROM rounds;       -- 578
SELECT COUNT(*) FROM ends;         -- 6276
```

---

## Database Schema

| Table | Description |
|---|---|
| clubs | Archery clubs — parent of archers and competitions |
| user_accounts | Login credentials — parent of archers |
| archers | Archer profiles linked to a club and user account |
| categories | Bow type, gender, and age group combinations |
| competitions | Competition events hosted by clubs |
| rounds | One row per round — stores total_score, x_number, distance |
| ends | One row per end — stores arrow_1 through arrow_6 |

---

## How to Use

1. Go to `http://localhost/archery/login.php`
2. Log in with username and password
3. Select a competition, archer, category and distance → click **Start scoring**
4. Tap each end → enter 6 arrow scores highest first
5. Click **Save end** after each end
6. When all rounds are complete → grand total is shown
7. Click **History** in the top nav to view past rounds

---

## Score Rules

- Valid values: `X 10 9 8 7 6 5 4 3 2 1 M`
- Arrows must be entered **highest first** within each end
- `X` = 10 points, `M` (miss) = 0 points
- Enforced in both the keypad and server-side in `api.php`

---

## API Endpoints

All requests go through `api.php?action=...`

| Action | Method | Description |
|---|---|---|
| `login` | POST | Authenticate user, start PHP session |
| `logout` | POST | Destroy session, redirect to login |
| `me` | GET | Get current logged-in user |
| `competitions` | GET | List all competitions |
| `archers` | GET | List all archers with club name |
| `categories` | GET | List categories filtered by gender |
| `session_data` | GET | Get saved ends and round totals |
| `save_end` | POST | Save 6 arrow scores, recalculate round total |
| `history` | GET | Get archer's round history |

---

## Troubleshooting

| Problem | Fix |
|---|---|
| Blank page | Make sure Apache is running in XAMPP |
| DB connection failed | Check `DB_HOST` is `localhost` and MySQL is running |
| Access denied | XAMPP default is `root` with no password |
| Login fails | Run `generate_hash.php` and update `password_hash`. Check `is_active = 1` |
| Redirected to login | Session expired — log in again |
| Categories empty | Check `categories` table has rows matching archer's gender |
| Foreign key error on import | Run the DELETE block first to clear all tables |
| History shows empty | Check archer has rounds linked in the `rounds` table |
