---
name: add-overlay
description: Add (merge) a dark overlay scrim onto a Bricks selector — a global class (`.class-name`) or an element id (`#id`) — applying the standard overlay rules (relative + isolation + a z-index `-900` `::before` scrim) via the connected Novamira MCP. Use when the user runs `/add-overlay <selector>`, or asks to add / ensure a darkening overlay (e.g. behind content over a `brxp-has-bg-media` background image). Requires a connected Novamira MCP server.
---

# Add overlay to a Bricks selector

Ensure the given selector carries the standard overlay scrim. **Merge** into any existing CSS — never clobber.

## Input
`args` = a single selector:
- **`.class-name`** → a Bricks **global class** named `class-name`. CSS is authored on the class with `%root%`.
- **`#id`** → an element id. CSS goes in the **Theme CSS** (`settings.css.stylesheet`) targeting `#id`.

If `args` is empty, or not prefixed with `.` or `#`, ask the user for the selector before doing anything.

## The overlay rules
With `SEL` = `%root%` (for a global class) or the literal `#id`:

```css
SEL {
  position: relative;
  isolation: isolate;

  &::before {
    z-index: -100;
    content: '';
    position: absolute;
    inset: 0;
    background: #00000055;
  }
}
```

Why it works: `position: relative` makes `SEL` the containing block; `isolation: isolate` scopes the negative-z `::before` to this element; the `::before` at `z-index: -100` sits **above** a `brxp-has-bg-media__media` image (`-1000`) and **below** the content — i.e. it darkens a background image so foreground text keeps contrast.

## Procedure
1. **Prereq:** a connected Novamira MCP server (`mcp-adapter-execute-ability`). If several are connected, confirm which site.
2. **Parse `args`** → class vs id (see Input).
3. **Read the current CSS for the target** (so you can merge, not clobber):
   - class `foo` → its `_cssCustom` via `novamira/bricks-get-global-class` (or `execute-php` over `get_option('bricks_global_classes')`). If the class doesn't exist, you'll create it in step 5.
   - id → the active theme style's `settings.css.stylesheet` via `novamira/bricks-get-theme-style` (or `execute-php`). Search it for an existing `#id` rule.
4. **Merge (never clobber):**
   - **No existing rule for `SEL`** → add the full overlay block above.
   - **Existing `SEL` rule** → in its root block add `position: relative;` *only if* no `position` declaration is already present, and `isolation: isolate;` *only if* absent; then add the `&::before { … }` (class) / `SEL::before { … }` (id) **only if `SEL` has no `::before` yet**. If a *different* `::before` already exists, **stop and ask the user** — never overwrite an existing pseudo-element.
   - Leave every other existing declaration untouched. Re-running is idempotent (don't duplicate).
5. **Write back:**
   - class → `novamira/bricks-edit-global-class` with the merged `settings._cssCustom` (author with `%root%`, native `&::before` nesting, pretty-printed). If the class is missing, create it with `novamira/bricks-create-global-class`.
   - id → set the merged `settings.css.stylesheet`. **Append outside** the `/* BRXP_LAYOUT_RAILS_* */` locked block — never edit inside it. The stylesheet is large, so a `execute-php` read-modify-write is usually simplest (or pass the full merged string to `bricks-edit-theme-style`).
6. **Verify** via read-back that the rule is present (and on the frontend if useful), then report what was **added** vs. **already present**.

## Notes
- Follow the project's `CLAUDE.md` conventions.
- Overlay colour defaults to `#00000055` (≈33% black) as specified. If the project prefers a token, `var(--brxw-color-overlay)` is the BRXProd overlay equivalent — offer it, but don't switch without the user's say-so.
- **Pairs with `brxp-has-bg-media`:** apply this to the same container that has the background image; the scrim lands between the image and the content.
- **`%root%` vs class name:** author new CSS with `%root%`, but Bricks **persists the literal class selector** (e.g. `.nm-faq { … }`) and only shows `%root%` in the builder (transposing back on save). On **read-back, expect the literal** — detect the existing rule / `::before` against `.class-name`, not `%root%`, and merge into whichever root form is already stored (don't rewrite it).
- **Builder-reload caveat:** data-layer edits won't appear in an already-open Bricks builder until it's reloaded — verify on the frontend.
