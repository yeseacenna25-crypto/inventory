-- Safe step-by-step approach to connect orders to products
-- Run each section separately and check for errors

-- STEP 1: Check current table engines
SELECT TABLE_NAME, ENGINE 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'inventory_negrita' 
AND TABLE_NAME IN ('products', 'order_items', 'orders');

-- STEP 2: Check if there are any orphaned records
SELECT COUNT(*) as orphaned_records
FROM order_items oi
LEFT JOIN products p ON oi.product_id = p.product_id
WHERE p.product_id IS NULL;

-- STEP 3: If orphaned records exist, you have options:
-- Option A: Delete orphaned records (CAREFUL - this deletes data)
-- DELETE FROM order_items WHERE product_id NOT IN (SELECT product_id FROM products);

-- Option B: Create missing products for orphaned records
-- INSERT INTO products (product_id, product_name, quantity, price, description)
-- SELECT DISTINCT oi.product_id, oi.product_name, 0, 0, 'Imported from order'
-- FROM order_items oi
-- LEFT JOIN products p ON oi.product_id = p.product_id
-- WHERE p.product_id IS NULL;

-- STEP 4: Convert tables to InnoDB if needed
-- ALTER TABLE products ENGINE=InnoDB;
-- ALTER TABLE order_items ENGINE=InnoDB;
-- ALTER TABLE orders ENGINE=InnoDB;

-- STEP 5: Try to add the foreign key constraint
-- ALTER TABLE order_items 
-- ADD CONSTRAINT fk_order_items_product 
-- FOREIGN KEY (product_id) REFERENCES products(product_id) 
-- ON DELETE RESTRICT ON UPDATE CASCADE;

-- STEP 6: Verify the constraint was added
-- SELECT 
--     CONSTRAINT_NAME,
--     TABLE_NAME,
--     COLUMN_NAME,
--     REFERENCED_TABLE_NAME,
--     REFERENCED_COLUMN_NAME
-- FROM information_schema.KEY_COLUMN_USAGE
-- WHERE CONSTRAINT_SCHEMA = 'inventory_negrita'
-- AND TABLE_NAME = 'order_items'
-- AND CONSTRAINT_NAME = 'fk_order_items_product';

-- STEP 7: Test the connection
-- SELECT oi.order_id, oi.product_name, p.product_name as actual_product_name, p.quantity
-- FROM order_items oi
-- JOIN products p ON oi.product_id = p.product_id
-- LIMIT 5;
