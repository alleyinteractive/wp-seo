# WP SEO

WP SEO is an enterprise SEO plugin for large, performant WordPress sites. v2 rebuilds it as a set of independently togglable features rather than one always-on block of behavior.

## Language

**Feature**:
Something WP SEO offers a site that the site can choose to do without — Open Graph tags, canonical URLs, XML sitemaps. Off by default; a consumer must explicitly enable it. Implemented as an `Alley\WP\Types\Feature` (from `wp-type-extensions`) with a `boot()` method.

The test is whether someone deciding what the plugin should do for their site would recognize it and have an opinion about it. Scaffolding that exists so Features can do their job — a container other Features put fields into, a formatting system their output is written in — is not a Feature, however cleanly it is separated in code. Such infrastructure loads unconditionally and is judged by whether it does anything when no Feature is enabled, which it should not.
_Avoid_: Module, component (when referring to this unit)

**Handle**:
The stable string identifier for a Feature, used to name its enablement filter (e.g. `wp_seo_enable_{handle}`) and to key it in the feature Registry. Chosen once and not renamed casually — filters and the future admin UI key off of it.

**Label**:
The human-readable, translatable name a Feature is displayed by — "Open Graph" for `open_graph`. Written at the call site rather than derived from the Handle, since deriving degrades: a `title_description_tags` handle would render as "Title Description Tags" rather than "Title & Description Tags". Display text only — it names no filter and keys nothing, so unlike a Handle it is neither validated nor constrained.

**Category**:
A heading that Features are listed under on the admin screen — "Technical SEO", "Social sharing". Organizes a page and nothing else: a Category has no Handle, names no filter, and cannot be turned on or off. Distinct from a group, which is a Feature in its own right whose Handle gates everything inside it.
_Avoid_: Group, section (when referring to this heading)

**Registry**:
The enumerable collection of every WP SEO Feature and whether it is Active. Backs the v2.0 readonly admin display, the v2.1 toggle UI, and the generated recommended-features documentation.

**Enabled**:
A Feature whose filters answered yes. A statement of intent about one Feature, independent of whether anything acted on it.
_Avoid_: On, turned on

**Active**:
A Feature that is actually running on the site. A Feature nested inside a group that was never enabled is not Active however its own filters answered, so Enabled and Active can disagree — the admin display reports Active, because that is what an administrator is asking about.
_Avoid_: Loaded, running. "Booted" names the mechanism rather than the state, and belongs in code (`boot()`, `booted()`) rather than in conversation about a site.
