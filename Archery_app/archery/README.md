# Archery Score Recording
**COS20031 | Toan Nguyen (s106126517) | PHP + MariaDB**

---

## Overview

A web application for recording archery scores during competitions. Archers can log in, select a competition and category, and enter arrow scores end-by-end. Scores are saved to a MariaDB database and round totals are calculated automatically.

---

## File Structure

```
archery/
├── index.php              ← Main app (all screens)
├── api.php                ← JSON REST API
├── test_db.php            ← DB connection test (delete after use)
├── generate_hash.php      ← Password hash tool (delete after use)
├── indexes.sql            ← Index analysis SQL
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
- PHP 5.5+ with PDO and pdo_mysql extensions

---

## Setup

### 1. Copy files
Drop the `archery/` folder into your XAMPP web root:
```
C:\xampp\htdocs\archery\
```

### 2. Start XAMPP
Open XAMPP Control Panel and start both **Apache** and **MySQL**

### 3. Import mock data
See [Importing Mock Data from XLSX](#importing-mock-data-from-xlsx) below

### 4. Edit config.php
Open `includes/config.php` and set:
```php
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'archery_db');  // your local DB name
define('DB_USER',    'root');
define('DB_PASS',    '');            // XAMPP default: no password
define('DB_CHARSET', 'utf8mb4');
```

### 5. Set up passwords
Visit the hash generator:
```
http://localhost/archery/generate_hash.php
```
Enter a username and password → copy the generated SQL → paste into phpMyAdmin SQL tab → click **Go**. Repeat for each user.

> ⚠️ Delete `generate_hash.php` after setting all passwords.

### 6. Test the connection
```
http://localhost/archery/test_db.php
```
Should show ✅ Connected with row counts for all 7 tables.

> ⚠️ Delete `test_db.php` after confirming the connection works.

### 7. Open the app
```
http://localhost/archery/
```

---

## Importing Mock Data from XLSX

phpMyAdmin cannot import `.xlsx` files directly. You need to convert each sheet to CSV first, then import them one by one.

### Step 1 — Open the XLSX in Excel
Open your mock data file in Microsoft Excel or Google Sheets.

### Step 2 — Export each sheet as CSV
For each sheet in the workbook:
1. Click the sheet tab (e.g. `clubs`)
2. **Excel:** File → Save As → CSV
3. **Google Sheets:** File → Download → CSV
4. Name the file after the table e.g. `clubs.csv`
5. Repeat for every sheet

### Step 3 — Create the database in phpMyAdmin
```
http://localhost/phpmyadmin
```
1. Click **New** in the left sidebar
2. Name it `archery_db` → click **Create**

### Step 4 — Import in the correct order


| Order | Table | CSV file |
|---|---|---|
| 1st | clubs | clubs.csv |
| 2nd | categories | categories.csv |
| 3rd | user_accounts | user_accounts.csv |
| 4th | archers | archers.csv |
| 5th | competitions | competitions.csv |
| 6th | rounds | rounds.csv |
| 7th | ends | ends.csv |

### Step 5 — Import each CSV
For each table:
1. Click the table name in the left sidebar
2. Click the **Import** tab
3. Click **Choose File** → select the matching CSV
4. Set **Format** to `CSV`
5. Tick **Column names in first row** if your CSV has a header row
6. Click **Go**

You should see a green success message. Repeat for each table.

### Step 6 — Verify
Click each table → **Browse** to confirm rows loaded correctly.

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
| ends | One row per end — stores all 6 arrow scores |

---

## API Endpoints

All requests go through `api.php?action=...`

| Action | Method | Description |
|---|---|---|
| `login` | POST | Authenticate user, start PHP session |
| `logout` | POST | Destroy session |
| `me` | GET | Get current logged-in user |
| `competitions` | GET | List all competitions |
| `archers` | GET | List all archers with club name |
| `categories` | GET | List categories, optionally filtered by gender |
| `session_data` | GET | Get saved ends and round totals for a session |
| `save_end` | POST | Save 6 arrow scores, recalculate round total |
| `history` | GET | Get archer's round history |

---

## Score Rules

- Valid values: `X 10 9 8 7 6 5 4 3 2 1 M`
- Within each end, arrows must be entered **highest first**
- `X` counts as 10 points, `M` (miss) counts as 0
- Enforced in both the JS keypad and server-side in `api.php`

---

## Troubleshooting

| Problem | Fix |
|---|---|
| DB connection failed | Check `DB_HOST` is `localhost` and MySQL is running in XAMPP |
| Access denied for user | Wrong password. XAMPP default is `root` with no password |
| Login fails | Run `generate_hash.php`, update `password_hash`. Check `is_active = 1` |
| Categories dropdown empty | Check `categories` table has rows. Check `archers.gender` matches enum |
| Foreign key error on import | Import in correct order: clubs → categories → user_accounts → archers → competitions → rounds → ends |
| CSV column mismatch | Make sure CSV header names match table column names exactly |