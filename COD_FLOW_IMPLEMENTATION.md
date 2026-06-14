# COD (Cash on Delivery) Flow Implementation

## Overview
Implemented a complete COD flow for BoyCold Cafe orders from menu selection through status tracking and payment settlement.

## Architecture

### Database Schema
Updated `orders` table with:
- `payment_method` ENUM('cod','gcash') - Payment method selected by customer
- `payment_status` ENUM('unpaid','paid','cancelled') - Payment settlement status

### COD Flow Sequence
```
1. Customer places order with COD payment method
   → order.status = 'pending'
   → order.payment_status = 'unpaid'

2. Admin reviews and prepares order
   → order.status = 'preparing'
   → order.payment_status = 'unpaid'

3. Admin marks ready for delivery
   → order.status = 'ready'
   → order.payment_status = 'unpaid'

4. Admin delivers order
   → order.status = 'delivered'
   → order.payment_status = 'unpaid'

5. Admin marks order completed
   → order.status = 'completed'
   → order.payment_status = 'paid' (AUTO-SET for COD)
```

## Implementation Details

### 1. Frontend Changes (User/checkout.php)
- Added payment method capture from the UI selection (COD vs GCash)
- Updated orderData object to include `payment_method` field
- Logic: 
  - "Cash On Delivery" → payment_method = 'cod'
  - "GCash" → payment_method = 'gcash'

### 2. API Endpoints

#### checkout_api.php
- Updated to receive `payment_method` from frontend
- Sets `payment_status`:
  - GCash: 'paid' (instant payment)
  - COD: 'unpaid' (payment on delivery)
- Inserts order with payment details into database

#### orders_api.php
Provides the following actions:

**place** - Create new order
```json
Request: {
  "action": "place",
  "items": [...],
  "order_type": "dine-in|takeout|delivery",
  "payment_method": "cod|gcash",
  "address": "...",
  "delivery_fee": 30,
  "tax": 5
}
```

**list** - Get orders (filters by user unless admin)
```
?action=list&status=pending (filter by status)
?action=list&all=1 (admin: get all orders)
```

**detail** - Get order with items
```
?action=detail&order_id=123
```

**update_status** - Admin only: Update order status
```json
Request: {
  "action": "update_status",
  "order_id": 123,
  "status": "pending|preparing|ready|delivered|completed"
}
```
- When status = 'completed' AND payment_method = 'cod'
- Automatically sets payment_status = 'paid'

### 3. Admin Dashboard (admin/dashboard.php)
- View all orders with payment details
- Filter by order status
- Quick stats: Pending, Preparing, Delivered, COD Unpaid
- Update order status with modal dialog
- Shows payment method and payment status for each order

### 4. Database Updates
```sql
-- Schema additions to boycold_db.sql
ALTER TABLE orders ADD COLUMN payment_method ENUM('cod','gcash') DEFAULT 'cod';
ALTER TABLE orders ADD COLUMN payment_status ENUM('unpaid','paid','cancelled') DEFAULT 'unpaid';
```

## User Flow: Menu → Order Custom → Checkout → Status

### Step 1: Menu Selection
- User browses menu at `/User/menu.php`
- Selects items and quantities

### Step 2: Custom Order
- User customizes selected items at `/User/ordercustom.php`
- Sets milk options, addons, special notes
- Adds to cart

### Step 3: Checkout
- User proceeds to `/User/checkout.php`
- Selects order type (dine-in, takeout, delivery)
- **Selects payment method (COD or GCash)**
- Confirms address if delivery
- Places order

### Step 4: Order Status Tracking
- User views order status at `/User/status.php`
- Order progresses through stages:
  - pending → preparing → ready → delivered → completed
- Shows payment status (unpaid/paid)

### Step 5: Admin Management
- Admin logs in and views dashboard at `/admin/dashboard.php`
- Can see all pending orders
- Updates order status as work progresses
- When marking completed: COD orders auto-settle (payment_status = 'paid')

## Payment Settlement for COD

**Automatic Settlement**
- When admin marks order as 'completed'
- AND payment_method = 'cod'
- System automatically sets payment_status = 'paid'
- No manual payment processing needed

**GCash Orders**
- Payment is already settled at checkout
- payment_status = 'paid' from order creation
- No payment settlement needed

## Status Workflow States

| Status | Meaning | COD Payment Status |
|--------|---------|-------------------|
| pending | Order received, waiting for confirmation | unpaid |
| preparing | Admin actively preparing order | unpaid |
| ready | Order ready for delivery/pickup | unpaid |
| delivered | Order delivered to customer | unpaid |
| completed | Order fully processed | paid (auto) |
| cancelled | Order cancelled | cancelled |

## Admin Access

Admin status is determined by checking if email contains 'admin' or equals 'admin@boycold.com'.

To add admin users:
1. Create user account
2. Update email to include 'admin' or use 'admin@boycold.com'
3. Can access `/admin/dashboard.php`

Alternative: Add `is_admin` boolean column to users table for more granular control.

## Files Modified/Created

### Modified Files
- `config/boycold_db.sql` - Added payment_method and payment_status columns
- `api/checkout_api.php` - Added payment method handling
- `User/checkout.php` - Added payment method capture in JavaScript

### Created Files
- `admin/dashboard.php` - Admin order management interface

### Existing Files (No changes needed)
- `api/orders_api.php` - Already had COD flow implementation
- `User/status.php` - Existing status tracking UI
- `User/menu.php`, `ordercustom.php` - Existing order UI

## Testing Checklist

- [ ] User can select COD at checkout
- [ ] Order created with payment_method='cod', payment_status='unpaid'
- [ ] Admin can view orders in dashboard
- [ ] Admin can update order status
- [ ] When admin marks complete, payment_status auto-sets to 'paid'
- [ ] GCash orders stay paid=true regardless of status
- [ ] Status filtering works in admin dashboard
- [ ] Stats update correctly (pending, preparing, delivered, cod_unpaid counts)

## Future Enhancements

- Add SMS/Email notifications when order status changes
- Add delivery person assignment
- Add order rating/review after completion
- Add refund handling for cancelled COD orders
- Add payment reconciliation reports
- Add receipt printing for completed orders
