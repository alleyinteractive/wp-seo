# WP SEO

WP SEO is an enterprise SEO plugin for large, performant WordPress sites. v2 rebuilds it as a set of independently togglable features rather than one always-on block of behavior.

## Language

**Feature**:
A single unit of plugin behavior, implemented as an `Alley\WP\Types\Feature` (from `wp-type-extensions`) with a `boot()` method. Off by default; a consumer must explicitly enable it.
_Avoid_: Module, component (when referring to this unit)

**Handle**:
The stable string identifier for a Feature, used to name its enablement filter (e.g. `wp_seo_enable_{handle}`) and to key it in the feature Registry. Chosen once and not renamed casually — filters and the future admin UI key off of it.

**Registry**:
The enumerable collection of every WP SEO Feature and its current enabled/locked state. Backs the v2.0 readonly admin display, the v2.1 toggle UI, and the generated recommended-features documentation.
