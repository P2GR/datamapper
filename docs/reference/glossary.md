# Glossary

Quick definitions for terms that appear throughout the DataMapper documentation.

## Advanced Relationship

A relationship that goes beyond the conventional naming conventions—multiple associations to the same model, custom join keys, or self-referencing links. See [Advanced Relationship Patterns](/guide/relationships/advanced).

## Deep Relationship

Any relationship that spans more than one hop from the current model. Specify deep relationships with a slash-delimited path such as `author/profile/avatar`.

## Get (Advanced)

The companion to the standard `get()` method that provides additional query helpers for complex filters and join fields. See [Get (Advanced)](/guide/models/get-advanced).

## DMZ

Short for *DataMapper OverZealous Edition*, the upstream project that inspired DataMapper 2.0.

## Extension

A class that augments a DataMapper model without modifying its source. Extensions live in `application/datamapper/` and are covered in [Using Extensions](/guide/extensions/).

## Has Many Relationship

Associates one record with many related records. Example: a `User` has many `Post` entries. Reviewed in [Relationship Types](/guide/relationships/types).

## Has One Relationship

Associates one record with exactly one related record. Example: a `User` has one `Profile`. See [Relationship Types](/guide/relationships/types).

## Join Table

An intermediate table that connects two models in a many-to-many relationship. Learn more in [Database Tables](/guide/getting-started/database) and [Get (Advanced)](/guide/models/get-advanced#include_join_fields).

## Many-to-Many Relationship

Both sides of the relationship can have multiple related objects. Example: users and groups. See [Relationship Types](/guide/relationships/types).

## Method Chaining

Linking multiple method calls in a single expression for readability. DataMapper supports chaining most query builders—review [Method Chaining](/guide/models/get#method-chaining) for examples.

## Object-Relational Mapping (ORM)

The technique of mapping database tables to PHP objects. DataMapper is an ORM library. See the [Wikipedia entry](https://en.wikipedia.org/wiki/Object–relational_mapping) for background.

## Query Grouping

Wrapping parts of a query in parentheses to control precedence. DataMapper mirrors CodeIgniter's Active Record behaviour—see [Query Grouping](/guide/models/get#query-grouping).

## Self Relationship

A relationship where a model relates to itself, such as hierarchical categories. Covered in [Advanced Relationship Patterns](/guide/relationships/advanced).

## Validation Rules

Reusable constraints applied to model fields. DataMapper's validation layer is explained in [Advanced Validation](/guide/advanced/validation).