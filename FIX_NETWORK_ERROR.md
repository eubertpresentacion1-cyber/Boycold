# 🔧 Fix "Network error. Please try again." - Troubleshooting Guide

## Quick Diagnosis

When you see "Network error. Please try again." when placing an order, it usually means:

1. **Session/Login Issue** - You're not properly logged in
2. **API Connection Error** - The API endpoint isn't responding
3. **Database Issue** - Connection to database failed
4. **Invalid Data** - Order data is malformed
5. **CORS Error** - Cross-origin request blocked

---

## Step 1: Test with Diagnostic Tool

### Open the Diagnostic Tool:
```
http://localhost/api/test_order_api.html
```

### Run Tests:
1. **Click "Test API Endpoint"**
   - If ✅ Success: API is working
   - If ❌ Error: Check error message

2. **Click "Check Session/Auth"**
   - If ✅ Success: You're logged in
   - If ❌ 401 Error: You need to login first

3. **Click "Create Test Order"**
   - If ✅ Success: Order system works completely
   - If ❌ Error: See what failed

---

## Step 2: Check Browser Console

1. **Open Developer Tools**: Press `F12`
2. **Go to Console tab**: Click "Console"
3. **Place an order**: Try to place an order in the normal checkout
4. **Look for error messages**: You'll see the actual error

### Common Console Errors:

| Error | Cause | Solution |
|-------|-------|----------|
| `Failed to fetch` | Network/endpoint issue | Check API path |
| `CORS error` | Cross-origin blocked | Check headers |
| `401 Unauthorized` | Not logged in | Login first |
| `500 Internal Server Error` | API code error | Check server logs |
| `SyntaxError: Unexpected token` | Invalid JSON response | API returning HTML |

---

## Step 3: Make Sure You're Logged In

Before placing an order:
1. Go to **Login page**: `http://localhost/login.php`
2. **Enter your credentials** and login
3. You should be **redirected to menu**
4. **Now try to place an order**

If you weren't logged in, that's the issue!

---

## Step 4: Verify Database Connection

Check if the database is connected:

1. Open database client (phpMyAdmin, MySQL Workbench)
2. Connect to database: `boycold_db`
3. Check tables exist:
   - `users` - Should have your user
   - `products` - Should have menu items
   - `cart` - Should have your items
   - `orders` - Should be able to create new orders

---

## Step 5: Check Server Logs

Check PHP error logs for detailed errors:

### For XAMPP/Laragon:
```
C:\laragon\logs\php_errors.log
C:\laragon\logs\apache_error.log
```

### Look for errors like:
```
[date] PHP Fatal error: ...
[date] MySQL Error: ...
[date] Error in orders_api.php ...
```

---

## Step 6: Test Minimal Order Creation

If diagnostic tool passes but checkout fails:

1. Make sure your **cart is not empty**
2. Make sure you **selected payment method** (COD/GCash)
3. Make sure you **selected delivery address/branch**
4. Make sure you **selected delivery type** (delivery/dine-in)

---

## Common Causes & Fixes

### Issue 1: "Not authenticated" Error
**Cause**: Session not set  
**Fix**: 
- Logout and login again
- Clear browser cookies
- Try in incognito window

### Issue 2: "Failed to fetch"
**Cause**: API endpoint wrong or PHP errors  
**Fix**:
- Check API path in checkout.php: `../api/orders_api.php`
- Run diagnostic test first
- Check server logs

### Issue 3: "Database connection failed"
**Cause**: Can't connect to MySQL  
**Fix**:
- Check MySQL is running
- Check connection details in `config/db_config.php`
- Verify credentials are correct

### Issue 4: "Cart is empty"
**Cause**: No items selected  
**Fix**:
- Add items to cart first
- Make sure cart is showing items before checkout

### Issue 5: "Invalid payment method"
**Cause**: Payment method not selected  
**Fix**:
- Make sure to select "Cash on Delivery" or "GCash"
- Both options should be visible on checkout

---

## Debug Mode - Enable More Details

If you want to see more error details:

1. **Open checkout.php**
2. The improved error messages now show:
   - HTTP status codes
   - Actual error messages
   - Console logging

2. **Check browser console** (F12 → Console)
3. You should see detailed error messages

---

## Advanced Debugging

### Check Session Exists:
Open `http://localhost/api/orders_api.php?action=test` directly  
Should return JSON with your user_id

### Check Database Query:
In `orders_api.php`, you can add:
```php
error_log("Order attempt: " . json_encode($body));
```
Then check log files

### Enable PHP Errors:
Add to `orders_api.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

---

## When Everything Else Fails

1. **Restart Apache/Nginx**: 
   - Laragon: Right-click → Restart
   - XAMPP: Click Stop then Start

2. **Restart MySQL**:
   - Make sure database is running

3. **Clear Browser Cache**:
   - Ctrl+Shift+Delete
   - Clear all cache
   - Try again

4. **Check Firewall**:
   - Make sure localhost isn't blocked

5. **Try Different Browser**:
   - Chrome, Firefox, Safari
   - See if issue is browser-specific

---

## Get Help

When asking for help, provide:

1. **Error message** from browser console
2. **Server logs** (PHP + MySQL errors)
3. **Diagnostic test results** (pass/fail for each test)
4. **Steps to reproduce** (exact steps to trigger error)

Example:
```
"Network error" when placing order
Browser console shows: "Failed to fetch"
Diagnostic test 1: ✅ Pass
Diagnostic test 2: ❌ Fail - 401 Unauthorized
Diagnostic test 3: N/A
Steps: Login → Add items → Checkout → Click Place Order
```

---

## Files to Check

- `User/checkout.php` - Order placement code
- `api/orders_api.php` - API endpoint (newly improved with better error handling!)
- `config/db_config.php` - Database connection
- `config/mailer.php` - Email configuration (if needed)

---

**You now have all the tools to diagnose and fix the issue!** 🔧
