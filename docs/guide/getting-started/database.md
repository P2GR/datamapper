# Database Tables

[ in mind. In short, that means every table is aware only of itself, with fields relevant only to itself, as well as optional fields describing *$has_one* relationships. If a table has a **many** relationship with another table, it is represented by a special joining table. In either case, the same two objects can only [have one relationship between them](/help/troubleshooting#Relationships.NtoM).

(This is different from original DM, because it doesn't require a dedicated table for every relationship join.)

Lets take a look at the below example.

### countries

### countries_users

### users

Here we have 3 tables. Tables **countries** and **users** are normal tables. Table **countries_users** is the joining table that stores the relations between the records of countries and users.

The joining table shows that country ID 14 (Australia) has a relationship with user ID 7 (Foo). Country ID 12 (Armenia) has a relationship with user ID 8 (Baz).

## Table Naming Rules

[[ORM](/reference/glossary#ORM) methods.

- [Every** table must have a primary numeric key named **id** that by default is automatically generated. You can [override](/guide/models/save#saving-new-objects-with-an-existing-id) this behaviour.
- [**User**, the table would be named **users**. For **Country**, it would be **countries**. ([For odd pluralizations](/help/troubleshooting#General.Plural.Unusual), you may need to hard code the *$table* or *$model* fields.)
- A joining table must exist between each $has_many related normal tables. You can also use a joining table for any *$has_one* relationships.
- For in-table foreign keys, the column **must** allow NULLs, because DataMapper saves the object first, and relationships later.
- Joining tables must be named with both of the table names it is joining, in *alphabetical order*, separated by an underscore (_). For example, the joining table for **users** and **countries** is **countries_users**.
- Joining tables must have a specially name id field for each of the tables it is joining, named as the singular of the table name, followed by an underscore (_) and the word **id**. For example, the joining id field name for table **users** would be **user_id**. The joining id field name for table **countries** would be **country_id**. This same column name could be used for in-table foreign keys.

[[Advanced Relationship Patterns](/guide/advanced/usage#advanced-relationship-patterns).

### In-Table Foreign Keys

The way DataMapper originally required all relationships to have dedicated join tables. Datamapper ORM is a little more flexible and allows in-table foreign keys as well.

For this example, let's look at the same data, but when there is only one country for each user.

### countries

### users

Notice we've removed the joining table, and added the column **country_id** directly to the table **users**. Now the relationships are preserved, but we have less clutter in the database, and slightly faster queries as well.

[[DataMapper models](/guide/models/).