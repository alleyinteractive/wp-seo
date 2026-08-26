# Stored meta keys use the `alley_seo_` prefix

Four naming schemes for stored data are in the codebase at once: the original `_meta_title` / `_meta_description` / `_meta_canonical_url` / `_meta_robots_*`; `search_engine_title` and `search_engine_description`, added later for fields the block editor could reach over REST; and `wp_seo_open_graph_*` on the first migrated Feature. The first two store the same thing twice, with the newer read in preference to the older.

Nothing can be migrated until one wins, because the Features still to move — titles, descriptions, canonical URLs, robots meta — are exactly the ones holding the contested keys.

Decision: every meta key this plugin stores begins with `alley_seo_`, per [alleyinteractive/wp-seo#154](https://github.com/alleyinteractive/wp-seo/issues/154). The prefix is chosen to be distinctive enough that no other plugin is likely to claim it, so that two plugins managing the same concept do not write to the same key.

This applies to stored data. The plugin's hooks keep the `wp_seo_` prefix they already use — `wp_seo_enable_feature`, `wp_seo_enable_{handle}`, `wp_seo_register_features`, `wp_seo_formatting_tags` — because that is a published API a site writes filters against, and the collision this decision guards against is in storage. Worth knowing that the overlap with Yoast runs the other way: Yoast stores its post meta under `_yoast_wpseo_` and does not touch `wp_seo_`, while it does define a `wp_seo_get_bc_ancestors` filter and a `wp_seo_manage` capability. So the two prefixes are each avoiding a collision on the surface where one exists.

## Consequence

Open Graph is already migrated and stores `wp_seo_open_graph_*`. Those keys are wrong under this decision and must be renamed, along with the `search_engine_*` pair in `config/post-meta.json` and the sidebar component reading them. Open Graph shipped before this was settled; it is not an exception to it.

## Still open

This settles what new keys are called and not what happens to the old ones. Whether a migrated Feature reads the legacy keys, migrates them on upgrade, or writes both is undecided, and unlike the prefix it is not a matter of taste: sites running v1 hold real data under `_meta_*`, and v2 is meant to replace v1 on those sites.
