# BRICKS-CORNERS.md — inverted/outset corner utilities + overlapping layouts

On-demand reference (split out of `CLAUDE.md`). Read this **only** when a design calls for concave **scoop/flare/notch** corners or **overlapping/notched panel** layouts. Everyday rounded corners are just `border-radius` with a `brxw-radius-*` token — you don't need this file for those.

## Corner utilities — inverted & outset radius (BRXProd → Wireframes Tools → Corners)
BRXProd ships **16 framework corner utility classes** (Bricks globals). The classes are intentionally **empty** — their CSS lives in the **Theme CSS** inside locked markers (`/* BRXP_INVERTED_RADIUS_START…END */`, `/* BRXP_OUTSET_RADIUS_START…END */`). **Never edit those blocks** (same rule as the rails block); **apply the classes directly** (like `brxp-rails`), never recreate them.

- **Inverted radius** = a concave **scoop cut *into*** the corner → `brxp-inverted-radius-{corner}-{axis}`.
- **Outset radius** = a concave **fillet that flares *outside*** the corner (curves away and bites into the parent — the "tab/flag" join) → `brxp-outset-radius-{corner}-{axis}`.
- `{corner}` = `top-left | top-right | bottom-left | bottom-right`; `{axis}` = `horizontal | vertical`.

**`-horizontal` / `-vertical` selects the pseudo-element slot** (`horizontal → ::before`, `vertical → ::after`). This exists so you can **stack two corners on one element by pairing one `-horizontal` class with one `-vertical` class**; two of the *same* axis collide on one pseudo and only one renders. (*Inverted:* the two axes look identical per corner — slot only. *Outset:* `-horizontal` flares sideways, `-vertical` flares up/down.)

**Control via CSS custom properties** — set them on the element (or an ancestor). They're declared at `:root` and applied via `:where()` (0,0,0 specificity) so your overrides always win. ⚠️ These live in the **Theme CSS `:root`, not the Bricks variables store**, so the `bricks-design-tokens` skill does **not** list them — they are:
- `--inverted-radius` — scoop size (default `var(--brxw-radius-m, 12px)`).
- `--inverted-color` — **set to the surrounding/parent background colour** (default `red`, deliberately loud as a reminder).
- `--outset-radius` — fillet size (default `var(--brxw-radius-m, 12px)`).
- `--outset-color` — **set to the element's own background colour** (default `var(--brxw-color-neutral-25, white)`).

**Usage rules:**
- Size with `--inverted-radius` / `--outset-radius` (prefer `brxw-radius-*` tokens); set the colour var to the correct adjoining surface (prefer a `brxp`/`brxw` colour token).
- **Colour-match gotcha:** the fallback path fakes the corner with a *solid colour*, so you MUST set the matching colour var (inverted → **parent** bg, outset → **element** bg) or the loud default shows.
- **Rendering paths:** *inverted* upgrades to native `corner-shape: scoop` via `@supports` (Chrome 139+) using the element's real background (no colour var needed, pseudo hidden); older browsers — and **all** *outset* — use a pseudo-element + `radial-gradient` fallback.
- **Set the control vars in the block's `/* Settings */` group, not inline on the element.** When a block uses corner utilities, declare `--inverted-radius` / `--outset-radius` (and `--inverted-color` / `--outset-color`) on the **block root**, tied to the block's own settings (e.g. `--outset-radius: var(--_nm-feature-radius); --outset-color: var(--_nm-feature-bg);`). They inherit to the utility elements, stay in sync with the block, and re-theme with it.
- Apply only where a scoop/flare genuinely fits (per the one-global-class / utility-class rules).

**Verified effect lookup** (don't guess the axis/direction — read it off here). Build a labelled **Corner Radius Reference** page once per site (a 16-tile grid: a coloured card on a contrasting panel with each utility applied + a caption) to confirm these visually:

| Class | Slot | Effect |
|---|---|---|
| `brxp-outset-radius-top-left-horizontal` | `::before` | flares **LEFT** |
| `brxp-outset-radius-top-left-vertical` | `::after` | flares **UP** |
| `brxp-outset-radius-top-right-horizontal` | `::before` | flares **RIGHT** |
| `brxp-outset-radius-top-right-vertical` | `::after` | flares **UP** |
| `brxp-outset-radius-bottom-left-horizontal` | `::before` | flares **LEFT** |
| `brxp-outset-radius-bottom-left-vertical` | `::after` | flares **DOWN** |
| `brxp-outset-radius-bottom-right-horizontal` | `::before` | flares **RIGHT** |
| `brxp-outset-radius-bottom-right-vertical` | `::after` | flares **DOWN** |
| `brxp-inverted-radius-{corner}-horizontal` | `::before` | **scoop** cut into that corner |
| `brxp-inverted-radius-{corner}-vertical` | `::after` | **scoop** cut into that corner (looks identical to `-horizontal`) |

- **Outset rule of thumb:** `-horizontal` flares toward the corner's **side** edge (left for left-corners, right for right-corners); `-vertical` flares toward its **top/bottom** edge (up for top-corners, down for bottom-corners). The axis = *which way the fillet flares*.
- **Inverted:** the scoop is symmetric, so the two axes render **identically** — axis only chooses the pseudo **slot** (`::before` vs `::after`), so you can pair one `-horizontal` + one `-vertical` to put **two** scoops on one element. (Same slot ×2 collides — only one renders.)
- **Max two corners per element** (`::before` + `::after`). For a *third* outset/inverted corner you need an **extra element** (e.g. an absolutely-positioned child) with its own utility class — or, for an *inner* corner, a plain convex `border-radius` (doesn't consume a pseudo slot, usually reads the same when a panel wraps it). The element box's own `border-radius` composes freely with the two pseudo corners.

**Reading a corner from a design → which class:**
- **Convex** arc, the element's own colour, corner shaved off → plain **`border-radius`** (no utility).
- **Concave** arc revealing the **surrounding/parent colour** → **inverted *or* outset**. ⚠️ Both look like "surrounding colour in a concave bite", so that alone does **not** distinguish them — tell them apart by the element's **footprint**:
  - the bite is **inside** the element's rectangle (corner eaten away, element stays within its box) → **inverted** (scoop).
  - the element's colour **extends beyond** its rectangle into the neighbour, concave where it rejoins → **outset** (flare / tab).
- **The colour var references opposite surfaces:** inverted → `--inverted-color` = the **surrounding/parent** bg (it fakes the revealed parent in the bite); outset → `--outset-color` = the **element's own** bg (the part that flares; the bite is real transparency).
- **Then read corner + axis from the actual pixels — don't assume** (e.g. a tab joins where its curve actually is, not "at the bottom"): `{corner}` = where the curve sits; outset `{axis}` = which way the colour spills past the box (**sideways → `-horizontal`**, **up/down → `-vertical`**).
- A single element can **mix** treatments (e.g. an outset `top-left-horizontal` flare + plain `border-radius` on another corner). Zoom in and verify on the real render, not the assumption.

- **A card tucked into a panel's corner — the notch is usually a plain CONVEX `border-radius`** on the card's corners that face into the panel (the panel shows *around* them, so it reads as a notch). E.g. a card in the panel's **bottom-left** rounds its **top-right + bottom-right** (its bottom-left = the composition's outer corner); keep the other edges **flush/square** so it tucks rather than floats (rounding *all* corners makes it float). Use an **outset** only when the card's colour must visibly **flare OUT** into the panel (a seamless tab) — not for an ordinary rounded card. Tell them apart on the render: a convex bump the panel wraps = `border-radius`; a tab flaring into the panel = outset.

## Complex / overlapping layouts (grid + inverted/outset corners)
For non-trivial layouts — overlapping panels, cards that straddle a panel edge, "notched" joins — reach for an **explicit CSS grid**, not flex + negative margins:
- **One grid, flat siblings.** Make the block a grid (e.g. `grid-template-columns: repeat(12, 1fr)`) and place each region as a **direct child** via `grid-column` / `grid-row`. Regions must be **siblings** in the same grid to overlap — don't bury them in wrappers.
- **Overlap by sharing a track, not by negative margins.** Put the overlapping element in the **same row** as what it overlaps and pull it with **`align-self: end`** (or `start`). Reserve negative margins for cases grid genuinely can't express.
- **Full-height panels + an overlapping wrapper.** Side-by-side panels span all rows (equal height) via `grid-row`; a wrapper coloured the **same as the page** (`brxp-surface-l-10`), with padding, overlaps their bottom and holds the cards.
- **A "notch" where a wrapper meets a panel is an OUTSET radius on the *wrapper*** (the flaring element) — NOT a radius/scoop on the panel. Pair one `-horizontal` + one `-vertical` outset for two corners (different pseudo slots); set `--outset-color` to the **wrapper's own bg**; and **zero the `border-radius` on the flaring corners** so the flare reads cleanly.
- **Reading a join:** convex corner → standard `border-radius`; concave revealing the surrounding colour → inverted *or* outset (eaten-in vs flared-out). A wrapper that overlaps a panel and "cuts into" it → **outset on the wrapper**.
- **Responsive (`@container` + `:has()`):** to stack, collapse the grid to one column and **flip which corners round** so stacked panels read as one rounded rectangle (top panel: round top, square bottom; bottom panel: square top, round bottom; `row-gap: 0`). Keep the underlying track grid (e.g. 12-col) if an overlapping child still needs to span a *fraction* of the width.
- **Cross-class `@container` overrides must out-specify the base** (`.block .block__el`, not `.block__el`) — `@container` adds no specificity, so an equal-specificity base rule living in *another* class can win on source order.
- **Build from the real render.** Read the actual pixels (which corner, which way the curve opens), verify on the frontend, and iterate — don't infer geometry from an assumed model.
