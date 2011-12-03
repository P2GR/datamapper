CREATE TABLE `comments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `comment` text,
    `created` DATETIME NULL,
    `updated` DATETIME NULL,
    `user_id` BIGINT UNSIGNED,
    `bug_id` BIGINT UNSIGNED,
	PRIMARY KEY (`id`)
) ENGINE = InnoDB;