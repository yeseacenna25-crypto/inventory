-- Database update script for enhanced checkout functionality
-- Run this SQL in your MySQL database (inventory_negrita)

-- Add missing columns to orders table
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS handling_fee DECIMAL(10,2) DEFAULT 10.00 AFTER total_amount,
ADD COLUMN IF NOT EXISTS final_total DECIMAL(10,2) NOT NULL AFTER handling_fee,
ADD COLUMN IF NOT EXISTS user_type ENUM('admin', 'distributor') DEFAULT 'admin' AFTER created_by;

-- Update the foreign key constraint to allow both admin and distributor users
-- First, drop the existing foreign key if it exists
ALTER TABLE orders DROP FOREIGN KEY IF EXISTS orders_ibfk_1;

-- Update existing records to calculate final_total if not set
UPDATE orders 
SET final_total = total_amount + IFNULL(handling_fee, 10.00)
WHERE final_total IS NULL OR final_total = 0;

-- Create a view for order management that joins with user information
CREATE OR REPLACE VIEW order_summary AS
SELECT 
    o.order_id,
    o.customer_name,
    o.customer_contact,
    o.customer_address,
    o.total_amount,
    o.handling_fee,
    o.final_total,
    o.status,
    o.user_type,
    o.created_by,
    o.created_at,
    o.updated_at,
    CASE 
        WHEN o.user_type = 'admin' THEN a.name
        WHEN o.user_type = 'distributor' THEN d.name
        ELSE 'Unknown'
    END as created_by_name,
    COUNT(oi.order_item_id) as total_items
FROM orders o
LEFT JOIN admin_signup a ON o.user_type = 'admin' AND o.created_by = a.admin_id
LEFT JOIN distributor_signup d ON o.user_type = 'distributor' AND o.created_by = d.distributor_id
LEFT JOIN order_items oi ON o.order_id = oi.order_id
GROUP BY o.order_id
ORDER BY o.created_at DESC;

-- Verify the table structure
DESCRIBE orders;

-- Show sample data
SELECT 'Current orders count:' as info, COUNT(*) as value FROM orders
UNION ALL
SELECT 'Orders with pending status:', COUNT(*) FROM orders WHERE status = 'pending'
UNION ALL
SELECT 'Order items count:', COUNT(*) FROM order_items;
