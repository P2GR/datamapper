CREATE TABLE `dependencies_dependents` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `dependency_id` BIGINT UNSIGNED NOT NULL,
    `dependent_id` BIGINT UNSIGNED NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE = InnoDB;