
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- bridge_payment_link
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `bridge_payment_link`;

CREATE TABLE `bridge_payment_link`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(150) NOT NULL,
    `link` VARCHAR(200) NOT NULL,
    `status` VARCHAR(50),
    `order_id` INTEGER NOT NULL,
    `expired_at` DATE,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `idx_bridge_payment_link_uuid` (`uuid`),
    INDEX `bridge_payment_link_fi_75704f` (`order_id`),
    CONSTRAINT `bridge_payment_link_fk_75704f`
        FOREIGN KEY (`order_id`)
            REFERENCES `order` (`id`)
            ON UPDATE RESTRICT
            ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- bridge_payment_transaction
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `bridge_payment_transaction`;

CREATE TABLE `bridge_payment_transaction`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(150) NOT NULL,
    `order_id` INTEGER NOT NULL,
    `status` VARCHAR(50),
    `status_reason` VARCHAR(150),
    `payment_link_id` VARCHAR(150) NOT NULL,
    `payment_request_id` VARCHAR(150) NOT NULL,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `idx_bridge_payment_transaction_uuid` (`uuid`),
    INDEX `idx_bridge_payment_transaction_payment_request_id` (`payment_request_id`),
    INDEX `bridge_payment_transaction_fi_75704f` (`order_id`),
    INDEX `bridge_payment_transaction_fi_a08ebd` (`payment_link_id`),
    CONSTRAINT `bridge_payment_transaction_fk_75704f`
        FOREIGN KEY (`order_id`)
            REFERENCES `order` (`id`)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,
    CONSTRAINT `bridge_payment_transaction_fk_a08ebd`
        FOREIGN KEY (`payment_link_id`)
            REFERENCES `bridge_payment_link` (`uuid`)
            ON UPDATE RESTRICT
            ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
