CREATE TABLE "users" (
    "id" serial NOT NULL PRIMARY KEY,
    "name" character varying(100) NOT NULL,
    "username" character varying(20) NOT NULL UNIQUE,
    "email" character varying(120) NOT NULL UNIQUE,
    "password" character(40) NOT NULL,
    "salt" character varying(32),
    "group_id" integer
);