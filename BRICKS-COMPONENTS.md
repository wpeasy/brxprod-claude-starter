# BRICKS-COMPONENTS.md — Bricks Component patterns reference

On-demand reference (split out of `CLAUDE.md`). Load it when **building or editing Bricks Components** via the Novamira abilities. Documents the `/* Settings */` + variant CSS pattern, component property types, sub-components, and common mistakes.

---

## Component namespacing

| Prefix | Scope | Examples |
|---|---|---|
| `nm-` | Project-level blocks and page components | `nm-cta`, `nm-section-head`, `nm-team-card` |
| `atom-` | Atomic, reusable **sub-components** — library-level, not project-bound | `atom-eyebrow`, `atom-eyebrow--order-top` |
| (no prefix) | Cross-project structural components | `title-block`, `dual-button` |

`atom-` sub-components are the building blocks you embed inside other components. They are self-contained, styled independently, and placed as `cid` pointer elements inside a parent component's body.

---

## CSS — the `/* Settings */` + variant pattern (MUST follow)

Every component root class uses a two-block CSS pattern: a `/* Settings */` block that bridges **public** override vars to **private** consumption vars, followed by the actual rules. Child element classes consume the **private** vars. Variant modifier classes **only set public vars** — never direct CSS properties.

### Root class

```css
/* Settings */
.my-block {
  --_my-block-color: var(--my-block-color, var(--brxp-primary));
  --_my-block-size:  var(--my-block-size,  var(--brxw-text-m));
  --_my-block-gap:   var(--my-block-gap,   var(--brxw-content-gap));
}

.my-block {
  display: flex;
  flex-direction: column;
  gap:   var(--_my-block-gap);
  color: var(--_my-block-color);
}
```

### Child element class (consumes private vars)

```css
.my-block__title {
  font-size: var(--_my-block-size);
  /* inherits private vars from ancestor root */
}
```

### Variant modifier class (ONLY sets public vars — nothing else)

```css
/* Settings */
.my-block--large {
  --my-block-size: var(--brxw-text-xl);
}

/* Settings */
.my-block--align-left {
  --my-block-align-items: flex-start;
  --my-block-text-align:  left;
}
```

The `/* Settings */` comment appears on variant classes too — it signals that the class only contains var declarations, no direct CSS. A variant class that contains anything other than var declarations is a pattern violation.

---

## Component properties — types and connections

Properties are defined in the `properties` array on the component record. Each property connects one or more element settings to a labelled control in the builder panel.

### Anatomy of a property

```json
{
  "id":          "prop_title",
  "label":       "Title",
  "type":        "text",
  "group":       "grp_heading",
  "default":     "Change this heading",
  "connections": { "element_id": ["text"] }
}
```

- **`id`** — stable property id used by instances to override. Omit to auto-generate; always set explicitly so instances don't break when the component is edited.
- **`group`** — arbitrary string id; properties sharing the same group are shown together in the builder panel. Use one group per semantic section (heading, lede, footer, visibility).
- **`connections`** — map of `element_id → [setting_key, ...]`. One property can write to multiple elements. Sub-key syntax: `"setting:sub"` writes `settings[setting][sub]` (one level only).
- A property with **no `connections`** stores its value on the instance but applies nothing automatically — useful for JS-driven behaviour or data attributes.

### Property types

| Type | When to use | What it connects to |
|---|---|---|
| `text` | Short single-line content | `settings.text` on heading / text-basic |
| `textarea` | Multi-line content | `settings.text` |
| `number` | Numeric values | any numeric setting |
| `select` | Predefined dropdown (heading tag, button style/size) | `settings.tag`, `settings.style`, `settings.size` |
| `toggle` | Boolean show/hide or feature flag | `settings._hideElementBuilder`, `settings._hideElementFrontend` (see below) |
| `class` | Visual variants via BEM modifier classes | `settings._cssGlobalClasses` (see below) |
| `link` | URL/internal link | `settings.link` — use `type:"external"` as default for href rendering |
| `icon` | Icon picker | `settings.icon` |
| `image` | Image picker | `settings.image` |
| `color` | Colour picker | colour settings |

### Toggle — showing and hiding elements

`_hideElementBuilder` hides the element **in the Bricks builder canvas only** (useful for de-cluttering complex components during editing). `_hideElementFrontend` hides it **on the live frontend**. Use both together to fully hide; use `_hideElementBuilder` alone to keep it live but out of the editor view.

```json
{
  "id": "prop_hide_footer",
  "label": "Hide Footer",
  "type": "toggle",
  "group": "grp_visibility",
  "connections": {
    "footer_el_id": ["_hideElementBuilder", "_hideElementFrontend"]
  }
}
```

A single toggle can connect to **multiple elements** — e.g. hiding the footer hides both a lede element AND the footer wrapper in one click.

### `class` type — variant modifiers

The `class` property applies BEM modifier classes to an element's `_cssGlobalClasses`. Each option holds an array of class IDs to add; the "default / none" option has `value: ""`.

Set `multiple: true` to allow stacking modifiers (e.g. both "Secondary" colour AND "Compact" size at once).

```json
{
  "id": "prop_style",
  "label": "Style",
  "type": "class",
  "group": "grp_style",
  "multiple": true,
  "default": ["opt_default"],
  "connections": { "root_el_id": ["_cssGlobalClasses"] },
  "options": [
    { "id": "opt_default",   "label": "Default",   "value": "" },
    { "id": "opt_primary",   "label": "Primary",   "value": ["class_id_primary"] },
    { "id": "opt_secondary", "label": "Secondary", "value": ["class_id_secondary"] },
    { "id": "opt_compact",   "label": "Compact",   "value": ["class_id_compact"] }
  ]
}
```

When connected to the **root** element's `_cssGlobalClasses`, the selected variant class is added to the root, which then overrides its public CSS vars, which flow down via the `/* Settings */` bridge into the private vars used by child classes. No child class CSS is ever modified.

### `select` type — Bricks native settings

Use `select` to expose Bricks native dropdown settings through the component property panel. Common targets:

```json
{ "connections": { "heading_el_id": ["tag"] } }   // h1 / h2 / h3 …
{ "connections": { "button_el_id":  ["style"] } }  // primary / secondary / light …
{ "connections": { "button_el_id":  ["size"] }  }  // sm / md / lg / xl
```

---

## ⚠ Data-layer class renames and component property connections

When you rename a global class's **name** via the data layer (`execute-php` writing directly to `bricks_global_classes`) rather than through the Bricks builder UI, **component `class`-type property connections can silently break**. The class IDs are unchanged and the CSS applies correctly on the frontend — but the builder's internal connection state loses the link between the property and the renamed class.

**Symptoms:** variant classes stop applying in the editor canvas but work on the frontend.

**Fix:** open the affected component in the builder, find the broken `class`-type property, disconnect it, and reconnect it to the correct element.

**Prevention:** when renaming classes that are referenced by component `class`-type property connections, prefer doing the rename through the Bricks builder UI (which keeps connections in sync), or plan to manually reconnect all affected properties after a data-layer rename.

---

## Sub-components

A component body can embed another component as a `cid` pointer element. The inner component is fully self-contained — its own `/* Settings */`, variant classes, and property panel.

```json
{
  "id": "eyebrow_inst",
  "name": "div",
  "parent": "parent_el_id",
  "label": "",
  "cid": "atom_eyebrow_component_id",
  "settings": [],
  "properties": {}
}
```

To drive a sub-component's **variant** from the parent's class property: the parent's class property option values should contain the **same class IDs** that the sub-component's own class property would apply (the `atom-eyebrow` variant classes — `atom-eyebrow--primary`, `atom-eyebrow--order-top` etc. — are shared option values across components). The parent's property connection points at the **sub-component instance element's** `_cssGlobalClasses`, which merges the selected classes onto the wrapper div that carries the sub-component.

---

## Property groups — organising the builder panel

Group IDs are arbitrary strings; all properties sharing a `group` appear as a section in the builder's properties panel. Design one group per semantic area:

| Group purpose | Example properties |
|---|---|
| Main content | heading text, heading level |
| Supporting content | lede text, subtitle text |
| Footer / CTA | button text, button link, button style |
| Appearance / Style | class-type variant property |
| Visibility | toggle-hide properties for optional sections |

---

## Worked example — `atom-eyebrow` (atomic sub-component)

Root class (`atom-eyebrow`) exposes everything as CSS vars:

```css
/* Settings */
.atom-eyebrow {
  --_atom-eyebrow-color:          var(--atom-eyebrow-color,          inherit);
  --_atom-eyebrow-font-size:      var(--atom-eyebrow-font-size,      inherit);
  --_atom-eyebrow-font-weight:    var(--atom-eyebrow-font-weight,    bold);
  --_atom-eyebrow-letter-spacing: var(--atom-eyebrow-letter-spacing, 0.1em);
  --_atom-eyebrow-order:          var(--atom-eyebrow-order,          0);
  --_atom-eyebrow-text-transform: var(--atom-eyebrow-text-transform, uppercase);
}

.atom-eyebrow {
  display: flex;
  line-height: 1;
  color:          var(--_atom-eyebrow-color);
  text-transform: var(--_atom-eyebrow-text-transform);
  font-weight:    var(--_atom-eyebrow-font-weight);
  font-size:      var(--_atom-eyebrow-font-size);
  letter-spacing: var(--_atom-eyebrow-letter-spacing);
  order:          var(--_atom-eyebrow-order);
}
```

Variant classes only set public vars:

```css
/* Settings */
.atom-eyebrow--primary    { --atom-eyebrow-color: var(--brxp-primary); }

/* Settings */
.atom-eyebrow--secondary  { --atom-eyebrow-color: var(--brxp-secondary); }

/* Settings */
.atom-eyebrow--compact {
  --atom-eyebrow-letter-spacing: 0.08em;
  --atom-eyebrow-font-size:      var(--brxw-text-s);
}

/* Settings */
.atom-eyebrow--order-top  { --atom-eyebrow-order: -1; }
```

`--atom-eyebrow-order: -1` combined with `display: flex; flex-direction: column` on the parent is the **heading-first / eyebrow-after** source-order pattern — eyebrow appears visually above the heading but is last in the DOM.

---

## Common mistakes to avoid

- **Variant class with direct CSS properties** — a variant class must ONLY set public vars (`--block-name-*`). If you find yourself writing `color: red` in a variant, extract it as a var override instead.
- **Child class reading public vars directly** — child classes consume PRIVATE vars (`--_block-*`). Public vars are only for the `/* Settings */` bridge.
- **`select` vs `class` type** — use `select` for Bricks native enums (tag, style, size); use `class` for your own visual variants. They are not interchangeable.
- **Properties without `connections`** — valid but silent; a property without connections stores its value but applies nothing. Add a connection or document the JS/CSS mechanism that reads it.
- **Omitting `group`** — ungrouped properties pile up in a flat list. Always group related properties.
- **`type: "toggle"` vs boolean select** — `toggle` is the right choice for binary show/hide; for anything with a visible label in the panel, a `class` or `select` type reads better.
- **Building with `nm-` classes for sub-components** — atomic sub-components that are reusable across projects or components should use `atom-` (or another project-agnostic prefix). Reserve `nm-` for project-specific blocks.

---

## Template vs Component

Use this to decide how to build a reusable pattern before touching the editor.

| | **Component** | **Template** |
|---|---|---|
| **What it is** | A reusable element *tree* (multi-element structure) that instances can override via properties | A full Bricks layout (header, footer, archive, single post, search) rendered via template conditions |
| **Edited centrally?** | Yes — edit the component, all instances update | Yes — edit the template, all pages using it update |
| **Scope** | A UI pattern repeated *within* pages (CTA band, card, section header, hero, button group) | A page-level layout applied *across* pages (site header, post template, archive grid) |
| **Instance variation** | Via component properties (text, image, variant class) | Via dynamic data / Bricks query loops |
| **Use when** | The same element tree appears on ≥ 2 pages / locations and needs central editing | The structure is a header, footer, single, archive, search, or 404 page layout |

### Quick decision rule

- Repeated **section or sub-section** inside a page → **Component**
- Repeated across pages as the **page frame** (what wraps the content) → **Template**
- A single styled element (button, eyebrow, badge) → **global class + BEM modifiers** (no Component needed)

### Common patterns and their type

| Pattern | Type | Notes |
|---|---|---|
| Hero / page banner | Component | Properties: heading, subheading, CTA link/label, background image |
| Title Block (eyebrow + heading + lede) | Component | `atom-` namespace if reused across projects |
| CTA Section / CTA Band | Component | Properties: heading, body, primary + secondary button |
| Button Group | Component or global class | Single row of 2–3 buttons → global class + BEM is often enough; complex variant logic → Component |
| Card / Testimonial Card | Component | Used inside a query loop; properties or dynamic data for content |
| Card Grid | Component (or query loop) | Static set → Component with slot children; CMS-bound → query loop + card Component |
| Icon + Heading + Text tile | Component | Wraps `icon-box` or hand-built structure |
| Logo / Partner strip | Native `carousel` element | Not a Component unless it needs property-driven content |
| Section header (eyebrow + h2 + lede, centred) | Component | Very common — make it once as `nm-section-head` |
| Site header / nav | Template (header) | Scoped with "Entire website" condition |
| Site footer | Template (footer) | Scoped with "Entire website" condition |
| Single post layout | Template (single) | Scoped to post type |
| Archive / blog grid | Template (archive) | Scoped to post type archive |

---

## Registered Element Schemas

> Run the `bricks-elements` skill to populate this section. It fetches the controls/settings schema for every registered Bricks element via the Novamira MCP and writes individual `BRICKS_EL_{name}.md` reference files to the project root, then replaces this section with a linked index table.
>
> **Command:** invoke `/bricks-elements` (or ask Claude to run the `bricks-elements` skill) with a Novamira MCP server connected.

No element schemas generated yet.
