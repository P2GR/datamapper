CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` character varying(100) NOT NULL,
    `username` character varying(20) NOT NULL,
    `email` character varying(120) NOT NULL,
    `password` character(40) NOT NULL,
    `salt` character varying(32),
    `group_id` BIGINT UNSIGNED,
	PRIMARY KEY (`id`),
	UNIQUE INDEX username (`username` ASC),
	UNIQUE INDEX email (`email` ASC)
) ENGINE = InnoDB;