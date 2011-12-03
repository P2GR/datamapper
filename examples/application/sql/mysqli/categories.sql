CREATE TABLE `categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` character varying(40) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX name (`name` ASC)
) ENGINE = InnoDB;