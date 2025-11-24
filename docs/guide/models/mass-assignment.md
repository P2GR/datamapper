# Mass Assignment

Safely populating models from request data is crucial for avoiding privilege escalation bugs. DataMapper 2.0 brings first-class mass-assignment controls inspired by Laravel so you can opt in the attributes you expect and block everything else.

## Define Fillable Attributes

Declare a whitelist of assignable columns via `$fillable` and call `fill()` whenever you need to hydrate the model from an array:

```php
class User extends DataMapper {
    var $fillable = array('name', 'email', 'password');
}

$input = $this->input->post();

$user = new User();
$user->fill($input)->save();
```

`fill()` returns the model instance, so you can keep chaining (`$user->fill($payload)->skip_validation()->save();`). Attributes not present in `$fillable` are silently ignored.

::: warning Default Guard
The primary key (`id`) is always guarded. Add it to `$fillable` (or remove it from `$guarded`) only when you explicitly want incoming data to overwrite the identifier.
:::

## Guard Sensitive Columns

Prefer whitelisting, but you can also blacklist with `$guarded`:

```php
class User extends DataMapper {
    var $guarded = array('id', 'is_admin');
}

$user = new User();
$user->fill(array(
    'name' => 'Jess',
    'is_admin' => 1, // Stripped automatically
));
```

Set `$guarded = array('*');` to block everything by default and selectively call `forceFill()` when you are certain the payload is safe.

## Force Fill Trusted Data

`forceFill()` skips both `$fillable` and `$guarded`. This is useful for seeders, factories, migrations, or other code paths where you fully control the input.

```php
$user = new User();
$user->guarded = array('*');
$user->forceFill(array(
    'name' => 'System',
    'is_admin' => TRUE,
))->save();
```

## Temporarily Disable Guarding

Use `DataMapper::unguarded()` to disable protection for a single callback. The previous state is restored automatically, even if an exception is thrown.

```php
DataMapper::unguarded(function () {
    $CI = get_instance();

    $audit = new AuditLog();
    $audit->fill($CI->input->post())->save();
});
```

You can also toggle the flag manually with `DataMapper::unguard(TRUE)` and `DataMapper::reguard()` when building console tooling.

## Creating Models Quickly

Static `create()` now mirrors Laravel’s helper: it fills the model, saves it, and returns the instance on success (or `FALSE` on failure).

```php
$CI = get_instance();

$post = Post::create(array(
    'title' => $CI->input->post('title'),
    'body'  => $CI->input->post('body'),
));

if ($post) {
    // Saved successfully
}
```

## Tips

- Define `$fillable` (preferred) or `$guarded` on every model that handles user input.
- Keep `$guarded = array('*')` on baseline models and opt in attributes with `fillable` for the least privilege stance.
- Reach for `forceFill()` sparingly and only when you can guarantee the data source.
- Pair `fill()` with validation as usual—mass assignment does not bypass validation rules.

## See Also

- [Model Fields and Properties](fields) – broader overview of working with attributes.
- [From Array](from-array) – legacy helper that still works when you install the Array extension.
- [Save](save) – persistence workflow and validation details.
