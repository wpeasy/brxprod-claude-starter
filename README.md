# BRXProd Claude Starter

A reusable **Claude Code starter kit** for building WordPress sites with **Bricks Builder** + the **Bricks Wireframes** framework (`brxw-*`) + **BRXProd** (`brxp-*`), operated through a **Novamira** MCP server.

It gives Claude a consistent set of conventions (styling, naming, semantics, accessibility, layout rails, safety) plus a skill that auto-documents each site's design tokens.

## What's in here

| File | Purpose |
|---|---|
| `CLAUDE.starter.md` | The project guidance template. Copy to a site as `CLAUDE.md`, fill the placeholders, then run the skill. |
| `skills/bricks-design-tokens/SKILL.md` | A user-level Claude Code skill that reads a site's Bricks global **variables** and **classes** (`brxw-*` / `brxp-*`, `-fluid` excluded) and writes the "Design System reference" section into that site's `CLAUDE.md`. |
| `skills/add-overlay/SKILL.md` | A user-level Claude Code skill — `/add-overlay .class-name` or `/add-overlay #id` — that **merges** the standard dark-overlay scrim (relative + isolation + a `-100` z-index `::before`) onto a Bricks global class or element id, without clobbering existing CSS. Pairs with the `brxp-has-bg-media` background-image pattern. |
| `skills/init-bricks/SKILL.md` | A user-level Claude Code skill — `/init-bricks` — that asks for the site **background**, default **body text**, and default **heading** colours (favouring `brxw-`/`brxp-` tokens) and writes them to the active Bricks **Theme Style** (`general.siteBackground`, `typography.typographyBody.color`, `typography.typographyHeadings.color`). |
| `skills/add-pattern/SKILL.md` | A user-level Claude Code skill — `/add-pattern .class-name` or `/add-pattern #id` — that **merges** a decorative background-pattern layer (relative + isolation + a low-opacity `::after` with an SVG pattern + radial mask, `@abp-*` generator metadata preserved) onto a Bricks global class or element id. Sibling of `add-overlay` (`::before` scrim); the two layer together. |
| `PROFORMS.md` | Conditional companion doc — the **Bricksforge Pro Forms** build playbook. `CLAUDE.md` instructs reading it only when Pro Forms is active; copy it alongside `CLAUDE.md` on sites that may use Bricksforge. |

## Prerequisites (per site)

- **Bricks** theme + **Bricks Wireframes** framework + **BRXProd** installed.
- A connected **Novamira** MCP server for the site (the kit's tooling reads/writes through it).
- Data modelling via **Meta Box** (AIO Pro) and code via **Fluent Snippets** — or edit those rules in `CLAUDE.md` to match the site's actual stack.

## Bootstrap a new site

1. **Install the skill once** (user-level, shared across all projects):
   ```
   cp -r skills/bricks-design-tokens ~/.claude/skills/
   ```
   (Windows: copy `skills\bricks-design-tokens` into `%USERPROFILE%\.claude\skills\`.)
2. **Add the project guidance:** copy `CLAUDE.starter.md` into the site's project root as `CLAUDE.md`.
3. **Tailor it** — follow the "How to use this starter" block at the top of the file:
   - replace `<MCP_SERVER_NAME>` with the site's Novamira MCP server name;
   - confirm the Fluent Snippets / Meta Box / WP Reset / `nm-` prefix / text-domain rules match the site;
   - delete the "How to use" block.
4. **Generate the token reference:** with the site's Novamira MCP server connected, run the skill:
   ```
   /bricks-design-tokens
   ```
   It appends the site's real `brxw-*` / `brxp-*` variable + class inventory, replacing the placeholder section.
5. Re-run the skill any time the site's variables, classes, or color palette change.

## Conventions at a glance

The template encodes (see `CLAUDE.starter.md` for the full rules):

- **Code → Fluent Snippets only**, created inactive for manual review.
- **CPTs & custom fields → the field tool's UI only** (Meta Box AIO Pro by default; ACF if that's the site's tool — verify which/edition) — never `register_*` in code.
- **`nm-` / `novamira-`** namespace for project classes, CSS vars, PHP, and text domain — kept separate from the framework's `brxw-`/`brxp-`.
- **Styling:** always use `brxw-`/`brxp-` variables (never fixed values); snap to the scales; one `nm-` BEM global class per element with pretty-printed, nested CSS; `ally` text colors as the minimum contrast on colored backgrounds; `auto-fit` grids with `align-items: stretch`; `@container` (never `@media`).
- **Layout Rails** for outer/section layout via named grid lines + variables.
- **HTML semantics & accessibility** first — real lists, correct elements, a11y best practices.
- **Element labels mirror the BEM class** so the Bricks structure panel matches the CSS.

## Notes

- The rail band widths shown in the template are BRXProd **defaults**; the live values come from each site's `--brxp-*-width` variables.
- Customise the prefix/text-domain and plugin-specific rules to suit your own house style before sharing with a team.
