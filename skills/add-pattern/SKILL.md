---
name: add-pattern
description: Add (merge) a decorative background pattern layer onto a Bricks selector — a global class (`.class-name`) or an element id (`#id`) — applying the standard pattern rules (relative + isolation + a low-opacity `::after` carrying an SVG pattern background and a radial mask) via the connected Novamira MCP. Use when the user runs `/add-pattern <selector>`, or asks to add / ensure a background pattern (grid / dots / lines) behind content. Requires a connected Novamira MCP server.
---

# Add pattern to a Bricks selector

Ensure the given selector carries the standard decorative pattern layer. **Merge** into any existing CSS — never clobber. (Sibling of `add-overlay`: that skill uses a `::before` scrim; this one uses a `::after` pattern — see *Coexistence* below.)

## Input
`args` = a single selector:
- **`.class-name`** → a Bricks **global class** named `class-name`. CSS is authored on the class with `%root%`.
- **`#id`** → an element id. CSS goes in the **Theme CSS** (`settings.css.stylesheet`) targeting `#id`.

If `args` is empty, or not prefixed with `.` or `#`, ask the user for the selector before doing anything.

## The pattern rules
With `SEL` = `%root%` (for a global class) or the literal `#id`:

```css
%root% {
  position: relative;
  isolation: isolate;

  &::after {
    opacity: 0.3;
    z-index: -50;
    content: '';
    position: absolute;
    inset: 0;
    /* @abp-pattern: eyJ0eXBlIjoiZ3JpZCIsImZnIjoidmFyKC0tYnJ4cC1zdXJmYWNlLWwtMTApIiwiYmciOiJ0cmFuc3BhcmVudCIsInNpemUiOjIwLCJzdHJpcGVXaWR0aCI6NTAsImRvdFNpemUiOjMwLCJncmlkTGluZSI6MSwiYXNwZWN0UmF0aW8iOiIxOjEiLCJtYXR0ZUNvbG9yIjoibm9uZSJ9 */
    background-image: url("data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2220%22%20height%3D%2220%22%3E%3Crect%20width%3D%2220%22%20height%3D%2220%22%20fill%3D%22transparent%22%2F%3E%3Cline%20x1%3D%220%22%20y1%3D%220%22%20x2%3D%2220%22%20y2%3D%220%22%20stroke%3D%22hsl(238%2C%2010%25%2C%2095%25)%22%20stroke-width%3D%221%22%2F%3E%3Cline%20x1%3D%220%22%20y1%3D%220%22%20x2%3D%220%22%20y2%3D%2220%22%20stroke%3D%22hsl(238%2C%2010%25%2C%2095%25)%22%20stroke-width%3D%221%22%2F%3E%3C%2Fsvg%3E");
    background-repeat: repeat;
    background-size: 20px 20px;
    /* @abp-pattern-end */
    /* @abp-gradient: eyJvdXRwdXRNb2RlIjoibWFzayIsImdyYWRpZW50VHlwZSI6InJhZGlhbCIsImJhc2VDb2xvciI6InRyYW5zcGFyZW50IiwiYXNwZWN0UmF0aW8iOiIxOjEiLCJtYXR0ZUNvbG9yIjoibm9uZSIsImxpbmVhckFuZ2xlIjo5MCwibGluZWFyRGlyZWN0aW9uIjpudWxsLCJsaW5lYXJTdG9wcyI6W3siaWQiOiI1ZGZlNTk3OC00ZmVhLTRlMTMtYTg5Mi1jNGQ1ZjNlYmQ2ODciLCJjb2xvciI6IiM2MzY2ZjFmZiIsInBvc2l0aW9uIjowfSx7ImlkIjoiZGYxYzNiZjItNThiYy00MjYwLWI0OTgtNTQyZjUwNmE0OWVkIiwiY29sb3IiOiIjYTg1NWY3ZmYiLCJwb3NpdGlvbiI6MTAwfV0sInJhZGlhbExheWVycyI6W3siaWQiOiIzMmNhY2E2NC02N2YxLTRlNTUtYWIyNC0zOTc2M2MwZGU2MjkiLCJsYXllck51bWJlciI6MSwiZnJvbUNvbG9yIjoiI2ZmZmZmZiIsInRvQ29sb3IiOiJ0cmFuc3BhcmVudCIsInN0b3BQZXJjZW50Ijo2NywicG9zWCI6MCwicG9zWSI6MCwic2l6ZSI6IiJ9XSwibGF5ZXJOdW1iZXJDb3VudGVyIjoyLCJjb25pY1N0YXJ0QW5nbGUiOjAsImNvbmljUG9zWCI6NTAsImNvbmljUG9zWSI6NTAsImNvbmljU3RvcHMiOlt7ImlkIjoiNzUyMTgwMDItNGViZS00ZWVhLTlmNGMtMmM1ZDIxMTM0ODNmIiwiY29sb3IiOiIjNjM2NmYxZmYiLCJwb3NpdGlvbiI6MH0seyJpZCI6IjBlYWNhYWEwLTAxM2EtNDFkMi05YzgyLWFiYWRlMmI1MmRlNiIsImNvbG9yIjoiI2E4NTVmN2ZmIiwicG9zaXRpb24iOjEwMH1dfQ== */
    -webkit-mask-image: radial-gradient(at 0% 0%, #ffffff 0px, transparent 67%);
    mask-image: radial-gradient(at 0% 0%, #ffffff 0px, transparent 67%);
    -webkit-mask-mode: luminance;
    mask-mode: luminance;
    /* @abp-gradient-end */
  }
}
```

For an `#id`, swap the outer `%root%` for the literal `#id` (the `&::after` nesting stays).

### About this block
- `position: relative` + `isolation: isolate` give `SEL` its own stacking context; the `::after` at `z-index: -50` sits **behind the content** but **above** an `add-overlay` `::before` (`-900`) and a `brxp-has-bg-media__media` image (`-1000`). Layering (back → front): **bg image (-1000) → overlay scrim (-900) → pattern (-50) → content**.
- The `/* @abp-pattern… */ … /* @abp-pattern-end */` and `/* @abp-gradient… */ … /* @abp-gradient-end */` comments are **metadata for the Advanced Background Patterns generator** — **preserve them verbatim** so the pattern stays re-editable. Keep the data-URI percent-encoding (`%3C`, `%20`, …) intact; the **only** token Bricks substitutes is `%root%`.

## Procedure
1. **Prereq:** a connected Novamira MCP server (`mcp-adapter-execute-ability`). If several are connected, confirm which site.
2. **Parse `args`** → class vs id (see Input).
3. **Read the current CSS for the target** (so you can merge, not clobber):
   - class `foo` → its `_cssCustom` via `novamira/bricks-get-global-class` (or `execute-php` over `get_option('bricks_global_classes')`). If the class doesn't exist, you'll create it in step 5.
   - id → the active theme style's `settings.css.stylesheet` via `novamira/bricks-get-theme-style` (or `execute-php`). Search it for an existing `#id` rule.
4. **Merge (never clobber):**
   - **No existing rule for `SEL`** → add the full pattern block above.
   - **Existing `SEL` rule** → add `position: relative;` *only if* no `position` declaration is present, and `isolation: isolate;` *only if* absent; then add the `&::after { … }` (class) / `SEL::after { … }` (id) **only if `SEL` has no `::after` yet**. If a *different* `::after` already exists, **stop and ask the user** — never overwrite an existing pseudo-element.
   - Leave every other existing declaration untouched. **Idempotent:** if an `@abp-pattern` marker (or the `z-index: -50` `::after`) is already on `SEL`, report "already present" and change nothing.
5. **Write back:**
   - class → `novamira/bricks-edit-global-class` with the merged `settings._cssCustom` (author with `%root%`, native `&::after` nesting, pretty-printed; comments preserved). If the class is missing, create it with `novamira/bricks-create-global-class`.
   - id → set the merged `settings.css.stylesheet`. **Append outside** the `/* BRXP_LAYOUT_RAILS_* */` locked block — never edit inside it. The stylesheet is large, so an `execute-php` read-modify-write is usually simplest (or pass the full merged string to `bricks-edit-theme-style`).
6. **Verify** via read-back that the rule is present (and on the frontend if useful — the `SEL` rule + the pattern `background-image` / `mask-image`), then report what was **added** vs. **already present**.

## Coexistence
`add-overlay` (`::before`, z `-900`) and `add-pattern` (`::after`, z `-50`) are designed to layer together. Applying both to the same `SEL` is expected — merge each into the **same** `%root%` rule (one shares the `position`/`isolation` root; the scrim is `::before`, the pattern is `::after`). Never let one overwrite the other.

## Notes
- Follow the project's `CLAUDE.md` conventions.
- The pattern's shape/colours are a **default placeholder** from the generator (a 20px grid in `hsl(238,10%,95%)` ≈ `var(--brxp-surface-l-10)`, faded by a radial mask). To restyle, regenerate the pattern or edit the block — prefer `brxw-`/`brxp-` tokens where practical, and keep the `@abp-*` markers so it stays editable.
- **Pairs with `brxp-has-bg-media`:** apply to the same container that has the background image; the pattern lands just behind the content.
- **Builder-reload caveat:** data-layer edits won't appear in an already-open Bricks builder until it's reloaded — verify on the frontend.
