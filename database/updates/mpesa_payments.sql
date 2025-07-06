
ALTER TABLE payments
ADD COLUMN merchant_request_id VARCHAR(50) NULL AFTER transaction_id,
ADD COLUMN checkout_request_id VARCHAR(50) NULL AFTER merchant_request_id,
ADD COLUMN phone_number VARCHAR(20) NULL AFTER amount,
ADD COLUMN response_code VARCHAR(10) NULL AFTER status,
ADD COLUMN response_description TEXT NULL AFTER response_code,
ADD COLUMN callback_metadata JSON NULL AFTER response_description;

ALTER TABLE payments
ADD INDEX idx_merchant_request_id (merchant_request_id),
ADD INDEX idx_checkout_request_id (checkout_request_id);

CREATE TABLE IF NOT EXISTS mpesa_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    transaction_date DATETIME NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    reference VARCHAR(100) NOT NULL,
    result_code INT NOT NULL,
    result_description TEXT,
    merchant_request_id VARCHAR(50) NOT NULL,
    checkout_request_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE mpesa_transactions
ADD INDEX idx_merchant_request_id (merchant_request_id),
ADD INDEX idx_checkout_request_id (checkout_request_id),
ADD INDEX idx_phone_number (phone_number);
