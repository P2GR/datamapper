CREATE TABLE "categories" (
    "id" serial NOT NULL PRIMARY KEY,
    "name" character varying(40) NOT NULL UNIQUE
);