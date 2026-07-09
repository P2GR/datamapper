---
layout: home

hero:
  name: DataMapper ORM
  text: Modern Active Record for CodeIgniter
  tagline: A CodeIgniter 3 ORM with chainable queries, eager loading, casting, caching, and streaming helpers
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started/introduction
    - theme: alt
      text: Quick Start
      link: /guide/getting-started/quickstart
    - theme: alt
      text: View on GitHub
      link: https://github.com/P2GR/datamapper

features:
  - icon: 🔗
    title: Query Builder
    details: Write chainable model queries while keeping classic DataMapper behaviour intact.
    link: /guide/datamapper-2/query-builder
    linkText: Learn More
    
  - icon: ⚡
    title: Eager Loading
    details: Load related models explicitly, with support for constraints and nested relations.
    link: /guide/datamapper-2/eager-loading
    linkText: Optimize Queries
    
  - icon: 📦
    title: Collections
    details: Work with results using collection methods - map, filter, pluck, chunk, and more.
    link: /guide/datamapper-2/collections
    linkText: Explore Collections
    
  - icon: 💾
    title: Query Caching
    details: Cache selected read queries through file, Redis, or Memcached drivers.
    link: /guide/datamapper-2/caching
    linkText: Speed It Up
    
  - icon: 🗑️
    title: Soft Deletes
    details: Mark records as deleted and opt into deleted rows when you need them.
    link: /guide/datamapper-2/soft-deletes
    linkText: Learn More
    
  - icon: 🕐
    title: Timestamps
    details: Maintain created and updated timestamps through the timestamp trait.
    link: /guide/datamapper-2/timestamps
    linkText: Auto Timestamps
    
  - icon: 🔄
    title: Attribute Casting
    details: Automatically cast database values to proper types - integers, booleans, dates, JSON, and custom types.
    link: /guide/datamapper-2/casting
    linkText: Type Safety
    
  - icon: 📊
    title: Streaming Results
    details: Process large result sets with chunked callbacks, cursors, and lazy collections.
    link: /guide/datamapper-2/streaming
    linkText: Stream Data
---

<!-- Custom content below features -->

## Get Started in 3 Steps

<div class="vp-doc" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin: 2rem 0;">

<div style="border: 2px solid var(--vp-c-brand-1); border-radius: 12px; padding: 2rem; text-align: center;">
  <div style="font-size: 3rem; margin-bottom: 1rem;">1️⃣</div>
  <h3 style="margin-top: 0;">Install</h3>
  <p>Drop DataMapper into your CodeIgniter application in under 5 minutes.</p>
  <a href="/guide/getting-started/installation" style="font-weight: 600; color: var(--vp-c-brand-1);">Installation Guide →</a>
</div>

<div style="border: 2px solid var(--vp-c-brand-1); border-radius: 12px; padding: 2rem; text-align: center;">
  <div style="font-size: 3rem; margin-bottom: 1rem;">2️⃣</div>
  <h3 style="margin-top: 0;">Create Models</h3>
  <p>Build your first model and start querying your database with DataMapper's chainable helpers.</p>
  <a href="/guide/getting-started/quickstart" style="font-weight: 600; color: var(--vp-c-brand-1);">Quick Start →</a>
</div>

<div style="border: 2px solid var(--vp-c-brand-1); border-radius: 12px; padding: 2rem; text-align: center;">
  <div style="font-size: 3rem; margin-bottom: 1rem;">3️⃣</div>
  <h3 style="margin-top: 0;">Optimize</h3>
  <p>Add eager loading, caching, and streaming where they fit your workload.</p>
  <a href="/guide/datamapper-2/" style="font-weight: 600; color: var(--vp-c-brand-1);">Explore Features →</a>
</div>

</div>

## Why DataMapper 2.0?

::: info DataMapper 2.0
DataMapper 2.0 adds focused query, collection, casting, cache, and streaming helpers while preserving the classic CodeIgniter 3 workflow.
:::

### Before vs After

::: code-group

```php [Traditional (1.x)]
$user = new User();
$user->where('active', 1);
$user->where('age >', 18);
$user->order_by('created_at', 'DESC');
$user->limit(10);
$user->get();

// N+1 problem - multiple queries
foreach ($user as $u) {
    foreach ($u->post as $post) {  // Extra query each iteration!
        echo $post->title;
    }
}
```

```php [Query Builder (2.0)]
$users = (new User())
    ->where('active', 1)
    ->where('age >', 18)
    ->order_by('created_at', 'DESC')
    ->limit(10)
    ->with('post')
    ->get();

// Related posts are already loaded.
foreach ($users as $user) {
  foreach ($user->post as $post) {
        echo $post->title;
    }
}
```

:::

### Eager Loading in Practice

```php
// Without eager loading: one query for organizations, then one per organization.
$organizations = (new Organization())->get();
foreach ($organizations as $org) {
    foreach ($org->installation as $installation) {
        echo $installation->name;
    }
}

// With eager loading: one query for organizations and one for the related installations.
$organizations = (new Organization())
    ->with('installation')
    ->get();
    
foreach ($organizations as $org) {
    foreach ($org->installation as $installation) {
        echo $installation->name;
    }
}
```

::: tip Query count
For one relation, eager loading usually turns `1 + N` related queries into two queries. Nested or multiple relations add one query per loaded relation path.
:::

## Quick Example

```php
// E-commerce: Get premium customers with recent orders
$customers = (new Customer())
  ->cache(3600)
    ->with([
        'order' => function($q) {
            $q->where('created_at >', date('Y-m-d', strtotime('-30 days')))
              ->where('status', 'completed')
              ->order_by('created_at', 'DESC')
              ->limit(10);
        }
    ])
    ->where('status', 'premium')
    ->where('credits >', 100)
    ->where_not_null('email_verified_at')
    ->order_by('total_spent', 'DESC')
    ->get();

// Work with collections
$totalSpent = $customers->sum('total_spent');
$emails = $customers->pluck('email');
$topCustomer = $customers->first();
```

## Feature Comparison

| Feature | DataMapper 2.0 | Laravel | Doctrine ORM |
|---------|---------------|---------|--------------|
| **Modern Query Builder** | Yes | Yes | DQL |
| **Eager Loading** | Yes | Yes | Yes |
| **Query Caching** | Built-in | Manual | Complex |
| **Soft Deletes** | Trait | Trait | Manual |
| **Timestamps** | Trait | Trait | Callbacks |
| **Collections** | Yes | Yes | Arrays |
| **Streaming** | Yes | Chunk | No |
| **CodeIgniter 3** | Perfect | N/A | Complex |
| **Learning Curve** | Easy | Medium | Steep |
| **Setup Time** | 5 min | N/A | Hours |

## Authors & Maintainers

DataMapper ORM is developed and maintained by:

- **[P2GR](https://github.com/P2GR)** - Version 2.0 development and maintenance
- **[KayElliot](https://github.com/kayelliot)** - Version 2.0 development and maintenance

DataMapper ORM was originally created by **Phil DeJarnett** and **Simon Stenhouse**, with continued development by **Harro Verton** through version 1.8.3.

## Community & Support

<div class="vp-doc" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-top: 2rem;">

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
  <h3>Documentation</h3>
  <p>Comprehensive guides and API reference</p>
  <a href="/guide/getting-started/introduction">Read the Docs →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
  <h3>GitHub Discussions</h3>
  <p>Ask questions and share knowledge</p>
  <a href="https://github.com/P2GR/datamapper/discussions" target="_blank">Join Discussion →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
  <h3>Issue Tracker</h3>
  <p>Report bugs and request features</p>
  <a href="https://github.com/P2GR/datamapper/issues" target="_blank">Report Issue →</a>
</div>

<div style="border: 1px solid var(--vp-c-divider); border-radius: 8px; padding: 1.5rem;">
  <h3>Contributing</h3>
  <p>Help improve DataMapper</p>
  <a href="/help/contributing">Contribute →</a>
</div>

</div>

## Legacy Manual

> The legacy HTML manual that used to live under `/manual/` has been retired. All content now lives in this VitePress site under `/guide`, `/reference`, and `/examples`.

If you previously linked to URLs such as `/manual/pages/gettingstarted.html`, update them to the equivalent path on this site (for example `/guide/getting-started/introduction`). When hosting the docs, configure HTTP 301 redirects from the old `/manual/*` paths to their new locations so bookmarks and search indexes continue to work.

## Trusted By

DataMapper ORM powers applications across diverse industries:

- **Healthcare** - Patient management systems
- **E-commerce** - Online stores and marketplaces
- **Enterprise** - Business management platforms
- **Education** - Learning management systems
- **Fintech** - Financial tracking applications

---

<div style="text-align: center; margin-top: 3rem; padding: 2rem; background: var(--vp-c-bg-soft); border-radius: 8px;">
  <h2>Ready to Get Started?</h2>
  <p style="font-size: 1.1rem; color: var(--vp-c-text-2);">
    Install DataMapper in minutes and start building better CodeIgniter applications.
  </p>
  <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap;">
    <a href="/datamapper/guide/getting-started/installation" class="vp-button brand">Install Now</a>
    <a href="/datamapper/examples/" class="vp-button alt">View Examples</a>
  </div>
</div>

<style>
.vp-button {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.2s;
}

.vp-button.brand {
  background: var(--vp-button-brand-bg);
  color: var(--vp-button-brand-text);
}

.vp-button.brand:hover {
  background: var(--vp-button-brand-hover-bg);
}

.vp-button.alt {
  background: var(--vp-button-alt-bg);
  color: var(--vp-button-alt-text);
  border: 1px solid var(--vp-button-alt-border);
}

.vp-button.alt:hover {
  background: var(--vp-button-alt-hover-bg);
  border-color: var(--vp-button-alt-hover-border);
}
</style>
