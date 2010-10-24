CREATE TABLE `statuses` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` character varying(40) NOT NULL,
    `closed` smallint DEFAULT 0 NOT NULL,
    `sortorder` BIGINT UNSIGNED DEFAULT 0 NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX name (`name` ASC)
) ENGINE = InnoDB;