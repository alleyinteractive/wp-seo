# Stored meta keys use the `alley_seo_` prefix

Four naming schemes for stored data are in the codebase at once: the original `_meta_title` / `_meta_description` / `_meta_canonical_url` / `_meta_robots_*`; `search_engine_title` and `search_engine_description`, added later for fields the block editor could reach over REST; and `wp_seo_open_graph_*` on the first migrated Feature. The first two store the same thing twice, with the newer read in preference to the older.

Nothing can be migrated until one wins, because the Features still to move — titles, descriptions, canonical URLs, robots meta — are exactly the ones holding the contested keys.

Decision: every meta key this plugin stores begins with `alley_seo_`, per [alleyinteractive/wp-seo#154](https://github.com/alleyinteractive/wp-seo/issues/154). The prefix is chosen to be distinctive enough that no other plugin is likely to claim it, so that two plugins managing the same concept do not write to the same key.

This applies to stored data. The plugin's hooks keep the `wp_seo_` prefix they already use — `wp_seo_enable_feature`, `wp_seo_enable_{handle}`, `wp_seo_register_features`, `wp_seo_formatting_tags` — because that is a published API a site writes filters against, and the collision this decision guards against is in storage. Worth knowing that the overlap with Yoast runs the other way: Yoast stores its post meta under `_yoast_wpseo_` and does not touch `wp_seo_`, while it does define a `wp_seo_get_bc_ancestors` filter and a `wp_seo_manage` capability. So the two prefixes are each avoiding a collision on the surface where one exists.

## Consequence

Open Graph is already migrated and stores `wp_seo_open_graph_*`. Those keys are wrong under this decision and must be renamed, along with the `search_engine_*` pair in `config/post-meta.json` and the sidebar component reading them. Open Graph shipped before this was settled; it is not an exception to it.

## The old keys are dropped, not migrated

The question this ADR originally left open — whether a migrated Feature reads the legacy keys, migrates them on upgrade, or writes both — is answered by a fact about the world rather than by a preference: the plugin has no install base to protect. No site is running it, so no stored data is at risk, and a breaking key change costs nothing.

A migrated Feature therefore reads and writes exactly one key per concept. Nothing reads `_meta_*`, nothing is backfilled, and the plugin needs no upgrade routine — worth stating plainly, because it has never had one and would have had to grow one first.

This is worth more than permission to rename. The reason `_meta_title` and `search_engine_title` both exist is that the second was added when the first could not safely be dropped. That constraint is gone, so the duplication collapses instead of being carried forward:

| Concept | Keys today | Key after migration | How it gets there |
| --- | --- | --- | --- |
| Title | `_meta_title`, `search_engine_title` | `alley_seo_search_engine_title` | replaced when `titles` migrates |
| Description | `_meta_description`, `search_engine_description` | `alley_seo_search_engine_description` | replaced when `descriptions` migrates |
| Canonical | `_meta_canonical_url` | `alley_seo_canonical_url` | replaced when `canonical_urls` migrates |
| Robots | `_meta_robots_{directive}` | `alley_seo_robots_{directive}` | replaced when `robots_meta` migrates |
| Open Graph | `wp_seo_open_graph_*` | `alley_seo_open_graph_*` | renamed in place |
| Twitter Card | `wp_seo_twitter_card_*` | `alley_seo_twitter_card_*` | renamed in place |

Only the last two rows are renames. Open Graph and Twitter Card are already Features and already own their keys, so nothing has to happen first. Every other row is a replacement: the Feature that takes ownership of the concept registers the new key and stops reading the old one, in the same change that moves its rendering out of `php/`. There is no intermediate state in which a legacy key wears a new name.

That distinction is worth stating because the alternative reads as reasonable and has already been written once. [alleyinteractive/wp-seo#171](https://github.com/alleyinteractive/wp-seo/pull/171) renamed all four legacy keys in place and was approved; it produced `_alley_seo_meta_title`, which keeps the leading underscore, stays invisible to REST, and would have to be renamed again when `titles` migrated. Do not open a standalone rename PR for the first four rows.

Two further things follow from the shape of the table rather than from the prefix.

No new key carries a leading underscore. Underscore-prefixed meta is hidden from REST, which is the entire subject of [alleyinteractive/wp-seo#188](https://github.com/alleyinteractive/wp-seo/issues/188). Registering those keys for REST is therefore not work to do but work that stops existing: #188 closes as moot once the four Features migrate.

Titles and descriptions keep a `search_engine_` qualifier rather than becoming bare `alley_seo_title` and `alley_seo_description`. A post has two titles — the one search engines see and the one Open Graph declares — so an unqualified key would name neither, and the qualifier reads the way `open_graph_` does in its counterpart. This is the one row chosen on taste rather than forced by the ruling, and it is cheap to revisit while nothing stores it.
