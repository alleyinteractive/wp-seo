# Feature groups get their own handle, independent of their children's handles

A "complete feature" from a user's perspective (e.g. "customize title & description tags") is sometimes implemented as a `Group` of several smaller `Feature`s (e.g. posts-titles, archive-titles). If the group were the only handled unit, its children could never be toggled independently later without a breaking change to existing filter usage.

Decision: both groups and their children get their own handle and filter, via the same wrapper from [ADR 0002](0002-bespoke-feature-wrapper-for-gating-and-registry.md) applied at both levels — no special-casing needed, since the wrapper works uniformly on any `Feature`, including a `Group`. A child's filter defaults to enabled, but only takes effect if its parent group is enabled; disabling the group's handle short-circuits the whole subtree regardless of the children's own filters.

This costs an extra handle and filter per child versus a single group-level toggle, so it isn't free — restraint on how finely features get split is a judgment call left to the developer registering them, not a rule enforced by the system.
