# DataMapper 2.1 Release Audit

## Executive Summary

DataMapper has a substantial modern ORM surface on top of its CodeIgniter 3 and DataMapper 1.x base: query-builder helpers, mass assignment, casts, dirty tracking, model events, soft deletes, timestamps, eager loading, caching, and collection/streaming helpers. This is a credible foundation, but it is not yet safe to present as a uniformly reliable Eloquent-style ORM.

The P0 blockers and the original P1 correctness items are fixed in this working tree. This follow-up hardening pass also closes custom-key gaps in constructors, subqueries, counts, collections, and legacy extensions; prevents relationship cleanup failures from writing the base row; validates keyset iteration keys; honors custom timestamp columns; and propagates Redis and file-cache flush failures. The remaining work is broader database-backed relationship coverage and staged ORM improvements.

This audit compares the project against the useful parts of Laravel 13 Eloquent and Query Builder, while preserving the project's actual goal: a backwards-compatible ORM for CodeIgniter 3, not a Laravel clone.

## Scope and Method

- Reviewed the DataMapper model, `DMZ_QueryBuilder`, lazy collection, timestamp and soft-delete traits, configuration, public documentation, Copilot instructions, and the relevant PHPUnit tests.
- Compared capabilities and design priorities with the official [Laravel 13 Eloquent](https://laravel.com/docs/13.x/eloquent), [relationships](https://laravel.com/docs/13.x/eloquent-relationships), and [query builder](https://laravel.com/docs/13.x/queries) documentation.
- Ran `vendor\bin\phpunit --configuration tests\phpunit.xml`: **167 tests, 431 assertions, passing** on PHP 8.4.3. PHP emitted a non-fatal Imagick/ImageMagick version warning.
- Applied P0 and P1 remediation in persistence lifecycle handling, eager loading, relation caching, lazy iteration, timestamps, CI, and current documentation.

The suite now executes the production eager-loading implementation for custom parent/related keys, custom aliases, and per-parent limits. A real CI3 database matrix remains necessary for complete SQL-level confidence across has-one, has-many, and many-to-many variants.

## Current Position

| Area | Current assessment |
| --- | --- |
| Legacy compatibility | `save()`, eager hydration, `touch()`, chunking, and lazy keyset iteration honor configured primary keys in the repaired paths. |
| Basic query API | Solid modern baseline: fluent filters, ordering, grouping, aggregates, conditional clauses, find/first helpers, relation existence filters, and JSON containment helpers. |
| Model ergonomics | Good initial set: `$fillable`, `$guarded`, `fill`, `create`, casts, accessors/mutators, serialization controls, dirty tracking, replication, counters, events, timestamps, and soft deletes. |
| Relationships | Eager hydration now consumes normalized DataMapper aliases and configured keys; per-parent limits are applied after batched loading. |
| Streaming | `lazy()` clones mutable CI query state and disables cache per chunk; `lazy_by_id()` provides keyset progression for mutable datasets. |
| Caching | `cache_relations()` stores hydrated graphs under relation-specific keys and graph entries are invalidated on model writes. Constrained eager loads deliberately bypass graph caching. |
| Release hygiene | Runtime, package metadata, changelogs, and shipped source annotations are prepared for `2.1.1`; remaining older markers are limited to legacy instructions and historical documentation. |

## Completed P0 Remediation

### PHP 8.0 support contract

`application/datamapper/attributecasting.php` uses PHP 8-only `mixed` types and `match` expressions. The supported range is now PHP 8.0 through 8.5, declared in `composer.json`, enforced by the GitHub Actions matrix, and reflected in current requirements, upgrade, FAQ, troubleshooting, and README documentation.

Historical changelog entries retain their original PHP 7.4 release note. They are not a statement of the current support contract.

### Custom primary-key persistence in `save()`

`DataMapper::save()` now resolves `$this->primary_key` once and uses it for new-record detection, update predicates, and generated insert-ID assignment. `tests/DataMapperPrimaryKeySaveTest.php` proves update and insert behavior with `primary_key = 'user_id'`.

Relationship hydration and the audited model utilities now resolve configured primary keys in the repaired paths. Remaining coverage is database-backed validation across every relationship storage type and driver.

## Completed Correctness Priorities (P1)

| Finding | Resolution | Regression evidence |
| --- | --- | --- |
| Eager metadata/key assumptions | Production eager hydration now consumes normalized relationship aliases and both models' configured primary keys. | Custom-key/custom-alias has-many production-loader test. |
| Global eager limits | Batched results are ordered/filtered in SQL, then limit and offset are applied independently to each parent group. | Two-parent limit regression. |
| Failed-write success side effects | Stored-state refresh, success events, and normal cache invalidation are gated on final success; automatic transactions explicitly roll back reported ORM failures. Non-transactional partial commits refresh state and invalidate cache without firing success events. | Failed insert/update, relationship failure, and rollback tests. |
| Shallow lazy clone | Every chunk uses `get_clone(TRUE)->no_cache()`. | Multi-chunk clone/cache regression. |
| Mutable offset iteration | Added `lazy_by_id()`, defaulting to the configured primary key. | Custom-key multi-chunk keyset regression. |
| Base-only relation cache | Relation caching is finalized after eager hydration, uses graph-specific keys, restores null/collection/single relations, and invalidates graph keys on writes. Constrained eager loads bypass graph caching because callbacks are not safely serializable. | Relation graph round-trip test. |
| Incorrect `touch()` success | `touch()` uses the configured key, propagates update failure, restores failed in-memory state, refreshes stored state, and invalidates cache on success. | Success/failure timestamp tests. |
| Eager-loader test override | Added a separate test that executes production `_load_eager_relations()`. | `ProductionEagerLoadingTest`. |

### Residual P1 Risk

- Add CI3 database-backed fixtures for parent-FK has-one, inverse has-one, many-to-many, empty/nested relations, soft-delete overrides, and relation-name collisions.
- Exercise relation graph cache hits through a real configured query builder, including nested relations and invalidation after child writes.
- Run the existing PHP 8.0-8.5 CI matrix before release; local verification used PHP 8.4.3.

## Documentation and Release Drift

| Finding | Evidence | Action |
| --- | --- | --- |
| Cache configuration conflict | The Copilot guide previously documented unsupported top-level cache flags. | Updated the guide to use `cache_driver` and driver-specific `cache_config`. |
| Version drift | Runtime and shipped source annotations are 2.1.1; legacy Copilot instructions and historical documentation intentionally retain older release markers. | Establish one release version source and validate public version strings in CI. |
| Unsupported performance language | Public docs used unqualified query and memory-reduction percentages. | Replaced the percentages with contextual behavior descriptions; benchmark claims still require reproducible scenarios before publication. |
| Eager-limit semantics | Eager-loading limits now apply per parent in PHP after the batched related query. | Add database-backed ordering/limit fixtures for every relationship storage type. |
| Composer package metadata is incomplete | `composer.json` requires PHP 8.x but has no CodeIgniter requirement and identifies itself as a testing harness project. | Publish a proper library package contract, or document that Composer is development-only. |

## Modernization Roadmap

### Phase 0: Stabilize the existing contract

1. **Complete:** consume normalized relationship metadata and configured keys in eager hydration.
2. **Complete:** repair eager loading, per-parent limits, and relation graph caching semantics.
3. **Complete:** correct write result handling for `save()`, `touch()`, events, transactions, cache invalidation, and stored-state refresh.
4. **Complete:** repair `lazy()` cloning and add `lazy_by_id()` keyset iteration.
5. **Remaining:** add database-backed relationship integration tests before extending the relation API.

Exit criterion: every advertised v2.1 feature has at least one production-path test. Relationship tests must prove correctness with a custom primary key and custom relationship aliases.

### Phase 1: Relationship APIs with the highest payoff

These features solve common application needs and build directly on the Phase 0 metadata work.

| Capability | Recommended API direction | Why it matters |
| --- | --- | --- |
| Deferred eager loading | `load()`, `load_missing()`, collection-level loading | Lets callers decide after retrieval without N+1 loops. |
| Default eager relations | Model `$with`, plus per-query `without()` / `with_only()` | Makes commonly used graphs explicit and predictable. |
| Eager aggregate attributes | `with_count`, `with_sum`, `with_avg`, `with_min`, `with_max`, `with_exists` | Avoids loading entire child collections just to display counts and summaries. |
| Shared filtering/loading | `with_where_has()` built on the same relation constraint | Prevents duplicated callbacks and mismatched list/detail results. |
| Relationship mutation | `associate`, `dissociate`, `attach`, `detach`, `sync`, `toggle`, and pivot attribute updates | Provides safe, expressive relationship writes and reduces manual join-table SQL. |
| Relation defaults | A null-object/default related model for supported single relations | Removes repetitive null checks in views and API mapping. |

Deliver these only after all relationship types use the same metadata resolver. APIs should use snake_case as the canonical project style, with documented camelCase aliases only where compatibility genuinely requires them.

### Phase 2: Query and persistence improvements

| Capability | Priority | Notes |
| --- | --- | --- |
| Atomic `upsert()` | High | Essential for imports, synchronization, and race-safe create/update flows. Require unique-index documentation and driver-specific tests. |
| Offset and keyset pagination | High | Add a defined paginator value object; add cursor/keyset pagination for stable large result sets. |
| Pessimistic locks | Medium | Add `lock_for_update()` and `shared_lock()` with explicit transaction guidance and database capability checks. |
| Broader JSON predicates | Medium | Add key-existence and length predicates only for database drivers that support them. Existing JSON containment is a useful base. |
| Full-text predicates | Medium | Add portable API with driver-specific SQL generation and a clear unsupported-driver error. |
| Query inspection | Medium | Evolve existing debug/benchmark support into structured SQL, bindings, elapsed time, and an explain-plan hook that never interpolates untrusted bindings. |
| Reusable query objects/macros | Low | Favor explicit, testable scope objects over global mutable macros. |

Do not prioritize Laravel-specific vector-search methods or broad convenience aliases until the above database-neutral features are robust for CI3-supported drivers.

### Phase 3: Model safety and developer experience

| Capability | Recommended outcome |
| --- | --- |
| Strictness modes | Development-mode exceptions for silently discarded mass-assignment attributes, unknown attributes, and accidental lazy loading. Production behavior should remain configurable for compatibility. |
| Identifier strategies | Explicit non-incrementing string keys and optional UUIDv7/ULID generation. This depends on Phase 0 primary-key correctness. |
| Global scopes | Named, removable model scopes with reliable grouping around `OR` clauses. Soft delete should be the reference implementation. |
| Observers and quiet operations | Observer registration plus `save_quietly`, `delete_quietly`, `restore_quietly`, and transaction-after-commit event behavior. |
| Attribute defaults and richer casts | Default attributes; immutable datetime and custom cast objects only if they preserve serialization and legacy property behavior. |
| Model comparison and refresh consistency | Preserve loaded-relation semantics and add broader integration coverage for the configured-key paths now implemented by `is`, `is_not`, `fresh`, `refresh`, and `replicate`. |

## Test Strategy

### New Test Layers

1. **Fast unit tests:** metadata normalization, cache-key construction, relation key resolution, event ordering, and failure branches.
2. **Database integration tests:** real CI3 query builder plus schema fixtures for relationships, cache, timestamps, and query results.
3. **Driver matrix:** SQLite for fast feedback, then MySQL/MariaDB and PostgreSQL for features that differ by dialect such as JSON, locks, and upserts.
4. **Compatibility matrix:** test PHP 8.0 through 8.5, the supported runtime range.
5. **Documentation examples:** run selected snippets as tests so query configuration and examples cannot drift from the public API.

### Minimum Regression Set

- Custom primary-key behavior outside `save()`: delete, fresh/refresh, comparison, chunks, and relationship operations.
- Has-one, has-many, and many-to-many eager loads using the actual production loader.
- Nested eager loads and constrained eager loads, including a per-parent limit scenario.
- Eager-cache hit behavior: verify whether the relation graph is reused or explicitly reloaded.
- Failed insert/update and rolled-back transaction do not fire success events or invalidate cache.
- Failed `touch()` returns false and leaves in-memory timestamp state coherent.
- Filtered lazy iteration across multiple chunks never returns rows outside the original constraint.

## Recommended Delivery Order

1. Add failing integration tests for eager loading, events, lazy iteration, cache relations, and `touch()`.
2. Repair the existing implementation until those tests pass.
3. Release the completed P0 work and remaining stabilization work with clear migration notes.
4. Implement Phase 1 relationship APIs in small releases, one capability group at a time.
5. Add Phase 2 query APIs only with a documented database support matrix.

## Bottom Line

The project should not aim for superficial Eloquent parity. Its strongest next release is one that makes the existing v2 APIs trustworthy across legacy schemas, custom keys, real relationship graphs, and supported PHP versions. Once that base is in place, eager aggregates, deferred loading, pivot operations, upserts, keyset pagination, strictness controls, and scoped relationships will deliver more practical value than a large batch of aliases.
