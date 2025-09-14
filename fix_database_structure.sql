-- Add profile_image column to all user tables if not exists
-- This SQL script will fix the "Unknown column 'profile_image'" error

-- Add profile_image column to admin_signup table
ALTER TABLE admin_signup 
ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL AFTER email;

-- Add profile_image column to staff_signup table
ALTER TABLE staff_signup 
ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL AFTER email;

-- Add profile_image column to distributor_signup table
ALTER TABLE distributor_signup 
ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL AFTER email;

-- Add staff_id and distributor_id columns to orders table for user tracking
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS staff_id INT DEFAULT NULL AFTER created_by,
ADD COLUMN IF NOT EXISTS distributor_id INT DEFAULT NULL AFTER staff_id;

-- Add foreign key constraints for referential integrity (optional)
-- ALTER TABLE orders ADD FOREIGN KEY (staff_id) REFERENCES staff_signup(staff_id) ON DELETE SET NULL;
-- ALTER TABLE orders ADD FOREIGN KEY (distributor_id) REFERENCES distributor_signup(distributor_id) ON DELETE SET NULL;

-- Create log tables if they don't exist
CREATE TABLE IF NOT EXISTS admin_profile_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_by INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_changed_by (changed_by),
    INDEX idx_change_date (change_date)
);

CREATE TABLE IF NOT EXISTS staff_profile_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_by INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_changed_by (changed_by),
    INDEX idx_change_date (change_date)
);

CREATE TABLE IF NOT EXISTS distributor_profile_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_by INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    change_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_changed_by (changed_by),
    INDEX idx_change_date (change_date)
);
  