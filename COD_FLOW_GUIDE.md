# COD Flow Implementation - Quick Start Guide

## What Was Implemented

### 1. Database Schema Updates ✓
- Added `payment_method` column (ENUM: 'cod', 'gcash')
- Added `payment_status` column (ENUM: 'unpaid', 'paid', 'cancelled')
- Location: `/config/boycold_db.sql`

### 2. Frontend Changes ✓

#### Checkout Page (`/User/checkout.php`)
- Captures selected payment method from UI radio buttons
- Sends `payment_method` in order data to API
- Mapping:
  - "Cash On Delivery" → `'cod'`
  - "GCash" → `'gcash'`

#### Payment UI (Already Existed)
- Two payment options available:
  1. GCash (immediate payment)
  2. Cash On Delivery (pay later)

### 3. Backend Changes ✓

#### Checkout API (`/api/checkout_api.php`)
- Validates payment method
- Sets payment_status based on method:
  - GCash → `'paid'` (instant)
  - COD → `'unpaid'` (pay on delivery)
- Saves to database with order

#### Orders API (`/api/orders_api.php`)
- **Already had full COD implementation!**
- Action `update_status`: When admin marks order as 'completed'
  - Automatically sets `payment_status = 'paid'` for COD orders
  - Leaves GCash orders unchanged (already paid)

### 4. Admin Dashboard ✓

#### New Admin Dashboard (`/admin/dashboard.php`)
Features:
- View all orders with payment details
- Filter by order status
- Quick statistics dashboard
  - Pending orders count
  - Orders being prepared
  - Delivered today
  - COD unpaid count
- Update order status with modal dialog
- Shows payment method and payment status for each order

**Access Control:**
- Users with email containing "admin" or exact email "admin@boycold.com"
- Can access `/admin/dashboard.php`

## How It Works: Complete COD Flow

```
CUSTOMER ACTION                 SYSTEM STATE
─────────────────────────────────────────────────────────
Browse Menu                     
├─ Select items
├─ Customize order
└─ Add to cart

Proceed to Checkout
├─ Select Order Type
│  (dine-in/takeout/delivery)
├─ [NEW] Select Payment Method  payment_method = null
│  • Cash On Delivery        or  payment_method = 'cod'
│  • GCash
└─ Select Address (if delivery)

Place Order
├─ Frontend sends payment_method
├─ Backend validates & sets:     ORDER CREATED:
│  - payment_status = 'unpaid'   status = 'pending'
│    (for COD)                   payment_method = 'cod'
│  - payment_status = 'paid'     payment_status = 'unpaid'
│    (for GCash)
└─ Order inserted in database

ADMIN ACTIONS                   SYSTEM STATE
─────────────────────────────────────────────────────────
View Dashboard
└─ See pending COD orders

Prepare Order
└─ Click "Update"               status = 'preparing'
                                payment_status = 'unpaid'

Mark Ready
└─ Click "Update"               status = 'ready'
                                payment_status = 'unpaid'

Deliver Order
└─ Click "Update"               status = 'delivered'
                                payment_status = 'unpaid'

Complete Order
└─ Click "Update"
   - Backend sees:
     - status = 'completed'
     - payment_method = 'cod'
   - Auto-triggers:             status = 'completed'
     payment_status = 'paid'    payment_status = 'paid'
                                (AUTO SET!)
```

## Setup Instructions

### 1. Update Database
Run the SQL migrations:
```sql
-- These are already in boycold_db.sql
ALTER TABLE orders ADD COLUMN payment_method ENUM('cod','gcash') DEFAULT 'cod';
ALTER TABLE orders ADD COLUMN payment_status ENUM('unpaid','paid','cancelled') DEFAULT 'unpaid';
```

### 2. Make Admin User
```sql
UPDATE users SET email = 'admin@boycold.com' WHERE id = <your_admin_user_id>;
-- OR: email contains 'admin' will work too
UPDATE users SET email = 'john_admin@boycold.com' WHERE id = <your_admin_user_id>;
```

### 3. Access Admin Dashboard
Go to: `http://localhost/User/../admin/dashboard.php`
or: `http://yoursite.com/admin/dashboard.php`

## Files to Review

1. **Database**: `/config/boycold_db.sql` - Lines 94-112
2. **Frontend**: `/User/checkout.php` - Lines 425-452
3. **API**: `/api/checkout_api.php` - Lines 42-69
4. **API**: `/api/orders_api.php` - Full file (already implemented!)
5. **Admin**: `/admin/dashboard.php` - Complete new dashboard

## Testing the Flow

### Step 1: Customer Places COD Order
1. Go to Menu
2. Add items to cart
3. Go to Checkout
4. Select **"Cash On Delivery"**
5. Place order
6. Check database: `SELECT * FROM orders WHERE id=<order_id>`
   - Should show: `payment_method='cod', payment_status='unpaid'`

### Step 2: Admin Manages Order
1. Go to `/admin/dashboard.php`
2. Find the pending COD order
3. Click "Update"
4. Change status through:
   - pending → preparing → ready → delivered → **completed**
5. When marking as **completed**, check database:
   - Should auto-set: `payment_status='paid'`

### Step 3: Verify GCash Orders
1. Place order selecting **"GCash"**
2. Check database: `payment_status='paid'` (immediate)
3. Update order status to completed
4. Verify: `payment_status` stays `'paid'` (unchanged)

## API Endpoints for Integration

### Create Order
```
POST /api/checkout_api.php

Body: {
  "items": [...],
  "order_type": "delivery",
  "payment_method": "cod",        // NEW
  "address": "...",
  "delivery_fee": 30,
  "tax": 5
}

Response: {
  "success": true,
  "order_id": 123,
  "total": "500.00",
  "payment_method": "cod",        // NEW
  "payment_status": "unpaid"      // NEW
}
```

### Update Order Status (Admin Only)
```
POST /api/orders_api.php

Body: {
  "action": "update_status",
  "order_id": 123,
  "status": "completed"
}

Response: {
  "success": true,
  "message": "Order status set to 'completed'."
}

Note: When status='completed' AND payment_method='cod'
      → payment_status AUTO-SET to 'paid'
```

### Get Orders (Admin)
```
GET /api/orders_api.php?action=list&all=1&status=pending

Response: {
  "success": true,
  "orders": [
    {
      "id": 123,
      "user_id": 5,
      "status": "pending",
      "order_type": "delivery",
      "payment_method": "cod",        // NEW
      "payment_status": "unpaid",     // NEW
      "total": "500.00",
      "created_at": "2026-06-14 ..."
    }
  ]
}
```

## Troubleshooting

### Admin Dashboard Shows No Orders
- Check: Is your user email set to include "admin" or "admin@boycold.com"?
- Check: Are there orders in the database?
- Check: Browser console for JavaScript errors

### Payment Status Not Updating
- Verify: orders_api.php is reachable
- Verify: User making the request has admin access
- Check: Order status is being set to 'completed'
- Check: Database logs for SQL errors

### Payment Method Not Saving
- Verify: checkout_api.php receives the `payment_method` field
- Check: `payment_method` value is 'cod' or 'gcash' (not 'online' or other values)
- Check: Database columns exist

## Next Steps (Optional Enhancements)

1. **Add Notifications**
   - SMS/Email when order status changes
   - Customer receives "Order completed! Payment collected."

2. **Add Receipt Printing**
   - Generate receipt when order completed
   - Mark COD as "PAID" on receipt

3. **Add Refund Handling**
   - Manual refund option for cancelled COD orders
   - Change payment_status to 'cancelled'

4. **Add Payment Reconciliation**
   - Report of daily COD collections
   - Cash vs GCash breakdown

5. **Delivery Person Assignment**
   - Assign delivery person when status changes to 'ready'
   - Track delivery completion
