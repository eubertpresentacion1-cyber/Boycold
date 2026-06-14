# 🔍 How to Verify & Fix Your Database

## Quick Check

### Option 1: Use the Diagnostic Tool (EASIEST)
1. Open: `http://localhost/api/test_db_schema.php`
2. See if all tables and columns exist
3. If any are missing → follow the instructions on that page

### Option 2: Manual Check in phpMyAdmin
1. Open: `http://localhost/phpmyadmin/`
2. Select database: `boycold_db`
3. Look for these tables in the left sidebar:
   - ✅ `orders` (should have: id, user_id, status, order_type, **payment_method**, **payment_status**, subtotal, delivery_fee, tax, total, address, notes, created_at, updated_at)
   - ✅ `order_items` (should have: id, order_id, product_name, product_image, unit_price, quantity, line_total, milk, addons, order_type, notes)

## If Tables Are Missing or Incomplete

### Fix Step 1: Apply the SQL Schema
1. Go to: `http://localhost/phpmyadmin/`
2. Select database: `boycold_db`
3. Click **SQL** tab (top menu)
4. Click **Choose File** and select: `config/boycold_db.sql`
5. Click **Go** to execute

**OR** paste the SQL directly:

1. Go to: `http://localhost/phpmyadmin/`
2. Select database: `boycold_db`
3. Click **SQL** tab
4. Copy contents of `config/boycold_db.sql`
5. Paste into the text area
6. Click **Go**

### Fix Step 2: Verify It Worked
After running the SQL:
1. Refresh: `http://localhost/api/test_db_schema.php`
2. All checks should show ✅

## Expected Database Structure

### `orders` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | INT (PK) | Order ID |
| `user_id` | INT (FK) | Customer |
| `status` | ENUM | Order status: pending, confirmed, preparing, ready, delivered, cancelled |
| `order_type` | ENUM | dine-in, takeout, delivery |
| **`payment_method`** | ENUM | **cod** or **gcash** |
| **`payment_status`** | ENUM | **unpaid**, **paid**, **cancelled** |
| `subtotal` | DECIMAL | Sum of items |
| `delivery_fee` | DECIMAL | Delivery cost |
| `tax` | DECIMAL | Tax amount |
| `total` | DECIMAL | Grand total |
| `address` | VARCHAR | Delivery address |
| `notes` | TEXT | Customer notes |
| `created_at` | DATETIME | When order was placed |
| `updated_at` | DATETIME | Last update |

**Key columns that might be missing:**
- ✨ `payment_method` ← **MUST exist for COD flow**
- ✨ `payment_status` ← **MUST exist for payment tracking**

### `order_items` Table
| Column | Type | Purpose |
|--------|------|---------|
| `id` | INT (PK) | Item ID |
| `order_id` | INT (FK) | Which order |
| `product_name` | VARCHAR | Item name |
| `product_image` | VARCHAR | Image path |
| `unit_price` | DECIMAL | Price per unit |
| `quantity` | INT | How many |
| `line_total` | DECIMAL | unit_price × quantity |
| `milk` | VARCHAR | Milk type (coffee) |
| `addons` | VARCHAR | Extra options |
| `order_type` | VARCHAR | dine-in, takeout, delivery |
| `notes` | TEXT | Special instructions |

## Common Issues & Solutions

### Issue 1: "Table 'boycold_db.orders' doesn't exist"
**Cause**: Orders table was never created  
**Solution**: Run `config/boycold_db.sql` using phpMyAdmin

### Issue 2: "Unknown column 'payment_method' in 'field list'"
**Cause**: Payment columns not added to orders table  
**Solution**: Run `config/boycold_db.sql` to add missing columns

### Issue 3: "Unknown column 'payment_status' in 'field list'"
**Cause**: Payment status column missing  
**Solution**: Run `config/boycold_db.sql`

### Issue 4: Orders table exists but order_items doesn't
**Cause**: Partial SQL execution  
**Solution**: Re-run entire `config/boycold_db.sql`

## After Fixing Database

1. ✅ Test again: `http://localhost/api/test_db_schema.php`
2. ✅ Try to place an order: `http://localhost/User/checkout.php`
3. ✅ Check your orders: `http://localhost/User/ordercustom.php`
4. ✅ View in admin: `http://localhost/admin/dashboard.php`

## Need More Help?

1. **Check server logs** for MySQL errors
2. **Verify database exists**: `http://localhost/phpmyadmin/` → look for `boycold_db`
3. **Check user permissions**: MySQL user `root` needs full access to `boycold_db`
4. **Verify file permissions**: `config/boycold_db.sql` must be readable

## Test Your Database Connection

If you still get 500 errors after fixing the schema:

1. Create a test file (temporary):
   ```php
   <?php
   $mysqli = new mysqli('localhost', 'root', '', 'boycold_db');
   if ($mysqli->connect_error) {
       die('Connection failed: ' . $mysqli->connect_error);
   }
   echo 'Connection OK!';
   $mysqli->close();
   ?>
   ```

2. Save as `test_connection.php` in the project root
3. Visit: `http://localhost/test_connection.php`
4. Delete the file when done

---

**Bottom Line**: If you're getting 500 errors and it's not the prepared statement issue (which we fixed), it's almost certainly a missing database schema. Use the diagnostic tool to find out what's missing!
