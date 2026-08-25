# WP SEO

WP SEO is an enterprise SEO plugin for large, performant WordPress sites. v2 rebuilds it as a set of independently togglable features rather than one always-on block of behavior.

## Language

**Feature**:
A single unit of plugin behavior, implemented as an `Alley\WP\Types\Feature` (from `wp-type-extensions`) with a `boot()` method. Off by default; a consumer must explicitly enable it.
_Avoid_: Module, component (when referring to this unit)

**Handle**:
The stable string identifier for a Feature, used to name its enablement filter (e.g. `wp_seo_enable_{handle}`) and to key it in the feature Registry. Chosen once and not renamed casually — filters and the future admin UI key off of it.

**Label**:
The human-readable, translatable name a Feature is displayed by — "Open Graph" for `open_graph`. Written at the call site rather than derived from the Handle, since deriving degrades: a `title_description_tags` handle would render as "Title Description Tags" rather than "Title & Description Tags". Display text only — it names no filter and keys nothing, so unlike a Handle it is neither validated nor constrained.

**Registry**:
The enumerable collection of every WP SEO Feature and whether it is Active. Backs the v2.0 readonly admin display, the v2.1 toggle UI, and the generated recommended-features documentation.

**Enabled**:
A Feature whose filters answered yes. A statement of intent about one Feature, independent of whether anything acted on it.
_Avoid_: On, turned on

**Active**:
A Feature that is actually running on the site. A Feature nested inside a group that was never enabled is not Active however its own filters answered, so Enabled and Active can disagree — the admin display reports Active, because that is what an administrator is asking about.
_Avoid_: Loaded, running. "Booted" names the mechanism rather than the state, and belongs in code (`boot()`, `booted()`) rather than in conversation about a site.
