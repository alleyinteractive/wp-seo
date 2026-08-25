---
status: superseded by [ADR 0006](0006-defer-feature-composition-not-individual-features.md)
---

# Feature enablement checks defer to `after_setup_theme`

> **Superseded.** The decision to wait for `after_setup_theme` still stands, but the mechanism described here — each feature deferring itself — was replaced by deferring the composition root once. See [ADR 0006](0006-defer-feature-composition-not-individual-features.md). This record is kept because the guard it describes is subtle, and a future reader may otherwise reinvent it.


`wp-seo.php` currently calls `main()` synchronously at plugin-load time. WordPress loads plugins before it loads the active theme's `functions.php`, so if the enablement filter ([ADR 0001](0001-feature-enablement-defaults-to-opt-in.md)) were checked at that point, a site adding `add_filter('wp_seo_enable_{handle}', ...)` in its theme would register that filter too late — after WP SEO already checked it and defaulted to off.

Decision: the WP SEO feature wrapper defers its filter check (and the wrapped feature's `boot()`) to `after_setup_theme`, so that features load only after all plugins and themes have had a chance to add filters. This is safe for WP SEO's concerns specifically because `after_setup_theme` fires well before `init`, `send_headers`, `template_redirect`, and `wp_head` — deferring *when a feature's hooks get registered* by this much doesn't delay *when they fire*. It would only be wrong for a feature that needs to act on something earlier than `after_setup_theme` (e.g. theme selection), which no current or planned WP SEO feature does.

## Consequence: plain deferral does not nest

Deferring by `add_action( 'after_setup_theme', ... )` alone is not sufficient, and [ADR 0003](0003-nested-handles-for-feature-groups.md)'s nested handles do not work without an additional guard.

A feature nested inside an enabled `Group` has its `boot()` called *from within* `after_setup_theme` — the parent's own gating check is what calls it. Its `add_action( 'after_setup_theme', ... )` then lands at priority 10 on a hook WordPress is already iterating. `WP_Hook::apply_filters()` runs `foreach ( $this->callbacks[ $priority ] as $the_ )` over a *copy* of that priority bucket, and `resort_active_iterations()` only re-syncs the priority list, never re-entering a bucket mid-iteration (its same-priority special case is skipped, since the priority already existed). The child's callback is therefore unreachable and the feature silently never boots.

The wrapper guards against this by gating immediately when `doing_action( 'after_setup_theme' )` is true, since in that case there is nothing left to wait for. `doing_action()` is used rather than `did_action()` deliberately: `did_action()` stays true forever once the hook has fired, which would collapse the deferral entirely in any late-loading context and make this ADR's actual decision untestable.

