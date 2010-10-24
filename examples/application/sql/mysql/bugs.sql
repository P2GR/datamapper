CREATE TABLE `bugs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` character varying(100) NOT NULL,
    `description` text,
    `priority` smallint DEFAULT 0 NOT NULL,
    `created` DATETIME NULL,
    `updated` DATETIME NULL,
    `status_id` BIGINT UNSIGNED,
    `creator_id` BIGINT UNSIGNED,
    `editor_id` BIGINT UNSIGNED,
	PRIMARY KEY (`id`)
) ENGINE = InnoDB;