CREATE TABLE "comments" (
    "id" serial NOT NULL PRIMARY KEY,
    "comment" text,
    "created" timestamp with time zone DEFAULT now() NOT NULL,
    "updated" timestamp with time zone DEFAULT now() NOT NULL,
    "user_id" integer,
    "bug_id" integer
);