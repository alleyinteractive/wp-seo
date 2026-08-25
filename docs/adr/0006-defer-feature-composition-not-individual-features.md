# Defer feature composition once, not each feature individually

WP SEO features must not decide whether they are enabled until themes and other plugins have had a chance to add their filters, which means waiting for `after_setup_theme`. [ADR 0005](0005-defer-feature-gating-to-after_setup_theme.md) originally put that wait inside each feature. That turned out to need a `doing_action()` guard, because a feature nested inside an enabled `Group` is booted from *within* `after_setup_theme`, and a callback added to a hook mid-iteration at the same priority is unreachable.

Decision: `main()` is hooked to `after_setup_theme` in `wp-seo.php`, and feature gating is synchronous — a feature checks its filters and boots, or doesn't, immediately. The wait happens once, in one visible line, instead of being duplicated into every feature.

## Why the obvious patterns don't transfer

WP SEO combines two requirements that are usually found apart, so neither established approach fits unmodified:

- **Deferring inside each feature** suits a plugin gated by consumer filters, which genuinely must wait. It assumes features are flat, mutually independent units — and breaks as soon as a gating wrapper contains another one, which is the mid-iteration problem above.
- **Composing synchronously at plugin load** suits a plugin that nests composition freely, because it gates on self-evaluable conditions (environment type, `is_admin()`, blog ID) rather than on anything external. Nothing configures it, so it has nobody to wait for.

WP SEO needs consumer filters *and* nesting ([ADR 0003](0003-nested-handles-for-feature-groups.md)). This decision takes the synchronous interior of the second approach and adds the single deferral point that only the first one needs.

## Consequences

Synchronous gating is correct at any time after theme load, so a feature composed late (say, during `init`) still gates correctly. Per-feature deferral had the opposite property: anything constructed after `after_setup_theme` had already fired would hook a dead action and silently never boot — a failure window covering most of the WordPress lifecycle.

The trade-off is that timing is now a property of *where* features are composed rather than of the feature itself. Composing before themes load would read filters too early. In practice this window is narrow and nothing composes there: WP SEO's features are all built by the plugin inside `main()`, and client sites configure the plugin with `add_filter()` rather than by constructing features. Sites needing behavior the plugin doesn't offer are expected to implement it in their theme, outside this system entirely.
