-- Clinic Expense Manager
-- Run once on the server database. Safe to re-run (IF NOT EXISTS).

CREATE TABLE IF NOT EXISTS `expense` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `expense_date`  DATE            NOT NULL,
    `category`      VARCHAR(60)     NOT NULL,
    `description`   VARCHAR(255)    NULL,
    `vendor`        VARCHAR(120)    NULL,
    `amount`        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `payment_mode`  VARCHAR(20)     NOT NULL DEFAULT 'cash',
    `created_by`    INT UNSIGNED    NULL,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_date` (`expense_date`),
    KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
