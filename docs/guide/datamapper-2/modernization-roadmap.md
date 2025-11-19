# DataMapper 2.x Modernization Roadmap

This playbook captures the next set of quality-of-life upgrades we discussed so the ORM can feel as contemporary as Laravel/Eloquent while staying backward compatible. Each item includes the problem it solves, the suggested implementation strategy, and follow-up tasks.

> **Legend**  
> **Priority:** 🔴 High · 🟠 Medium · 🟢 Low  
> **Effort:** 🧩 Small (<1 day) · ⚙️ Medium (1–3 days) · 🏗️ Large (>3 days)

## 🔴 High-priority enhancements

### 1. Relation-aware filtering helpers (`whereHas`, `has`, `orWhereHas`) ✅
- **Status:** Implemented (snake_case + camelCase aliases powered by `include_related_count()` constraints). Docs updated in the Query Builder guide.
- **What/Why:** Let developers filter parent results through the query builder without dropping to legacy `where_related`. Aligns with Eloquent muscle memory and keeps relation names consistent.
- **Implementation notes:**
  - Add methods to `DMZ_QueryBuilder` that translate to relation metadata and accept optional callbacks.
  - Ensure nested relation strings (`building.client`) are supported.
  - Reuse `include_related_count` under the hood to keep queries efficient.
- **Testing:** New unit tests around the builder verifying generated SQL and interaction with eager loading. *(Pending addition)*
- **Docs:** Update query builder guide and eager loading documentation (see docs table below).

### 2. Nested eager constraint chaining improvements
- **What/Why:** Allow constraint arrays such as `with(['building' => ['client' => fn () => ...]])` and permit the callback to return another builder for deeper nesting.
- **Implementation notes:**
  - Extend `_apply_eager_constraints` to accept array structures and propagate constraints per level.
  - Make sure recursion respects existing `_optimize_eager_loads` logic.
- **Testing:** Cover multi-level eager loads with constraints + soft-delete scopes.
- **Docs:** Eager loading guide + query builder reference examples.

### 3. Relation-aware ordering helpers (`orderByRelation`, etc.)
- **What/Why:** Replace stringly typed column aliases (`building_clients.company_name`) with expressive helpers that understand relationships.
- **Implementation notes:**
  - Add helpers to builder that resolve join tables/aliases using the relationship metadata already available.
  - Support ascending/descending and multiple fields.
- **Testing:** Ensure generated SQL matches manual join strategy; cover many-to-many edge cases.
- **Docs:** Query builder guide, streaming examples where cross-table ordering appears.

## 🟠 Medium-priority enhancements

### 4. Global scope registry
- **What/Why:** Package tenant/company filters or soft-delete overrides as reusable scopes (`with_softdeleted`, `forTenant`).
- **Implementation notes:**
  - Introduce optional trait + static registry in `DataMapper` to register/disable scopes per model.
  - Inject scopes before query execution; allow per-query opt-out (e.g., `$model->withoutGlobalScope('tenant')`).
- **Testing:** Add coverage for stacking scopes and opt-out behaviour.
- **Docs:** New section in the query builder guide; mention in soft-delete/timestamps docs.

### 5. Batch operations (`insertBatch`, `updateBatch`)
- **What/Why:** Speed up imports/exports and reduce memory usage (already hinted in roadmap—pull forward).
- **Implementation notes:**
  - Wrap CodeIgniter’s batch methods in a new helper (`DMZ_BatchOperations`?) while respecting events/scopes.
  - Provide sanity checks for large payloads (chunking).
- **Testing:** Ensure transaction integration + soft delete/timestamps interplay.
- **Docs:** Streaming & chunking guide, plus new section in reference docs.

### 6. Query profiling hooks (`debug`, `toSql`, `dumpSql`)
- **What/Why:** Offer zero-effort instrumentation for spotting N+1s and slow queries.
- **Implementation notes:**
  - Add optional `->debug()` toggle that logs SQL + bindings; optionally integrate with CodeIgniter profiler.
  - Ensure production-safe (no accidental dumps unless explicitly enabled).
- **Testing:** Confirm logs are produced only when toggled.
- **Docs:** Performance section in the query builder guide; troubleshooting FAQ.

## 🟢 Low-priority / nice-to-haves

### 7. Builder macros / mixins
- **What/Why:** Allow projects to register custom builder methods once and reuse them everywhere.
- **Implementation notes:**
  - Provide macro registry on `DMZ_QueryBuilder`; allow closures bound to the builder instance.
- **Docs:** Add cookbook examples.

### 8. Deferred eager loading (`load`, `loadMissing`)
- **What/Why:** Fetch relationships conditionally after the initial query, mirroring Eloquent’s late eager loading.
- **Implementation notes:**
  - Add helpers on `DataMapper`/`DMZ_Collection` that leverage `_load_eager_relations` with the existing metadata.
- **Docs:** Eager loading guide, advanced patterns.

### 9. JSON column helpers (`whereJsonContains`, etc.)
- **What/Why:** Embrace modern SQL JSON functions for casting-heavy models.
- **Implementation notes:**
  - Provide minimal wrappers that generate provider-specific SQL; gate behaviour by database driver.
- **Docs:** Casting guide, query builder API reference.

---

## 📚 Documentation touchpoints

| Priority | File | Reason | Notes |
|----------|------|--------|-------|
| 🔴 | `docs/guide/datamapper-2/query-builder.md` | Add new builder APIs (`whereHas`, relation ordering, profiling, macros). | Update quick reference tables and examples. |
| 🔴 | `docs/guide/datamapper-2/eager-loading.md` | Document nested constraints, deferred loading, `loadMissing`. | Include before/after snippets. |
| 🟠 | `docs/guide/datamapper-2/streaming.md` | Mention batch insert/update helpers and when to pair with chunking. | Cross-link to batch section. |
| 🟠 | `docs/guide/datamapper-2/casting.md` | Highlight JSON helper usage and accessor interplay. | Add caution around DB support. |
| 🟠 | `docs/help/roadmap.md` | Reflect the roadmap pull-forward (batch ops, profiling). | Update timelines once features land. |
| 🟢 | `docs/reference/functions.md` (or equivalent) | Add API signatures for new helpers. | Ensure index/search hits the new methods. |
| 🟢 | `docs/help/changelog.md` | Record releases once shipped. | Follow existing release note format. |

> **Tip:** Update `docs/guide/datamapper-2/index.md` summary bullets after each feature lands so the landing page reflects the latest ergonomics.

---

## ✅ Next steps checklist

1. Pick one 🔴 item to implement end-to-end (code + tests + docs) before tackling medium-priority work.
2. After merging, update `docs/help/changelog.md` and `docs/help/roadmap.md` to keep public messaging aligned.
3. Schedule a short demo/readme snippet for each feature so teams learn the new APIs quickly.

This file should evolve as work completes—treat it as the single source of truth for modernization initiatives.
