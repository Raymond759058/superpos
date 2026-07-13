# SuperPOS — Supermarket Point of Sale System

## Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.4+
- Web server: Apache / Nginx / XAMPP / Laragon
- Modern browser (Chrome, Firefox, Edge)

---

## Installation

### Step 1 — Set up the database
1. Open **phpMyAdmin** (or your MySQL client)
2. Create a new database called `superpos`
3. Import the file `database.sql`
   - This creates all tables and inserts sample data

### Step 2 — Configure the database connection
Open `includes/config.php` and update:
```php
define('DB_HOST', 'localhost');   // your MySQL host
define('DB_USER', 'root');        // your MySQL username
define('DB_PASS', '');            // your MySQL password
define('DB_NAME', 'superpos');    // database name
```

### Step 3 — Deploy to your web server
- **XAMPP**: Copy the `superpos` folder to `C:\xampp\htdocs\`
  - Access: `http://localhost/superpos/`
- **Laragon**: Copy to `C:\laragon\www\`
  - Access: `http://superpos.test/` (after virtual host setup)
- **Linux Apache**: Copy to `/var/www/html/superpos/`
  - Access: `http://localhost/superpos/`

### Step 4 — Set BASE_URL (if in subdirectory)
In `includes/config.php`, set:
```php
define('BASE_URL', '/superpos');  // or '' if in root
```

### Step 5 — File permissions (Linux)
```bash
chmod 755 /var/www/html/superpos
chmod -R 644 /var/www/html/superpos/assets/img
```

---

## Default Login Credentials

| Role    | Username  | Password   |
|---------|-----------|------------|
| Admin   | `admin`   | `password` |
| Cashier | `cashier1`| `password` |

> ⚠️ Change passwords after first login in Admin → Users.

---

## Features

### Admin
- User management (create, disable, reset password)
- Product management (add, edit, activate/deactivate)
- Reports: daily sales, profit, top products, cashier performance
- System settings (tax rate, cash drawer, printer, discounts)
- Audit log

### Cashier
- Barcode scanning + product search
- Shopping cart (add, adjust quantity, hold orders)
- Item removal requires admin password approval
- Payment: Cash, Card, DuitNow QR, TNG, GrabPay, Boost
- Change calculation
- Receipt printing (browser / ESC/POS)
- Hold & resume orders

---

## ESC/POS Printer Setup
The system sends ESC/POS commands via the browser.
For real thermal printing, install a connector such as:
- **QZ Tray** (https://qz.io) — recommended
- **EPSON TM-T20**, **XPrinter XP-80C**, or any ESC/POS compatible printer

The drawer open command is standard: `ESC p 0 25 250`

---

## File Structure
```
superpos/
├── index.php              Login page
├── logout.php
├── database.sql           DB schema + sample data
├── includes/
│   ├── config.php         DB config & helper functions
│   ├── header.php         Navigation layout
│   └── footer.php
├── cashier/
│   ├── pos.php            Main POS register
│   └── hold.php           Hold orders
├── admin/
│   ├── users.php          User management
│   ├── products.php       Product management
│   ├── reports.php        Sales reports
│   ├── settings.php       System settings
│   └── audit.php          Audit log
├── api/
│   ├── products.php       Products REST API
│   ├── transactions.php   Transactions API
│   ├── hold.php           Hold orders API
│   ├── drawer.php         Cash drawer API
│   ├── auth.php           Admin verification API
│   └── audit.php          Audit logging API
└── assets/
    ├── css/style.css
    └── js/app.js
```

---

## Security Notes
- Passwords are hashed with `password_hash()` (bcrypt)
- Session-based authentication with timeout
- RBAC: admin-only routes are enforced server-side
- All user inputs are sanitised with PDO prepared statements
- Audit log tracks all critical actions
- Cash drawer has 5-second cooldown between opens

---

## Support
Built for PHP 8+ with Bootstrap 5 and vanilla JavaScript.
No additional composer packages required.
