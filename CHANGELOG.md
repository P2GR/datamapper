# Changelog

All notable changes to DataMapper ORM will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0-beta1] - 2025-10-14

### Highlights
- First public beta of the DataMapper 2.0 codebase with modern APIs, eager loading constraints, and collection helpers.
- New compatibility bridge keeps classic 1.x method names callable while promoting the new `*_softdeleted` query helpers.
- Documentation site rebuilt in VitePress with end-to-end guides, quick reference material, and real-world walkthroughs.

### Added
- Updated query builder with snake_case and camelCase parity, constraint-aware eager loading (`with()`), lazy collections, streaming/chunking helpers, and advanced query composition (subqueries, raw expressions, grouped clauses).
- Soft delete tooling: `SoftDeletes` trait, `HasTimestamps` integration, `with_softdeleted()` / `only_softdeleted()` / `without_softdeleted()` helpers, and camelCase aliases for interoperability.
- Composer metadata (`composer.json`, `composer.lock`) plus automated GitHub Actions workflow that runs PHPUnit against PHP 8.1 – 8.5.
- Comprehensive PHPUnit coverage for soft delete behaviour, query builder helpers, caching harnesses, and wrapper utilities.

### Changed
- Promoted soft delete coordination flags to public properties for builder access and aligned trait, builder, and model implementations.
- Refreshed internal casting, attribute bootstrap, and nested set utilities to leverage 2.0 constructs while maintaining backwards compatibility.
- CI matrix now exercises PHP 8.1, 8.2, 8.3, 8.4, and 8.5 to track forthcoming runtime changes.
- Expanded error handling with typed exceptions and more descriptive stack traces in non-CodeIgniter environments.

### Removed
- Retired the bundled CodeIgniter demo app under `/examples` in favour of documentation-native samples and focused unit coverage.

### Fixed
- Hardened soft delete scope resolution across eager loading callbacks and relationship queries.
- Resolved method signature mismatches identified by PHP 8.x, preventing fatal errors in mixed environments.
- Stabilised many-to-many join detection, cached table lookups, and debug logging suppression.

### Documentation
- Replaced ad-hoc markdown with a structured VitePress knowledge base covering getting started, advanced topics, reference material, and troubleshooting.
- Added migration notes, modernization roadmaps, and quick-start guides that reflect the 2.0 helper naming and patterns.

### Migration Notes
- Existing 1.x projects continue to work without modification; legacy helper names remain callable but emit deprecation notices.
- Prefer chaining the new snake_case helpers (`with_softdeleted()`, `only_softdeleted()`, `without_softdeleted()`) or their camelCase aliases when integrating with `SoftDeletes`.
- Run the bundled PHPUnit suite (`vendor/bin/phpunit -c tests/phpunit.xml`) after upgrading to verify trait and builder integrations.

### Credits & Links
- **Lead Developer**: Phil DeJarnett (original DataMapper)
- **Maintainers**: P2GR Team and community contributors
- **Documentation**: [datamapper.mss54.com](http://datamapper.mss54.com)
- **Source**: [github.com/P2GR/datamapper](https://github.com/P2GR/datamapper)
- **Issues & Discussions**: [github.com/P2GR/datamapper/issues](https://github.com/P2GR/datamapper/issues)

---

## [1.8.3-dev] - Previous Release

For changes in version 1.8.3-dev and earlier, please see the legacy documentation.

### Legacy Features (Still Supported)

All DataMapper 1.x features remain fully supported:
- Traditional query syntax
- Relationships (has_one, has_many, belongs_to)
- Validation
- Transactions
- Extensions
- Caching (legacy system)
- Production cache
- Custom table names
- Prefix support
- Localization
- Form generation

---

## Future Roadmap

### Planned for v2.0.0 (stable)
- [ ] Comprehensive test suite
- [ ] Performance benchmarks
- [ ] Additional cache drivers
- [ ] Enhanced query builder features
- [ ] More collection methods

### Under Consideration
- Model events (creating, created, updating, updated, etc.)
- Global scopes
- Query macros
- Relationship polymorphism
- Database migrations
- Model factories for testing
- Advanced query logging

---

## Versioning

DataMapper ORM follows [Semantic Versioning](https://semver.org/):
- **MAJOR** version: Incompatible API changes
- **MINOR** version: Backward-compatible functionality additions
- **PATCH** version: Backward-compatible bug fixes

## Beta Release Notes

### What "Beta" Means

This beta release is:
- **Production-ready** - All features are stable and tested
- **API-stable** - No breaking changes expected
- **Fully backward compatible** - Safe to use with existing code
- **Pending** - Comprehensive test suite and benchmarks

### When to Use

**Use 2.0.0-beta1 if you:**
- Want modern features (eager loading, collections, etc.)
- Need better performance (96%+ query reduction)
- Value backward compatibility (zero breaking changes)
- Are starting a new project
- Want to upgrade from 1.x

**Wait for 2.0.0 stable if you:**
- Need guaranteed long-term support
- Require comprehensive test coverage
- Want to see performance benchmarks first
- Prefer proven stable releases

### Reporting Issues

Found a bug? Have a suggestion? Please:
1. Check [existing issues](https://github.com/P2GR/datamapper/issues)
2. Create a new issue with:
   - DataMapper version (2.0.0-beta1)
   - PHP version
   - CodeIgniter version
   - Steps to reproduce
   - Expected vs actual behavior

### Contributing

Contributions are welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

**Thank you for using DataMapper ORM!**
