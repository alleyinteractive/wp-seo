# A group owns its children, rather than a child naming its parent

The admin display reports nested features ([ADR 0003](0003-nested-handles-for-feature-groups.md)) as a hierarchy, so the Registry has to know which features belong to which group. It cannot infer this: `Group` is `final` with a private list, so a parent cannot enumerate what it holds, and PHP evaluates inner constructor arguments first, so a child is constructed before its parent exists.

Two ways to supply the missing relationship:

- A child names its parent by handle, as WordPress itself does throughout (`add_submenu_page()`, `add_settings_field()`, script dependencies).
- A group receives its children as objects and records the relationship itself — the Composite pattern.

Decision: the group owns its children, via a `Feature::group()` constructor that takes the children it wraps, builds their `Group` internally, and records parentage as it receives them.

Naming a parent by handle buys late binding: the parent need not exist when the child is declared, and unrelated code can attach to a group it knows nothing about. WP SEO does not need that. Every feature is composed by the plugin in one place, and anything a site wants to contribute goes through `wp_seo_register_features` with the group in hand.

What that flexibility would cost is a class of bug the alternative cannot have. A handle is just a string, so a typo produces a feature silently attached to a parent that does not exist, and detecting it means building validation that the object-based form does not need. Passing the children directly also makes the call site mirror the tree it describes, in the same way that named constructors made a feature's top-level or nested position legible where a bare boolean did not.
