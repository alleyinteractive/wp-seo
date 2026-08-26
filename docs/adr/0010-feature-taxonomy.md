# Features divide by the output they produce, not by the code that produces it

The behavior in `php/` had to be divided into Features before it could be migrated, and the code offered three plausible seams: the surface it acts on (front-end output versus editor UI), the page type it acts for (posts, terms, archives), or the output it produces (titles, descriptions, canonical URLs, robots).

The existing code is organized by surface — one class emits every tag from `wp_head()` and renders every editor field in one meta box — so dividing that way would have been nearly free. It was rejected anyway. A Handle is a contract a site writes into its own code, so a division chosen for migration convenience would permanently expose the shape of a file that happened to exist in 2015. "SEO Output" is also not something anyone wants to turn off, and it cannot express wanting canonical URLs without robots directives.

Decision: Features divide by output type. Each covers its output everywhere it appears — post, term, archive, search, 404 — and owns both its front-end rendering and its editor field.

| Feature | Handle |
| --- | --- |
| Title Tags | `titles` |
| Meta Descriptions | `descriptions` |
| Canonical URLs | `canonical_urls` |
| Robots Meta | `robots_meta` |
| Open Graph | `open_graph` |
| Arbitrary Meta Tags | `arbitrary_tags` |

Titles and descriptions are separate Features rather than one. A site whose theme already renders titles well may want only descriptions, and the reverse becomes likelier as generated descriptions arrive. Nothing is lost by keeping them apart, and merging them could not be undone without breaking whoever had filtered the combined handle.

Handles are flat, lowercase, and underscore-separated. Nothing here is nested, so nothing is namespaced.

## Page type is a separate axis

Dividing by page type was rejected as a way to define Features, but it remains how configuration is organized: a site enables `titles` once and then configures a title format for each page type. The two axes are orthogonal and should not be collapsed into each other. Separating page types properly in the admin is tracked in [alleyinteractive/wp-seo#199](https://github.com/alleyinteractive/wp-seo/issues/199).

## What this leaves as infrastructure

Dividing by output type splits things the code currently keeps together, and what is left over is not Features but scaffolding — it fails the test in `CONTEXT.md`, since no site would have an opinion about whether to have it.

The editor's SEO meta box stays a single box, registered unconditionally, which enabled Features contribute their fields to. Giving each Feature its own box would put four boxes on the edit screen, and gating the box behind a Feature of its own would let a site enable `titles` and find nowhere to set one. The box renders nothing when no Feature contributes to it, so an unconfigured site still gets nothing. The same arrangement will be needed for each page-type settings screen under #199, which is a reason to prefer Features registering fields rather than registering markup for one particular surface.

Formatting tags are infrastructure for the same reason: they are the notation a title or description format is written in, not something to have an opinion about.

## No recommended set in v2.0

Requirement 4 of the original specification asked documentation to name a recommended set of Features. Each Feature is instead documented on its own merits, because a list naming five of six Features says little and implies the sixth is second-rate. A baseline for sites migrating from another SEO plugin — a checklist against silently losing behavior they already had, rather than a curated shortlist — is deferred to [alleyinteractive/wp-seo#200](https://github.com/alleyinteractive/wp-seo/issues/200), when the Features it names exist.
