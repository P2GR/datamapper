CREATE TABLE `bugs_users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED,
    `bug_id` BIGINT UNSIGNED,
    `iscompleted` smallint DEFAULT 0 NOT NULL,
    `isowner` smallint DEFAULT 0 NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE = InnoDB;