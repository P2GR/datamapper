CREATE TABLE "statuses" (
    "id" serial NOT NULL PRIMARY KEY,
    "name" character varying(40) NOT NULL,
    "closed" smallint DEFAULT 0 NOT NULL,
    "sortorder" integer DEFAULT 0 NOT NULL
);