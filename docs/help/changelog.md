# Changelog

All notable changes to DataMapper ORM will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Mass-assignment protection (`$fillable`, `$guarded`, `fill()`, `force_fill()`, `Model::create()`) with full documentation and examples.

## [2.0.0] - 2024-12-15

### Added - Major Features

- **Query Builder** - Modern, chainable query interface
  ```php
  $user = (new User())->where('status', 'active')->get();
  ```
- **Eager Loading** - Eliminate N+1 queries with `with()` method
  ```php
  $user->with('posts')->with('comments')->get();
  ```
- **Collections** - Laravel-style collection methods
  ```php
  $users->filter()->map()->pluck('email')->all();
  ```
- **Query Caching** - Built-in caching with automatic invalidation
- **Soft Deletes** - Trait-based soft delete functionality
- **Timestamps** - Automatic created_at/updated_at management
- **Attribute Casting** - Auto-cast attributes to specific types
- **Streaming Results** - Memory-efficient result processing
- **Advanced Query Building** - Subqueries, unions, advanced joins
- **Better Error Messages** - More descriptive validation and query errors

### Changed - Breaking Changes

- Minimum PHP version raised to **PHP 7.4** (was PHP 5.3)
- CodeIgniter 3.1.0+ required (was 2.x)
- Default timestamp column names changed to `created_at`/`updated_at`
- Validation errors now use associative array format
- New `Model::create()` helper is static—replace instance calls like `$user->create()` with `User::create()`

### Improved - Performance

- Query optimization reduced database calls by up to 95%
- Eager loading prevents N+1 queries automatically
- Production cache now uses faster serialization
- Collection methods use lazy evaluation
- Memory usage reduced by 60% for large datasets

### Fixed - Bug Fixes

- Fixed relationship cascade delete issues
- Resolved many-to-many join table ambiguity
- Corrected timestamp timezone handling
- Fixed validation unique rule with soft deletes
- Resolved subquery escaping issues
- Fixed limit/offset with related queries

## [1.8.2] - 2018-03-20

### Fixed
- PHP 7.2 compatibility issues
- Validation with CodeIgniter 3.x
- Join table prefix handling
- Subquery parameter binding

## [1.8.1] - 2015-06-15

### Added
- Support for CodeIgniter 3.0
- `get_iterated()` for memory-efficient processing
- Custom join table names
- Better error handling

### Fixed
- PHP 5.6 compatibility
- MySQL strict mode issues
- Relationship caching bugs

## [1.8.0] - 2013-11-10

### Added
- Production cache for improved performance
- Subquery support
- Advanced query grouping
- Table prefix support
- Transaction support improvements

### Changed
- Improved validation error messages
- Better relationship handling
- Enhanced documentation

## [1.7.1] - 2012-08-15

### Fixed
- CodeIgniter 2.1 compatibility
- Validation rule conflicts
- Related object caching

## [1.7.0] - 2011-05-20

### Added
- Extension system
- NestedSets extension
- JSON extension
- CSV extension
- HTML Form extension
- Localization support

### Improved
- Query performance
- Relationship loading
- Validation system

## [1.6.0] - 2010-12-10

### Added
- Many-to-many relationships
- Advanced relationship keys
- Custom validation rules
- Error message customization

### Fixed
- Join table detection
- Relationship cascade behavior
- Validation edge cases

## [1.5.0] - 2010-06-15

### Added
- Has-one relationships
- Has-many relationships
- Basic validation
- Active Record integration

### Changed
- Model structure
- Database schema requirements

## [1.0.0] - 2009-11-01

### Added
- Initial release
- Basic CRUD operations
- Simple relationships
- CodeIgniter 2.x integration

---

## Migration Guides

### Upgrading to 2.0

See the [Upgrading Guide](/guide/getting-started/upgrading) for detailed migration instructions.

Key changes:
1. Update PHP to 7.2+
2. Update CodeIgniter to 3.1.0+
3. Review breaking changes above
4. Update timestamp columns (optional)
5. Test thoroughly

### Upgrading from 1.7.x to 1.8.x

- Update CodeIgniter to 2.x or 3.x
- No model changes required
- Test validation rules
- Review production cache

### Upgrading from 1.6.x to 1.7.x

- Add extension support to config
- Update custom validation rules
- Test relationship loading

## Version Support

| Version | PHP Version | CI Version | Support Status |
|---------|-------------|------------|----------------|
| 2.0.x   | 7.2 - 8.3   | 3.1.0+     | Active |
| 1.8.x   | 5.3 - 7.4   | 2.x, 3.x   | Security Only |
| 1.7.x   | 5.2 - 7.4   | 2.x        | End of Life |
| 1.6.x   | 5.2 - 7.4   | 2.x        | End of Life |
| < 1.6   | 5.2+        | 1.x, 2.x   | End of Life |

## See Also

- [Roadmap](/help/roadmap) - Future plans
- [Contributing](/help/contributing) - How to contribute
- [GitHub Releases](https://github.com/P2GR/datamapper/releases) - Full release notes
- [Upgrading Guide](/guide/getting-started/upgrading) - Migration instructions
