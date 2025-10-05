-- Update payments table for PayHere integration
-- Run this script to update the existing payments table

USE rentfinder_sl;

-- Add 'payhere' to payment_method enum
ALTER TABLE payments 
MODIFY COLUMN payment_method ENUM('card','bank_transfer','payhere') NOT NULL;

-- Add 'commission' to payment_type enum  
ALTER TABLE payments 
MODIFY COLUMN payment_type ENUM('rent','deposit','maintenance','commission') NOT NULL;

-- Make rental_agreement_id nullable for standalone payments
ALTER TABLE payments 
MODIFY COLUMN rental_agreement_id INT NULL;

-- Add 'cancelled' to status enum
ALTER TABLE payments 
MODIFY COLUMN status ENUM('pending','completed','failed','cancelled','refunded') DEFAULT 'pending';

-- Add indexes for better performance
CREATE INDEX idx_payments_transaction_id ON payments(transaction_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_payments_created_at ON payments(created_at);

-- Add payment tracking fields
ALTER TABLE payments 
ADD COLUMN payment_gateway VARCHAR(50) DEFAULT 'payhere' AFTER payment_method,
ADD COLUMN gateway_transaction_id VARCHAR(100) AFTER transaction_id,
ADD COLUMN gateway_response TEXT AFTER gateway_transaction_id,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Update existing records to have payhere as default gateway
UPDATE payments SET payment_gateway = 'payhere' WHERE payment_gateway IS NULL;

-- Add commission tracking table
CREATE TABLE IF NOT EXISTS commission_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rental_agreement_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    commission_rate DECIMAL(5,4) NOT NULL,
    payment_id INT,
    status ENUM('pending','paid','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rental_agreement_id) REFERENCES rental_agreements(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add payment logs table for debugging
CREATE TABLE IF NOT EXISTS payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT,
    log_level ENUM('INFO','ERROR','SUCCESS','WARNING') NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for payment logs
CREATE INDEX idx_payment_logs_payment_id ON payment_logs(payment_id);
CREATE INDEX idx_payment_logs_created_at ON payment_logs(created_at);

-- Insert sample commission rates
INSERT IGNORE INTO commission_payments (rental_agreement_id, amount, commission_rate, status) 
SELECT 
    ra.id,
    ra.monthly_rent * 0.05, -- 5% commission
    0.05,
    'pending'
FROM rental_agreements ra 
WHERE ra.status = 'active' 
AND NOT EXISTS (
    SELECT 1 FROM commission_payments cp 
    WHERE cp.rental_agreement_id = ra.id
);

-- Show updated table structure
DESCRIBE payments;
DESCRIBE commission_payments;
DESCRIBE payment_logs;

-- Show sample data
SELECT 'Payments Table Sample' as table_name;
SELECT * FROM payments LIMIT 5;

SELECT 'Commission Payments Sample' as table_name;
SELECT * FROM commission_payments LIMIT 5;

