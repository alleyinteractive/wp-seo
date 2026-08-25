# Feature enablement defaults to opt-in

WP SEO v2 features must be explicitly enabled in code before they boot — nothing runs unless a consumer turns it on.

The alternative, shipping features on by default and having sites opt out, suits a plugin whose job is baseline hardening nobody should have to think about. It is wrong for an SEO plugin: a site silently losing SEO output it depended on, such as Open Graph tags, is a worse failure than a site missing an optional feature it never asked for. Opting in is a deliberate act with a visible result; opting out by accident is invisible until something downstream breaks.

Long term, the enabled set should be defined entirely in code (not stored as DB options), so the set of active features can't drift between environments.
