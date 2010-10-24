CREATE TABLE "groups" (
    "id" serial NOT NULL PRIMARY KEY,
    "name" character varying(20) NOT NULL UNIQUE
);