# Categories group features for display only, and do not gate them

Features are listed on an admin screen that will grow past the point where a flat list reads well, so they are grouped under headings. The plugin already has a grouping mechanism — `Feature::group()` from [ADR 0008](0008-groups-own-their-children.md) — and it would be the obvious thing to reach for.

Decision: it is not the same thing, and the two are kept separate. A Category is a heading on the features screen. It has no handle, no filter, no row of its own, and no way to be turned on or off. It exists to organize a page.

`Feature::group()` builds a gating group: a real feature with a handle whose filter controls everything inside it. That is for a case where the children are not independently meaningful — the parts of a sitemap implementation, say — and where a site asking for the group is asking for all of it.

Grouping features that a site would name and choose separately, such as title tags and meta descriptions, is a display concern. Making it a gating group would add a handle, a filter, and a row to express something a reader can already see, and would offer sites a switch nobody wants.

The distinction matters more than it looks, because the two have opposite reversibility. A handle is a contract: once a site filters on it, renaming it breaks that site. A Category is only ever rendered, so it can be introduced, renamed, or removed at any time without breaking anything. Deciding a feature's handle is therefore urgent and deciding its Category is not, which is why the feature list is settled first and categories are applied to it later.
