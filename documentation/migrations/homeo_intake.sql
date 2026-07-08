-- Homeopathy Intake Questionnaire
-- Run once on the server database. Safe to re-run (IF NOT EXISTS).

CREATE TABLE IF NOT EXISTS `homeo_intake` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `token`         CHAR(40)        NOT NULL,
    `patient_id`    INT UNSIGNED    NULL,
    `created_by`    INT UNSIGNED    NULL,
    `status`        ENUM('sent','submitted','locked') NOT NULL DEFAULT 'sent',
    `expires_at`    DATETIME        NULL,
    `submitted_at`  DATETIME        NULL,
    `answers`       LONGTEXT        NULL,   -- JSON
    `miasm_scores`  TEXT            NULL,   -- JSON
    `thermal`       VARCHAR(16)     NULL,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_token` (`token`),
    KEY `idx_patient` (`patient_id`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
