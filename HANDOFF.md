# WP SEO v2 feature system — handoff

Working notes for whoever picks this up next. Not plugin documentation; delete or gitignore it when the migration is done.

## Where things stand

Issue [#131](https://github.com/alleyinteractive/wp-seo/issues/131) is being delivered in layers on the `v2.0` branch, as a GitHub stack.

| Layer | Branch | State |
| --- | --- | --- |
| 1. Feature wrapper + Registry | `feature/issue-131/feature-wrapper-registry` | [PR #196](https://github.com/alleyinteractive/wp-seo/pull/196), open |
| 2. Read-only features screen | `feature/issue-131/readonly-features-page` | [PR #197](https://github.com/alleyinteractive/wp-seo/pull/197), open |
| 3. Per-feature migration | not started | taxonomy settled; see the meta-key question below before starting |

`gh stack view` shows the stack. Both PRs are green and awaiting human review.

**One feature has been migrated: Open Graph.** Everything else — roughly 3,700 lines across ten files in `php/` — still loads unconditionally through a `require_once` block at the bottom of `wp-seo.php`.

## Read these first

The decisions are in `docs/adr/`, and they are the specification. `CONTEXT.md` is the glossary; use its vocabulary.

The two that most change how you'd otherwise write code:

- **ADR 0006** — feature composition is deferred once, at the root (`main()` is hooked to `after_setup_theme`), and gating is synchronous. An earlier design deferred inside each feature and was abandoned; ADR 0005 is kept as superseded because the trap it describes is easy to reinvent.
- **ADR 0010** — the feature taxonomy: features divide by *output type*, and each owns both its front-end rendering and its editor field.

`CONTEXT.md`'s definition of **Feature** is the test for whether something new should be one: *is this something a site could choose to do without and would have an opinion about?* Scaffolding that exists so features can do their job is not a feature and loads unconditionally.

## The API

```php
Feature::top_level( 'open_graph', __( 'Open Graph', 'wp-seo' ), new Features\Open_Graph() );
Feature::nested( 'handle', __( 'Label', 'wp-seo' ), $origin );
Feature::group( 'handle', __( 'Label', 'wp-seo' ), ...$children );
```

Handles are flat, lowercase, underscore-separated, must begin with a letter, and `feature` is reserved. A handle is a contract: once a site writes `add_filter( 'wp_seo_enable_titles', … )`, renaming it breaks that site. Labels are free-form display text.

Features are composed in `src/main.php`, which also fires `wp_seo_register_features` so a site can contribute its own without editing the plugin.

## What's left to migrate

Five features remain, per ADR 0010. Each gets its own branch, and **wrapping a feature is part of migrating it** — a migrated feature that still boots unconditionally is not migrated.

| Feature | Handle | Where it lives now |
| --- | --- | --- |
| Title Tags | `titles` | `php/class-wp-seo.php` — `pre_get_document_title`, `wp_title` |
| Meta Descriptions | `descriptions` | `php/class-wp-seo.php` — `wp_head` |
| Canonical URLs | `canonical_urls` | `php/class-wp-seo.php` — `wp_head` |
| Robots Meta | `robots_meta` | `php/class-wp-seo.php` — `wp_robots` |
| Arbitrary Meta Tags | `arbitrary_tags` | `php/class-wp-seo.php` — `wp_head` |

Start with `titles`. It is the most entangled, so it will establish the pattern the other four follow — in particular how a feature contributes a field to the shared editor meta box, which stays a single unconditionally-registered box rather than one box per feature.

### Read this before migrating anything: the meta keys

Every stored meta key begins with `alley_seo_` (ADR 0011, ruling in [#154](https://github.com/alleyinteractive/wp-seo/issues/154)). Hooks keep `wp_seo_` — that split is deliberate and the ADR explains it.

**Open Graph shipped before this was decided and is wrong.** It stores `wp_seo_open_graph_*` and needs renaming, as do the `search_engine_*` fields. Everything to change:

- `src/features/class-open-graph.php` — three keys, in `register_meta_helper()` calls and `get_post_meta()` reads
- `components/open-graph/index.tsx` — three `usePostMetaValue()` calls
- `components/search-engine/index.tsx` — two `usePostMetaValue()` calls
- `config/post-meta.json` — `search_engine_title`, `search_engine_description`
- `tests/Feature/OpenGraphTest.php`, `tests/Feature/OpenGraphRenderTest.php` — eleven references

Renaming a key that has been shipped is a data question, not a find-and-replace: any site already storing under the old key needs its data moved or read.

**What is still not settled is what happens to the v1 data**, and it blocks the first migration rather than the last. Each of the five remaining features owns a key that already exists under a different name, and two of them exist twice:

| Concept | Legacy key | Newer key |
| --- | --- | --- |
| Title | `_meta_title` | `search_engine_title` |
| Description | `_meta_description` | `search_engine_description` |
| Canonical | `_meta_canonical_url` | — |
| Robots | `_meta_robots_{directive}` | — |

The `search_engine_*` pair came from [#153](https://github.com/alleyinteractive/wp-seo/issues/153), which shipped: those are read in preference to the underscore-prefixed originals, which are invisible to REST and are why [#188](https://github.com/alleyinteractive/wp-seo/issues/188) exists. So one concept is stored under two keys today, and migrating adds a third unless it is handled deliberately.

This is not the same kind of decision as making Open Graph opt-in. That was free because nobody runs v2. **Sites running v1 hold real data under `_meta_*`**, and v2 is meant to replace v1 on those sites, so a migrated feature must either keep reading the legacy keys, migrate them on upgrade, or write both. Decide that before writing `titles`, not during.

Open Graph avoided all of this only because it was new code with no key to inherit.

**Stack these branches rather than running them in parallel.** Every migration deletes a line from the same `require_once` block in `wp-seo.php`, so parallel branches conflict there.

Not features, and not to be wrapped: the editor meta box container, the formatting-tag system (`#site_name#` and friends), and `php/default-filters.php`, which dissolves — its container wiring is infrastructure and its per-field bits move to the features that own them.

## Open issues

- [#154](https://github.com/alleyinteractive/wp-seo/issues/154) — the `alley_seo_` ruling, recorded as ADR 0011. Still open because the renames it requires are not done; it is the tracking issue for them
- [#188](https://github.com/alleyinteractive/wp-seo/issues/188) — register the legacy `_meta_*` keys for REST. Overlaps the migration: if those keys are being replaced, registering them may be redundant. Do not work it before the legacy-key question above is answered
- [#194](https://github.com/alleyinteractive/wp-seo/issues/194) — move `Settings → SEO` under the new top-level WP SEO menu
- [#199](https://github.com/alleyinteractive/wp-seo/issues/199) — separate the Page Type axis in the admin
- [#200](https://github.com/alleyinteractive/wp-seo/issues/200) — recommended feature set, v2.1
- Display categories on the features screen: decided in principle (ADR 0009), applied when the list is long enough to need them. Safe to defer — a category has no filter contract, unlike a handle.

## Practical notes

**Tests only run inside the Vagrant VM.** The host has no MySQL, so Mantle cannot install its test WordPress and fails on `mysqli_real_connect`. Setting `MANTLE_USE_SQLITE=true` as an environment variable does not help — `phpunit.xml` overrides it.

```
cd ~/broadway && vagrant ssh -c "cd /var/www/wp-test-bug/wp-content/plugins/wp-seo && vendor/bin/phpunit"
```

`vendor/bin/phpcs .` and `composer phpstan` run on the host. Use the composer script for phpstan, not the binary directly — it needs the memory limit the script sets. Note `.phpcs.xml` excludes `tests/`, so test files are never sniffed. phpstan runs at level max.

Current baseline: **213 tests, 0 failures.** The 1 deprecation and 2 notices are pre-existing.

**Commits are made by the maintainer, not by agents.** Write the code, report what changed, and leave `git commit` alone.

## Traps already found the hard way

- **Adding a callback to a hook that is currently running, at the same priority, never runs it.** `WP_Hook::apply_filters()` iterates a copy of the priority bucket. This silently broke nested features under the original design and is why composition is now deferred once rather than per feature.
- **A filter returning a non-string into a typed `string` return kills all of wp-admin**, `plugins.php` included, because the `TypeError` escapes `admin_menu`. Coerce filter results before returning them from a typed method.
- **An all-digit handle becomes an integer array key**, which is why handles must begin with a letter.
- **`add_menu_page()` registers its page unconditionally**, outside its own capability check. Denial for an unprivileged user comes from `$_wp_menu_nopriv`, populated in `wp-admin/includes/menu.php` for any top-level menu the user cannot access — independent of submenus.
- **The feature scaffolder in `.scaffolder/` was broken** in two ways: it wrote tests to `tests/Features/`, which `phpunit.xml` does not register, and imported a `WP_SEO\` namespace that does not exist. Fixed, but check generated output.
