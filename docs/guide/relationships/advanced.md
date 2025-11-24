# Advanced Relationship Patterns

Complex data models sometimes require more than the built-in `has_one`, `has_many`, or many-to-many defaults. DataMapper supports "advanced" relationships that let you customize join keys, reuse the same model multiple times, and even relate a model to itself.

## When to Reach for Advanced Relationships

Use an advanced relationship when any of the following applies:

- A model must connect to the same related model in multiple ways (for example, `author` and `editor` relationships that both point to `User`).
- The join table name or foreign keys do not follow the standard DataMapper naming conventions.
- You need to include pivot attributes or metadata on the join table and access them alongside the related models.
- A record relates to itself (nested set, organisational chart, or threaded comments).

These scenarios share the same building blocks: relationship aliases, custom join definitions, and relationship keys. You can configure all of them with the `$has_one`, `$has_many`, and `$auto_populate` arrays.

## Quick Example: Dual User Relationships

```php
class Post extends DataMapper {
    public $has_one = [
        'author' => [
            'class'       => 'user',
            'other_field' => 'authored_post',
            'join_other_as' => 'post',
            'join_self_as'  => 'author'
        ],
        'editor' => [
            'class'       => 'user',
            'other_field' => 'edited_post',
            'join_other_as' => 'post',
            'join_self_as'  => 'editor'
        ],
    ];
}
```

Each relationship defines its own alias (`author`, `editor`) and maps back to a distinct `other_field` on the `User` model. This keeps the associations clear while still pointing to the same underlying table.

## Join Tables with Extra Data

Advanced relationships can expose columns from the join table. Combine `include_join_fields()` and `query_join_fields()` to pull pivot data through to your related models.

```php
class Student extends DataMapper {
    public $has_many = [
        'course' => [
            'class'         => 'course',
            'other_field'   => 'student',
            'join_table'    => 'courses_students',
            'join_self_as'  => 'student',
            'join_other_as' => 'course'
        ]
    ];
}

$student = (new Student())
    ->include_related('course')
    ->get_by_id(7);

$student->course
    ->include_join_fields()
    ->get();

foreach ($student->course as $course) {
    echo $course->name;
    echo $course->courses_student_enrolled_at; // Example pivot attribute (see docs for naming rules)
}
```

Read the [Including Join Fields](/guide/models/get-advanced#include_join_fields) guide for the naming conventions that DataMapper applies to these attributes.

## Self-Referential Relationships

To model hierarchies, define a relationship that points back to the same class. Combine `class`, `other_field`, and `join_self_as` values to make the intent clear.

```php
class Category extends DataMapper {
    public $has_one = [
        'parent' => [
            'class'       => 'category',
            'other_field' => 'children'
        ],
    ];

    public $has_many = [
        'children' => [
            'class'       => 'category',
            'other_field' => 'parent'
        ],
    ];
}
```

Fetching nested trees is as simple as chaining relationships:

```php
$root = (new Category())->where('slug', 'news')->get();
$root->children->get();
```

## Learn More

The comprehensive configuration options and patterns live in the [Advanced Usage guide](/guide/advanced/usage#advanced-relationship-patterns). You will also find:

- Detailed tables of relationship keys and options
- Examples of deep relationship queries
- Tips for debugging complex wiring

## See Also

- [Relationship Types](/guide/relationships/types) — Recap of supported association styles
- [Accessing Relations](/guide/relationships/accessing) — Working with loaded relationships in controllers
- [Advanced Usage](/guide/advanced/usage) — Full reference for advanced configuration
