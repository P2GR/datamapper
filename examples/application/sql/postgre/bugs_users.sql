CREATE TABLE "bugs_users" (
    "id" serial NOT NULL PRIMARY KEY,
    "user_id" integer,
    "bug_id" integer,
    "iscompleted" smallint DEFAULT 0 NOT NULL,
    "isowner" smallint DEFAULT 0 NOT NULL
);