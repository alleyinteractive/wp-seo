# v2.0 ships a readonly feature page under a new top-level WP SEO menu, not a Site Health entry

Issue #131 requires features to be surfaced in the admin as readonly for v2.0.0, ahead of the full toggle UI deferred to v2.1. The cheap way to do that is to feed each feature's state into WordPress core's Site Health → Debug Information screen, which costs one filter and no UI.

Decision: build a bespoke page instead, reading from the feature Registry ([ADR 0002](0002-bespoke-feature-wrapper-for-gating-and-registry.md)), and give it a new top-level "WP SEO" admin menu rather than nesting it under Core's Settings sidebar (mirroring Yoast's own top-level menu, relevant since WP SEO is meant to replace Yoast on most client sites). This is more work than a Site Health entry, and is expected to be superseded by the v2.1 toggle UI, but a dedicated, discoverable location beats a debug screen most site admins never open.

For v2.0, this new top-level menu holds only the readonly feature page. WP SEO's existing content-configuration settings page stays where it is today (`Settings → SEO`, via `add_options_page()`) rather than moving in the same effort — migrating it is tracked separately in [alleyinteractive/wp-seo#194](https://github.com/alleyinteractive/wp-seo/issues/194) so it doesn't block #131.
