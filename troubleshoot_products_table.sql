-- Troubleshooting script to check and fix products table structure
-- Run these commands to verify your products table structure

-- 1. Check if products table exists and view its structure
DESCRIBE products;

-- 2. Show the create statement for products table
SHOW CREATE TABLE products;

-- 3. If products table doesn't exist, create it with this structure:
-- (Only run this if the table doesn't exist)
/*
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    product_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
*/

-- 4. If you want to add the foreign key constraint later, use this:
-- ALTER TABLE order_items ADD CONSTRAINT fk_order_items_product 
-- FOREIGN KEY (product_id) REFERENCES products(product_id);

-- 5. Sample products data (for testing)
/*
INSERT INTO products (product_name, category, quantity, price, description) VALUES
('Sample Product 1', 'Electronics', 100, 299.99, 'A sample electronic product'),
('Sample Product 2', 'Clothing', 50, 49.99, 'A sample clothing item'),
('Sample Product 3', 'Books', 200, 19.99, 'A sample book product');
*/
