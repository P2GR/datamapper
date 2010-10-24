CREATE TABLE "bugs_categories" (
    "id" serial NOT NULL PRIMARY KEY,
    "bug_id" integer NOT NULL,
    "category_id" integer NOT NULL
);