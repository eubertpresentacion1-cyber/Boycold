# BoyCold Café - Product Setup Instructions

## Issue Found & Fixed
Your menu was empty because the `products` table in the database had no data.

## Quick Setup (Choose One Option)

### Option 1: Run via Web Browser (EASIEST) ✅
1. Open your browser
2. Go to: `http://localhost/boycoldv2/User/insert_products.php`
3. Click the button to populate 65 products
4. Verify products appear in menu: `http://localhost/boycoldv2/User/menu.php`

### Option 2: Import SQL File via phpMyAdmin
1. Open phpMyAdmin
2. Select database: `boycold_db`
3. Click "Import" tab
4. Upload file: `config/boycold_db.sql`
5. Click "Go"

### Option 3: Command Line (MySQL)
```bash
mysql -u root boycold_db < config/boycold_db.sql
```

## What Gets Installed
- **65 Products** across 9 categories:
  - ☕ Coffee (8)
  - ✨ Special Coffee (7)
  - 🍵 Matcha Fusion (11)
  - 🍓 Fruit Shake (10)
  - 🥤 Frappe Series (5)
  - 🧇 Waffles (6)
  - 🥛 Non-Coffee (7)
  - 🍟 Bites (5)
  - 🌯 Quesadilla (3)

## After Setup
1. **Menu page** will display all products
2. **Heart button** ❤️ will add/remove products to favorites (database)
3. **Cart button** 🛒 will add products to cart (database)

## Notes
- All products are set to `is_available = 1` (enabled)
- Prices are in Philippine Peso (₱)
- Product images must be in `/picture/` directory
- Data is stored in database, not localStorage

## Troubleshooting
If menu is still empty after setup:
1. Check browser console (F12) for errors
2. Verify products were inserted: phpMyAdmin → products table
3. Clear browser cache (Ctrl+Shift+Delete)
4. Check that `/picture/` directory contains images

---
**Last Updated:** 2026-06-04
