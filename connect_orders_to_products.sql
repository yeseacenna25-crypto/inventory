-- Script to standardize products table and connect it properly to orders
-- Run this to ensure proper connection between orders and products

-- 1. First, let's check the current products table structure
-- DESCRIBE products;

-- 2. Standardize the products table structure (only if columns exist with different names)
-- Check if 'stock' column exists and 'quantity' doesn't
SET @column_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'stock');
SET @quantity_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'quantity');

-- Rename stock to quantity if needed
SET @sql = IF(@column_exists > 0 AND @quantity_exists = 0, 
    'ALTER TABLE products CHANGE COLUMN stock quantity INT NOT NULL DEFAULT 0', 
    'SELECT "quantity column already exists or stock column not found" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if 'image' column exists and 'product_image' doesn't
SET @image_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'image');
SET @product_image_exists = (SELECT COUNT(*) FROM information_schema.columns 
    WHERE table_schema = DATABASE() AND table_name = 'products' AND column_name = 'product_image');

-- Rename image to product_image if needed
SET @sql = IF(@image_exists > 0 AND @product_image_exists = 0, 
    'ALTER TABLE products CHANGE COLUMN image product_image VARCHAR(255)', 
    'SELECT "product_image column already exists or image column not found" as message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Ensure the products table has the right structure
-- Add missing columns if they don't exist
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS category VARCHAR(100) AFTER product_name;

ALTER TABLE products 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE products 
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 4. Add the foreign key constraint to connect orders to products
-- First, ensure both tables use the same engine (InnoDB supports foreign keys)
ALTER TABLE products ENGINE=InnoDB;
ALTER TABLE order_items ENGINE=InnoDB;

-- Clean up any orphaned records in order_items that don't have matching products
DELETE FROM order_items 
WHERE product_id NOT IN (SELECT product_id FROM products);

-- Now add the foreign key constraint
ALTER TABLE order_items 
ADD CONSTRAINT fk_order_items_product 
FOREIGN KEY (product_id) REFERENCES products(product_id) 
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Alternative approach if the above still fails:
-- You can skip the foreign key and rely on application-level validation
-- The order system will still work perfectly without this constraint

-- 5. Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_products_name ON products(product_name);
CREATE INDEX IF NOT EXISTS idx_products_category ON products(category);
CREATE INDEX IF NOT EXISTS idx_products_quantity ON products(quantity);

-- 6. Sample query to verify the connection works
-- SELECT oi.*, p.product_name, p.quantity as current_stock 
-- FROM order_items oi 
-- JOIN products p ON oi.product_id = p.product_id;
