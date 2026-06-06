# CLAUDE.md — Bricks starter

> **Starter template** for Bricks WordPress sites running the **Bricks Wireframes** framework (`brxw-*`) + **BRXProd** (`brxp-*`), operated through a **Novamira** MCP server. Copy this to a new project as `CLAUDE.md`, then do the **Setup checklist** below.

## How to use this starter
1. **Copy** this file to the new project as `CLAUDE.md`.
2. **Fill placeholders:** replace `<MCP_SERVER_NAME>` with the site's connected Novamira MCP server name.
3. **Confirm the stack rules match the site** and edit if not:
   - Code tool = **Fluent Snippets** (change if the site uses a different snippets manager).
   - Data modelling = **Meta Box AIO Pro** (default). **Verify the site's actual field tool + edition** before relying on it — check active plugins for `meta-box-aio` / `meta-box` (Meta Box) vs `advanced-custom-fields-pro` / `advanced-custom-fields` (ACF Pro / Free), then edit the "CPTs & Custom Fields" rule to match. Change if the site uses ACF/Pods/etc.
   - **WP Reset** safety note (remove if WP Reset isn't installed).
   - Project prefix **`nm-` / `novamira-`** and text domain **`nm`** (set to this site's house prefix).
4. **Generate the design-system reference:** run the **`bricks-design-tokens`** skill to append this site's actual `brxw-*` / `brxp-*` variable + class inventory (replaces the placeholder section at the bottom).
5. Delete this "How to use" block once the file is tailored.

---

Guidance for Claude Code when working in this project.

This site is a **Bricks** WordPress build, operated through the connected Novamira MCP server (`<MCP_SERVER_NAME>`). When styling pages or building elements, prefer the design-system **variable names** and **global classes** below over hard-coded values.

The design system has two layers, and we use **only** these two:

1. **Bricks Wireframes framework** — the `brxw-*` variables and classes (installed framework).
2. **BRXProd (`brxp-*`)** — an enhancement layer built on top of Bricks Wireframes.

To regenerate the inventory below (after the site's design system changes, or when setting up a new Bricks site), run the **`bricks-design-tokens`** skill.

## Working conventions (MUST follow)

### Adding code → Fluent Snippets only
- **All code added to this site MUST go through Fluent Snippets.** Never add code any other way (no editing theme/plugin files, no `functions.php`, no `execute-php` for persistent code, no must-use plugins, no direct file writes).
- **Never activate a snippet.** Create it in an inactive/draft state and stop — the user reviews and activates every snippet manually.
- Set an appropriate snippet type/scope (PHP, CSS, JS, etc.) and a clear title/description, but leave it switched **off**.
- **Snippet hygiene:** descriptive title + a group/tag; prefix every function, hook callback, option key, and asset handle with `nm_` / `novamira-` (see Naming); **enqueue** CSS/JS via `wp_enqueue_*` (no inline `<script>`/`<style>`); use the project text domain for all strings.

### CPTs & Custom Fields → managed via the field-tool UI (never in code)
- **State the site's data-modeling tool + edition here, after verifying which is active.** Default for this stack: **Meta Box AIO (Pro, licensed)** — confirm via the active plugin `meta-box-aio/meta-box-aio.php` (Meta Box core defines `RWMB_VER`; "AIO" is the paid all-in-one, so its presence = Pro). If the site uses **ACF** instead, swap the specifics below for ACF and record its edition — **Free** (`advanced-custom-fields`) vs **Pro** (`advanced-custom-fields-pro`; adds Repeater, Flexible Content, Options Pages, Clone, etc.; `acf_get_setting('pro')` is `true`).
- **Whichever tool: all Custom Post Types, taxonomies, and custom fields MUST be created/managed through that tool's UI** — Meta Box (its registration/builder), or ACF (field groups + a CPT-registration UI / `acf-json` local JSON) — **never** via raw `register_post_type` / `register_taxonomy` / `register_meta` in code.

### Naming & namespacing
Keep *our* code/styles clearly separate from the `brxw-`/`brxp-` framework namespaces. Project prefix is **`nm-` / `novamira-`**:
- **CSS classes:** BEM blocks prefixed `nm-` (e.g. `nm-card`, `nm-card__title`, `nm-card--featured`).
- **CSS custom properties:** `--nm-…` for any project-defined variable (never reuse the `--brxw-`/`--brxp-` namespaces).
- **PHP:** prefix functions/classes/hooks/options/transients with `nm_` (e.g. `nm_register_x()`, `add_filter('nm_…')`); namespace where practical.
- **Text domain:** use a single, consistent project text domain `nm` for all i18n strings (kept distinct from the Novamira plugin's own domain).
- Never invent new `brxw-`/`brxp-` names — those belong to the frameworks and are owned by the `bricks-design-tokens` inventory.

### General
- **Always follow WordPress best-practice / coding standards** (escaping, sanitization, nonces, capability checks, hooks, i18n, naming).

### Content & copy
- **Write all titles and copy in emotive, benefit-led language from the reader's perspective.** Lead with the outcome or feeling the reader gains — not features or our point of view. Favour "you/your", active voice, and concrete, vivid benefits; avoid feature-listing, jargon, and inside-out phrasing. (e.g. *not* "BRXProd includes a layout system" → "Build pixel-perfect layouts in minutes — without fighting CSS".)

### HTML semantics & accessibility
- **Lists are always lists.** Any list of items uses `<ul>`/`<ol>` + `<li>` — never a stack of `<div>`s. (Card grids, nav, feature lists, testimonial sets, etc.)
- **Always consider accessibility best practices** — logical heading order, landmarks, `alt` text, form labels, visible focus states, keyboard operability, sufficient contrast (see the A11Y color pairing), and ARIA only when native semantics can't express it.
- **Use the most correct element for the meaning** — page regions → `<section>`/`<header>`/`<footer>`/`<nav>`/`<main>`; an image-with-caption → `<figure>`/`<figcaption>`; a real action → `<button>`, a navigation → `<a>`. Set the tag in Bricks (`tag: "custom"` + `customTag`) rather than defaulting to `<div>`.
- **Cards: a grid of cards is a list** → `<ul>` + `<li>`. If each card is *self-contained content* (a product/service card, post teaser) nest an `<article>` inside the `<li>` (`<ul><li><article>`); short feature/label items stay a plain `<li>` (no `<article>`). Reserve a standalone `<article>` for non-list contexts.
- **De-styled lists need `role="list"`.** Any list with `list-style: none` gets `role="list"` on the `<ul>`/`<ol>` — Safari + VoiceOver drop list semantics otherwise.
- **Name region landmarks with `aria-labelledby`, not `aria-label`.** A `<section>` is only a landmark when it has an accessible name; point `aria-labelledby` at the section's heading. In Bricks, give that heading an explicit id via its **`_cssId`** setting (Bricks does NOT auto-output element ids), then reference it. Don't add a redundant `aria-label` where a visible heading already names the region — no ARIA beats bad ARIA.
- **Images: set `alt` intentionally** — decorative / illustrative-beside-a-heading → `alt=""` (don't duplicate the heading); informative → a concise, meaningful `alt`.
- **Quotations → `<figure>` + `<blockquote>` + `<figcaption>`** for the attribution (`<cite>` is for a *work's* title, not a person's name).
- **Accessible names for repeated lists/regions:** when several similar lists or regions exist, give each an accessible name with **`aria-labelledby`** → its heading (preferred over a literal `aria-label`). A card `<ul>` labelled by its section heading then announces as e.g. "Services, list, 3 items".
- **First rule of ARIA — prefer native HTML.** Only reach for ARIA when no native element/attribute conveys the meaning; never add a `role` that just duplicates an element's native role, and don't override native semantics.
- **Every control needs an accessible name** — icon-only buttons/links get `aria-label` or visually-hidden text; link/button text must make sense out of context (no "click here"); avoid `href="#"` placeholders in production.
- **One `<h1>` per page; never skip heading levels** (h1 → h2 → h3 …).
- **Visible focus** — never strip focus outlines without a replacement; ensure a clear `:focus-visible` indicator (≥ 3:1 contrast) on every interactive element.
- **Decorative icons / SVGs** → `aria-hidden="true"`; meaningful ones get an accessible name (`<title>` / `aria-label`).
- **Never convey meaning by colour alone** — pair with text, icon, or shape. Non-text contrast (UI components / icons / focus indicators) ≥ 3:1.
- **Respect `prefers-reduced-motion`** — BRXProd ships a reduced-motion override in the Theme CSS; don't add motion that ignores it.
- **Forms (when added):** every field has a programmatic `<label>`; group related fields with `<fieldset>`/`<legend>`; convey validation errors as text + `aria-describedby`, not colour alone.
- **Interactive widgets → W3C ARIA Authoring Practices Guide (APG):** https://www.w3.org/WAI/ARIA/apg/patterns/ — for accordions, tabs, dialogs/modals, menus, comboboxes, carousels, disclosure, tooltips, etc. it specifies the required roles, states, and **keyboard interaction**. Consult it whenever building or customising such a widget.
  - **Native/Bricks first:** prefer a native element (`<details>/<summary>`, `<dialog>`, `<button>`) or Bricks' **built-in** interactive element (Accordion, Tabs, Slider, Off-canvas) — which should already ship the keyboard + ARIA — over hand-rolling APG markup. Reach for APG only when native/Bricks falls short, and **verify** the element actually meets the pattern (keyboard + roles). Custom widget JS goes through Fluent Snippets.
  - **Scope:** the APG is for *widgets*, not document structure. For cards / lists / landmarks / figures / headings, rely on plain HTML semantics (HTML spec / MDN / WAI tutorials), per the rules above.

### Bricks styling
- **Always use the discovered Bricks variables and classes (see the reference below) — never hard-coded/fixed values** (no literal hex, px, rem where a token exists).
- **Snap to the scales** — size with `brxw-space-*` (spacing/gaps), `brxw-text-*` (type), `brxw-radius-*` (corners), and `brxw-grid-*` (columns) rather than arbitrary values; choose the nearest scale step instead of a custom one.
- **`-m` is the default step.** When there's no specific design reason for another size, default to the `-m` variant — `var(--brxw-text-m)` (type), `var(--brxw-space-m)` (spacing), `var(--brxw-radius-m)` (corners). Default element corner radius = `var(--brxw-radius-m)`.
- **Content padding & gaps → `var(--brxw-content-gap)`.** Use it for padding and the gap between content inside a component rather than hand-picking a `space-*` step. (Section/band spacing comes from the rails + section theme padding; grid *track* gaps use `var(--brxw-grid-gap)`.)
- **Prefer semantic colors** (`brxp-primary/secondary/tertiary/surface` + `info/success/warning/danger` and their `-l-*/-d-*` ramps) for brand UI; reserve the `brxw-color-neutral-*` ramp for neutral surfaces/borders/text.
- **Background images/media → always use the `brxp-has-bg-media` pattern, never a CSS `background-image`.** Put `.brxp-has-bg-media` on the container (it sets `position:relative; isolation:isolate; overflow:clip`), then add a real Bricks **Image** (or video) with `.brxp-has-bg-media__media` **directly on it** (no wrapping div) as the container's **last child** — decorative media goes last in source order. The framework absolutely-positions it (`inset:0; object-fit:cover; z-index:-1000`) behind the content, giving a real `<img>` (lazy-load, `srcset`, `object-fit`) instead of a CSS background. **Mark it decorative:** empty `alt` + `aria-hidden="true"`. Pair with an overlay + the matching `ally` text colour for contrast. (BRXProd's CSS also supports a `.brxp-has-bg-media__media` *wrapper* containing an `<img>` via its `> img` rule, but prefer the class directly on the image.)
- **Accessible text colors (A11Y pairing):** every *solid* `brxp` color variant has a matching accessible text-color variable for use as the foreground when that color is the **background**. Build the name by inserting `ally-` after `brxp-` and appending `-text`:
  - `--brxp-primary` → text `--brxp-ally-primary-text`
  - `--brxp-primary-d-1` → text `--brxp-ally-primary-d-1-text`
  - `--brxp-surface-l-9` → text `--brxp-ally-surface-l-9-text`
  - semantic bases too: `--brxp-info` → `--brxp-ally-info-text` (likewise success/warning/danger)
  - Applies to the base, light (`-l-1…10`) and dark (`-d-1…10`) variants of primary/secondary/tertiary/surface. **Transparent (`-t-*`) variants have no `ally` pair** (transparency has no fixed contrast). Whenever you set a `brxp` color as a `background`, set the text/foreground to its matching `--brxp-ally-…-text`.
  - **Treat the `ally` color as the *minimum* contrast for text on that background.** Favour it by default; you may choose a *higher*-contrast color if the design calls for it, but never go *below* the `ally` pairing's contrast.
  - **Never set `color` directly unless one of the element's wrappers has an explicit *solid* background.** Otherwise leave it to inherit the theme (body / heading colours). When a wrapper *does* have a solid background, give its child **headings and text** that wrapper's matching `ally` text var — and set it **explicitly on headings**, because the global `h1–h6` theme heading colour overrides inheritance. **Transparent (`-t-*`) backgrounds do NOT count** as an explicit background (they have no `ally` pair and the real contrast is still the inherited surface) — let text inherit.
- **Use BEM class naming** for new classes, and **favour referencing variables** over literal values inside them.
- **Only apply existing global / utility classes where it genuinely makes sense** — don't force a global/utility class onto an element it doesn't naturally fit.
- **One global class per element.** **Every** Bricks element gets its **own `nm-` BEM global class** — *even if it has no CSS yet* (an empty global class is valid: it documents structure and is ready for future styling). The element's CSS lives **on that class**, applied through Bricks **global classes** (`_cssGlobalClasses`), **not** the plain "CSS classes" field (reserve that for framework-required hooks like `accordion-title-wrapper`), and **never** as a single page-scoped descendant-selector class. A class used on repeated elements (e.g. `nm-…__card`) is created once and applied to each.
- **Author class CSS with `%root%`**, **pretty-printed** (multi-line, 2-space indent, one declaration per line) and using **native nesting** (`&:hover`, `&:focus-visible`, nested children) rather than long descendant chains. `%root%` is the **editor's** token for the class selector — the Bricks **builder persists the literal class name** in `bricks_global_classes` (e.g. `.nm-card { … }`) and only displays it as `%root%` in the builder, transposing back on save. Both forms render identically, so data-layer writes may use either (`%root%` is fine — the builder normalises it on next save); on **read-back, expect the literal class name**, not `%root%`. Class-double (`%root%%root%`) only where you must out-specify a framework rule (e.g. a rail band beating `.brxp-rails > *`).
- **Prefer logical properties** (`padding-inline`, `margin-block`, `inset`, `*-inline-start/-end`) over physical ones — RTL-safe and consistent with the rails/gutter model.
- **Width comes from Rails (the band), not `max-width`.** Never set `max-width` / auto-margins to control a block's *layout* width — choose the appropriate rail band instead. `max-width` is acceptable **only as a typographic measure** on text (via `brxw-text-width-*` / `brxw-title-width-*` tokens), never as an ad-hoc container width.
- Native **CSS nesting** is fine (the framework uses it); keep it shallow.
- **Avoid `!important`.** If you must out-specify a framework rule, match its **class-doubling** pattern (e.g. `.nm-x.nm-x { … }`) rather than reaching for `!important`.
- **Never edit locked framework classes or the `/* BRXP_LAYOUT_RAILS_* */` theme-style block.** Extend the framework with your own `nm-` classes/variables instead.
- **Never style buttons with CSS.** Buttons inherit the **Theme Style → button element defaults**; configure them with the native Bricks **button settings** — `style` (primary/secondary/light/muted/info/success/warning/danger/dark), `size` (sm/md/lg/xl), `outline`, `circle`. Don't add background/padding/border/typography via classes. (Ghost/outline button = `style: "primary"` + `outline: true`.)
- **Don't override Theme Style element defaults.** The theme sets defaults for `typography, section, container, block, button, form, video, woocommerce-button, contextualSpacing, general`. Style these via their **native element settings / theme variants**, not by re-declaring background/padding/typography in your own CSS. Read the theme's element defaults first (Theme Styles panel, or `bricks-get-theme-style`) and extend only what's genuinely missing.
- **Grids: favour `auto-fit`** (e.g. `grid-template-columns: repeat(auto-fit, minmax(…, 1fr))`) so layouts respond without breakpoints.
- **Content grids always set `align-items: stretch`** so items in a row share equal height.
- **Use `div`, not the `block` element, for wrapper / grouping elements.** Bricks' **Block** silently ships `width: 100%`, `display: flex`, `flex-direction: column`, and a default `row-gap: var(--brxw-content-gap)` — hidden defaults you end up fighting. A **Div** is a clean slate (plain `display: block`), so *what you set is what renders*. When you need a flex/grid layout, set `display`, `flex-direction`, and the exact `gap` / `row-gap` / `column-gap` you want **explicitly** on the element's `nm-` class. (Sections for layout bands and the native nestable elements still apply as normal — this is about generic grouping wrappers.)
- **Never add section/vertical padding to a section's content wrapper** (`padding-block`, or top/bottom via `padding`, on blocks like `nm-hero`, `nm-audience`). Bricks **sections already carry vertical padding from the Theme Style** (`section` → `padding` = `var(--brxw-section-space-*)`) — let the section provide it; don't duplicate. (Self-contained visual **components** — cards, coloured panels — keep their own `padding`; that's intrinsic component spacing, not section spacing.)
- **Never use `@media` queries. Always use `@container` queries** for responsive behaviour.
- When a `@container` query needs a query container, make the **parent** an inline-size container via the `:has()` pattern below. Transpose `%root%` to the actual class name if needed:

```css
:has( > %root% ) {
  container-type: inline-size;
}
```
- **Layout Rails is our preferred OUTER-layout system** — use it for section/page-level horizontal structure (which band a block occupies + page gutters), not for laying out content inside a band (use flex/`auto-fit` grids for that). The **parent/outer container must have the `.brxp-rails` class** (that's where the grid + band names live); then prefer applying bands/gutters to its **direct children** via **named grid lines + variables on our BEM classes** (`grid-column-start/-end`, `padding-inline-start/-end: var(--brxp-page-gutter)`) over the `.brxp-rail-*` / `.brxp-gutter-*` convenience utilities. See the **Layout Rails** section below.

#### Corner utilities — inverted & outset radius (BRXProd → Wireframes Tools → Corners)
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
- Apply only where a scoop/flare genuinely fits (per the one-global-class / utility-class rules).

### Bricks build conventions
- **Build pages in Bricks, not Gutenberg** — never mix block-editor content and Bricks on the same page.
- **Reusable UI → Bricks components** (or global classes for pure styling); don't copy-paste element trees.
- **Headers / footers / archives** → Bricks **templates** scoped with **template conditions** (a template with no conditions never renders).
- **Dynamic content** → bind the field tool's data (**Meta Box** or **ACF** fields) to **Bricks dynamic data** (dynamic tags); never hardcode values that belong to a field.

#### Forms → Pro Forms when available, else Core
- **If Bricksforge is installed *and* its Pro Forms module is enabled, build all forms with Pro Forms** (it extends the core Bricks Form with advanced actions, conditional logic, multi-step, more field types). If it's not available, use the **core Bricks Form** element.
- Bricksforge is third-party — **don't build from memory**: confirm it's active + Pro Forms enabled, then discover the real element + controls via `bricks-list-elements` and verify via read-backs before relying on any setting.
- **When Pro Forms is active (element `brf-pro-forms` registered), read [`PROFORMS.md`](PROFORMS.md) before building any form** — it holds the Pro Forms element map and build playbook. Skip that file entirely when Pro Forms isn't active.
- **Field layout:** arrange field rows with a **grid + `auto-fit`** (`grid-template-columns: repeat(auto-fit, minmax(<token>, 1fr))`, gap `var(--brxw-grid-gap)`) and cap the form with a **sensible `max-width`** (snap to a `brxw-*` width / text-width token) so input lines don't get over-long; let full-width fields (textarea, submit) span all columns. No `@media` — `auto-fit` handles the reflow.

#### Element labels must mirror the BEM class
Every Bricks element's **label** is derived from its class so the structure panel matches the CSS:
- **BEM block** (root class, no `__` — e.g. `nm-hero`): label = the class **with the `nm-` prefix removed**, uppercased with `-` → space → `HERO`. (`nm-cta` → `CTA`, `nm-testimonials` → `TESTIMONIALS`, `nm-feature-grid` → `FEATURE GRID`.) The `nm-` project prefix is never shown in labels.
- **BEM element** (`block__element` — e.g. `nm-hero__title`, `nm-audience__card-title`): label = the **element segment** (the part after `__`), uppercased with `-` → space → `TITLE`, `CARD TITLE`. Modifiers (`--primary`) are not part of the label.
- **Comments** may be appended to any label inside `()`, `[]`, or `{}` — e.g. `NM TESTIMONIALS (rail-full)`, `BTN (primary)`, `GRID [auto-fit]`.
- **The block class must exist.** An `nm-<block>__element` class is only valid BEM if its block `nm-<block>` exists on an ancestor — put `nm-<block>` on the element that represents the block (commonly the **Section**, e.g. `nm-faq` on the FAQ section → label `FAQ`). **Never** leave a wrapper class-less while its descendants use `nm-<block>__*`. A comment may still be appended for context (e.g. `FAQ (rail-full)`).

### Bricks internals reference (discovered)
Useful when configuring Bricks through the Novamira abilities / data layer:
- **Custom HTML tags:** to render a non-default tag on a Block or Text element, set `tag: "custom"` + `customTag: "ul" | "li" | "blockquote" | "cite" | "article" | …`. (Heading elements take `tag: "h1"…"h6"` directly.) This is how the "correct semantics" rule above is actually applied in the element tree.
- **Element IDs & attributes:** Bricks does **not** output an HTML `id` on elements by default — set one via the element's **`_cssId`** setting when you need a stable target (`aria-labelledby`, in-page anchors). Arbitrary HTML attributes go in **`_attributes`** = `[{id, name, value}]` (e.g. `role="list"`, `aria-labelledby`).
- **`<main>` + skip link are built in:** Bricks wraps page content in `<main id="brx-content">` and outputs a "Skip to main content" link — put primary content there; never add a second `<main>`.
- **Theme Style settings nest by group key** — e.g. `settings.typography.*`, `settings.section.*`, `settings.general.*`, `settings.css.*`. Key map for common targets:
  - **Site/Page background** → `general.siteBackground` (`{color:{raw}}`); Bricks outputs it to the **`html`** element (whole-page background).
  - **Body text colour** → `typography.typographyBody.color` (`{raw}`); **all headings** → `typography.typographyHeadings.color` (`{raw}`); per-heading → `typographyHeadingH1…H6`.
  - **Theme CSS** (the editable site-wide custom CSS, where the `BRXP_LAYOUT_RAILS` block lives) → `settings.css.stylesheet`. Append to it; never overwrite.
- **Page Settings → General → "CSS classes"** → key **`bodyClasses`**, applied to the page's **`<body>`** tag (not `#brx-content`). Page-level backgrounds: `siteBackground` (page, on `html`) / `contentBackground` (`#brx-content`).
- **CSS loading mode** → option `bricks_css_loading_method`: unset/`inline` = styles emitted at render (no regeneration needed after data-layer edits); `file` = external files that must be regenerated after off-builder changes.
- **Section striping recipe:** add a toggle class to the page via `bodyClasses`, then stripe in Theme CSS — `.<class> section:nth-of-type(even) { background-color: var(--brxp-surface-t-1); }`.
- **Sourcing images:** prefer the **Instant Images** plugin (Unsplash / Pixabay / Pexels) when it's installed — pick in WP admin, then use the resulting media-library attachment. Otherwise source from **Pixabay or Unsplash**. To add one programmatically, sideload a **direct image URL** with `download_url()` + `media_handle_sideload()` using a forced `.jpg` filename and a `getimagesize()` guard (plain `media_sideload_image()` rejects extensionless / query-string URLs). Note: Instant Images' keyless proxy is gated to its own browser flow, so it can't be called from the data layer.
- **Maps:** the Bricks **Google Map** element renders a single-`address` map via a **keyless Google Maps embed iframe** (`maps.google.com/maps?q=…&output=embed`) — **no site API key required**, and it geocodes the address for you. A **Google Maps JavaScript API key** is only needed for JS-API features (multiple/dynamic markers, clustering, custom map styles). The **Leaflet** element (`map-leaflet`) is also keyless but has **no geocode** — position it by `center: "lat,lng"`. (Verified: a single-address Google map loads `google.com/maps` embed with no `maps.googleapis.com` and no key notice.)
- **Nested accordion (`accordion-nested`)** — build each item as a `div` containing two child `div`s: a **Title** `div` with `_cssClasses: "accordion-title-wrapper"` (set `_display: flex`, `_direction: row`, `_justifyContent: space-between`, `_alignItems: center`) holding a `heading` (the question) + an `icon` with `isAccordionIcon: true` (the chevron); and a **Content** `div` with `_cssClasses: "accordion-content-wrapper"` holding the answer `text`. (Use `div` per the no-Block rule — the native default uses `block`, which would add an unwanted title↔content `row-gap`.) Those two classes are **required** by the toggle JS. Style via the element's **native controls** — `title*` / `titleActive*` / `content*` (background/padding/typography/border), `transition`, `expandFirstItem`, `independentToggle` — not custom CSS; enable **`faqSchema`** for FAQ‑page structured data. The native element outputs an accessible disclosure (`role="button"` + `aria-expanded` + `aria-controls`) — don't add ARIA. Title headings default to `h3`; set the correct level (no skips) and give them the title surface's `ally` text var (the global heading colour overrides inheritance otherwise).
- ⚠️ **Builder-reload caveat:** data-layer edits (page settings, theme styles, global classes, content) made **while the Bricks builder is open** do NOT appear until the builder is **reloaded** — and **saving from a stale builder session overwrites them**. After any data-layer change, reload the builder (or verify on the frontend) before editing in-builder. The builder canvas also may not apply page `bodyClasses` (those target the real frontend `<body>`), so verify body-class / striping behaviour on the **frontend**, not the builder.

### Safety & workflow
- ⚠️ **WP Reset is active on this site.** Never trigger resets or run destructive WP-CLI / SQL / bulk-delete operations.
- **Verify via Novamira read-backs** — after a write (content, settings, classes), read it back to confirm; don't assume success.
- ⚠️ **Before editing an existing page, read its current content first** (`bricks-get-content`) and base every change on that — the user may have edited the page in the Bricks builder since it was built. **Never resend a remembered/older element tree blind** — a full `bricks-set-content` replace would wipe their edits. Prefer targeted edits (`bricks-insert-content` / `bricks-patch-elements` / `bricks-remove-content`); if you must replace a whole area, re-fetch with `bricks-get-content` immediately beforehand.
- **After any design-system change** (variables, classes, color palette), re-run the **`bricks-design-tokens`** skill to refresh the reference below.
- Treat `execute-php` as **read/inspect only** for persistent behaviour — any code that should live on the site goes through Fluent Snippets (see above).

## Layout Rails (BRXProd)

**Rails is our preferred system for OUTER layout** — i.e. section/page-level horizontal structure: how blocks sit across the page width (content band, wider bands, full-bleed) and their page gutters. Use it instead of ad-hoc max-widths, auto margins, or custom container widths on outer blocks.

Rails is **not** for laying out content *inside* a band. For internal/content layout (cards, columns, media+text, button rows, etc.) use normal flex/grid layout — favour `auto-fit` grids and `@container` queries (per the Bricks styling conventions above), not rail bands.

BRXProd adds the **Rails** layout system to the `brxwireframes` Bricks Theme Style (CSS Grid with named column lines).

> **REQUIRED — the parent must carry `.brxp-rails`.** The grid and all named lines (`content`, `wide`, `breakout`, `layout`, `full`) are defined on `.brxp-rails`. The parent/outer container (typically a Bricks **Section**) **must** have the `.brxp-rails` class, and the elements you place into bands must be its **direct children**. Without `.brxp-rails` on the parent, the band names don't exist and `grid-column-start/-end: wide-start` etc. resolve to nothing.
>
> `.brxp-rails` is the one rail utility we **do** apply directly (it *is* the grid definition, not mere convenience). What we avoid on the *children* are the `.brxp-rail-*` / `.brxp-gutter-*` utilities — those we replace with named lines + variables on our own `nm-` BEM classes (see Preferred usage below).

Direct children of `.brxp-rails` land in the **content** band by default and can be promoted outward to wider bands or full-bleed.

The grid defines symmetric, named lines forming five spannable bands (outer → inner). *(Widths below are the default BRXProd values; the live values come from the site's `--brxp-*-width` variables.)*

| Band | Named lines (`*-start` / `*-end`) | Width source | Default |
|---|---|---|---|
| `full` | `full-start` / `full-end` | edge-to-edge | 100vw (bleeds) |
| `layout` | `layout-start` / `layout-end` | `--brxp-layout-width` | 140rem |
| `breakout` | `breakout-start` / `breakout-end` | `--brxp-breakout-width` | 120rem |
| `wide` | `wide-start` / `wide-end` | `--brxp-wide-width` | 100rem |
| `content` *(default)* | `content-start` / `content-end` | `--brxp-content-width` | 80rem |

**Responsive with no breakpoints:** bands share a `calc(100vw - 2*--brxp-page-gutter)` ceiling, so as the viewport narrows the widest band collapses inward (layout → breakout → wide → content) and they converge — only `full` keeps bleeding. Don't add `@media` to override it.

### Preferred usage — variables on our BEM classes (Advanced Usage)

The convenience utilities (`.brxp-rail-content/-wide/-breakout/-layout/-full`, `.brxp-gutter-x/-left/-right`) exist for quick composition, but **we favour applying the rail behaviour directly on our own BEM classes using the named lines and variables** (this is the "Advanced Usage" tab of the Rails docs). The element must be a **direct child** of the `.brxp-rails` grid.

- **Place into a band** with the named lines (do NOT add the `.brxp-rail-*` utility):

  ```css
  /* e.g. .nm-hero__media spanning the wide band */
  grid-column-start: wide-start;
  grid-column-end: wide-end;
  ```

- **Gutters** via logical padding + the gutter variable (instead of `.brxp-gutter-*`):

  ```css
  padding-inline-start: var(--brxp-page-gutter);
  padding-inline-end: var(--brxp-page-gutter);
  ```

So a BEM block opts itself into a band/gutter through its own class rules and variables — reserve the `.brxp-rail-*` / `.brxp-gutter-*` utilities for quick prototyping or one-offs where a dedicated class isn't warranted.

## Bricks Design System reference — `<MCP_SERVER_NAME>`

> **PLACEHOLDER — generated per site.** Run the **`bricks-design-tokens`** skill against this site's connected Novamira MCP server to replace this section with the site's actual `brxw-*` / `brxp-*` variable and global-class inventory (names only, `*-fluid` excluded). Until then, treat the live Bricks Style Manager as the source of truth and confirm names before use.
