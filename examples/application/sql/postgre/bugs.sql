CREATE TABLE "bugs" (
    "id" serial NOT NULL PRIMARY KEY,
    "title" character varying(100) NOT NULL,
    "description" text,
    "priority" smallint DEFAULT 0 NOT NULL,
    "created" timestamp with time zone DEFAULT now() NOT NULL,
    "updated" timestamp with time zone DEFAULT now() NOT NULL,
    "status_id" integer,
    "creator_id" integer,
    "editor_id" integer
);