# Database Schema - Orders Table with Payment Tracking

## Current Orders Table Structure

```sql
CREATE TABLE IF NOT EXISTS `orders` (
  `id`             INT            NOT NULL AUTO_INCREMENT,
  `user_id`        INT            NOT NULL,
  `status`         ENUM('pending','confirmed','preparing',
                        'ready','delivered','cancelled')
                                NOT NULL DEFAULT 'pending',
  `order_type`     ENUM('dine-in','takeout','delivery')
                                DEFAULT 'dine-in',
  
  -- NEW: Payment Tracking Columns
  `payment_method` ENUM('cod','gcash')
                                DEFAULT 'cod',
  `payment_status` ENUM('unpaid','paid','cancelled')
                                DEFAULT 'unpaid',
  
  `subtotal`       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `delivery_fee`   DECIMAL(10,2)           DEFAULT 0.00,
  `tax`            DECIMAL(10,2)           DEFAULT 0.00,
  `total`          DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `address`        VARCHAR(255)            DEFAULT NULL,
  `notes`          TEXT                    DEFAULT NULL,
  `created_at`     DATETIME                DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME                DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Column Descriptions

### Payment Method
- **Column**: `payment_method`
- **Type**: `ENUM('cod','gcash')`
- **Default**: `'cod'`
- **Allowed Values**:
  - `'cod'` - Cash on Delivery (customer pays on delivery)
  - `'gcash'` - GCash (customer pays online before delivery)

### Payment Status
- **Column**: `payment_status`
- **Type**: `ENUM('unpaid','paid','cancelled')`
- **Default**: `'unpaid'`
- **Allowed Values**:
  - `'unpaid'` - Payment pending (used for COD orders)
  - `'paid'` - Payment received (used for GCash orders or when COD delivered)
  - `'cancelled'` - Order/Payment cancelled

## Payment Logic

### At Order Creation (checkout_api.php)
```
IF payment_method = 'gcash'
    → payment_status = 'paid' (immediate)
ELSE (payment_method = 'cod')
    → payment_status = 'unpaid' (pending)
```

### At Order Completion (orders_api.php)
```
IF order status = 'completed' AND payment_method = 'cod'
    → payment_status = 'paid' (auto-update)
```

## Query Examples

### 1. View all COD orders awaiting payment
```sql
SELECT id, user_id, status, payment_method, payment_status, total, created_at
FROM orders
WHERE payment_method = 'cod' AND payment_status = 'unpaid'
ORDER BY created_at DESC;
```

### 2. View all paid orders (completed transactions)
```sql
SELECT id, user_id, status, payment_method, total, created_at
FROM orders
WHERE payment_status = 'paid'
ORDER BY created_at DESC;
```

### 3. Check order payment details
```sql
SELECT 
  id,
  user_id,
  status,
  order_type,
  payment_method,
  payment_status,
  total,
  created_at,
  updated_at
FROM orders
WHERE id = 123;
```

### 4. Get payment summary by method
```sql
SELECT 
  payment_method,
  payment_status,
  COUNT(*) as order_count,
  SUM(total) as total_amount
FROM orders
WHERE DATE(created_at) = CURDATE()
GROUP BY payment_method, payment_status;
```

### 5. Find COD orders that have been delivered but not paid yet
```sql
SELECT id, user_id, status, total, delivered_at
FROM orders
WHERE payment_method = 'cod' 
  AND status = 'delivered' 
  AND payment_status = 'unpaid'
ORDER BY created_at ASC;
```

### 6. Manually settle a COD payment (admin action)
```sql
UPDATE orders
SET payment_status = 'paid', updated_at = NOW()
WHERE id = 123 AND payment_method = 'cod';
```

### 7. Cancel an order and its payment
```sql
UPDATE orders
SET status = 'cancelled', payment_status = 'cancelled', updated_at = NOW()
WHERE id = 123;
```

## Index Recommendations

For better query performance:
```sql
-- Index for finding pending COD orders
ALTER TABLE orders ADD INDEX idx_payment_pending 
  (payment_method, payment_status, status);

-- Index for finding orders by user
ALTER TABLE orders ADD INDEX idx_user_id (user_id);

-- Index for finding orders by date
ALTER TABLE orders ADD INDEX idx_created_at (created_at);
```

## Migration Steps

If updating existing database:

```sql
-- Step 1: Add payment_method column
ALTER TABLE orders ADD COLUMN payment_method 
  ENUM('cod','gcash') DEFAULT 'cod' AFTER order_type;

-- Step 2: Add payment_status column
ALTER TABLE orders ADD COLUMN payment_status 
  ENUM('unpaid','paid','cancelled') DEFAULT 'unpaid' AFTER payment_method;

-- Step 3: Update existing orders to 'paid' if they're already completed
UPDATE orders SET payment_status = 'paid' WHERE status = 'completed';

-- Step 4: Add indexes for performance
ALTER TABLE orders ADD INDEX idx_payment_pending 
  (payment_method, payment_status, status);
```

## Notes

- Both columns use `ENUM` for memory efficiency and constrained values
- Default `payment_method = 'cod'` for backward compatibility
- Default `payment_status = 'unpaid'` - must be explicitly set to 'paid'
- The `orders_api.php` automatically sets `payment_status = 'paid'` when:
  - Order status becomes 'completed' AND
  - Payment method is 'cod'
