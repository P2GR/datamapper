CREATE TABLE "dependencies_dependents" (
    "id" serial NOT NULL PRIMARY KEY,
    "dependency_id" integer NOT NULL,
    "dependent_id" integer NOT NULL
);