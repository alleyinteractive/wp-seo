# The readonly features page is not itself a Feature

[ADR 0001](0001-feature-enablement-defaults-to-opt-in.md) says the plugin does nothing unless a feature is explicitly enabled, which raises the question of whether the readonly admin page from [ADR 0004](0004-readonly-settings-page-in-v2-0.md) should carry a handle and be opted into like everything else.

Decision: it registers unconditionally, and is not a `Feature`.

The principle in ADR 0001 exists so that a site never emits SEO output nobody asked for, and never silently loses output it depended on. A readonly admin screen emits nothing to the front end and cannot affect a site's SEO either way, so gating it protects nothing.

Gating it would also invert its purpose: the page exists to answer "what is this plugin doing on my site," and making it opt-in guarantees it is missing for the person most likely to need it — someone looking at a site where nothing appears to be enabled. A site that enabled no features would show no WP SEO menu at all, which is indistinguishable from the plugin being broken.

The cost is a visible admin menu on every site running the plugin, and one exception to an otherwise uniform rule. Both are accepted: introspection about the feature system is a different kind of thing from the product capabilities the system gates.
