
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
    `customer_id` INTEGER NOT NULL,
    `order_id` INTEGER NOT NULL,
    `expired_at` DATE,
    `created_at` DATE,
    `updated_at` DATE,
    PRIMARY KEY (`id`),
    INDEX `idx_bridge_payment_link_uuid` (`uuid`),
    INDEX `bridge_payment_link_fi_7e8f3e` (`customer_id`),
    INDEX `bridge_payment_link_fi_75704f` (`order_id`),
    CONSTRAINT `bridge_payment_link_fk_7e8f3e`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customer` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `bridge_payment_link_fk_75704f`
        FOREIGN KEY (`order_id`)
        REFERENCES `order` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- bridge_payment_request
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `bridge_payment_request`;

CREATE TABLE `bridge_payment_request`
(
    `uuid` VARCHAR(150) NOT NULL,
    `status` VARCHAR(50) NOT NULL,
    `payment_link_uuid` VARCHAR(150) NOT NULL,
    `created_at` DATE,
    `updated_at` DATE,
    PRIMARY KEY (`uuid`),
    INDEX `idx_bridge_payment_request_uuid` (`uuid`),
    INDEX `bridge_payment_request_fi_e31f7c` (`payment_link_uuid`),
    CONSTRAINT `bridge_payment_request_fk_e31f7c`
        FOREIGN KEY (`payment_link_uuid`)
        REFERENCES `bridge_payment_link` (`uuid`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
