# 🔧 Database Fix Guide

## ✅ Problem Found!

Your diagnostic report shows:
```
❌ payment_method column is MISSING
❌ payment_status column is MISSING
```

**This is why you're getting 500 errors when placing orders!**

---

## ⚡ Quick Fix (2 Steps)

### Step 1: Open the Fix Tool
Visit: **http://localhost/api/fix_database.php**

### Step 2: Click "Fix Database Now"
The tool will automatically add the missing columns.

---

## 📊 What's Being Added

| Column | Type | Purpose | Default |
|--------|------|---------|---------|
| `payment_method` | ENUM('cod','gcash') | Tracks payment type | 'cod' |
| `payment_status` | ENUM('unpaid','paid','cancelled') | Tracks payment state | 'unpaid' |

These columns are **essential** for COD payment flow to work.

---

## 🔍 Before and After

### BEFORE (Current - Broken)
```sql
id | user_id | status | order_type | subtotal | ... | notes
```
❌ Missing: payment_method, payment_status

### AFTER (Fixed)
```sql
id | user_id | status | order_type | payment_method | payment_status | subtotal | ... | notes
```
✅ All columns present

---

## 🚀 What Happens Next

After clicking "Fix Database Now":

1. ✅ The tool adds `payment_method` column
2. ✅ The tool adds `payment_status` column  
3. ✅ Your existing 6 orders are **not affected** (they get default values)
4. ✅ New orders will work correctly

---

## ✨ After the Fix

Your orders will have:
- `payment_method = 'cod'` or `'gcash'` (from checkout selection)
- `payment_status = 'unpaid'` initially, then `'paid'` when delivered (for COD) or immediately (for GCash)

---

## 📋 Manual SQL (If Tool Doesn't Work)

If the automatic tool fails, run this SQL manually in phpMyAdmin:

```sql
ALTER TABLE `orders` 
ADD COLUMN `payment_method` ENUM('cod','gcash') DEFAULT 'cod' AFTER `order_type`;

ALTER TABLE `orders` 
ADD COLUMN `payment_status` ENUM('unpaid','paid','cancelled') DEFAULT 'unpaid' AFTER `payment_method`;
```

**Steps:**
1. Open: `http://localhost/phpmyadmin/`
2. Select: `boycold_db`
3. Click: **SQL** tab
4. Paste both queries above
5. Click: **Go**

---

## ✅ Verify It Worked

After the fix:

1. Visit: `http://localhost/api/test_db_schema.php`
   - Should show ✅ for all columns

2. Try placing an order: `http://localhost/User/checkout.php`
   - Should work without 500 error

3. Check your order: `http://localhost/User/ordercustom.php`
   - Should see order with payment status

---

## 🎯 Summary

**Problem**: Missing database columns  
**Solution**: Use the fix tool at `http://localhost/api/fix_database.php`  
**Time**: < 30 seconds  
**Risk**: None (existing data is safe)  
**Result**: Orders will work! ✅

---

**👉 GO NOW:** http://localhost/api/fix_database.php
