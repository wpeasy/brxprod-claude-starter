---
name: init-bricks
description: Initialise a Bricks site's base colours — ask for the site background, default body text, and default heading colour, then write them to the active Bricks Theme Style (`general.siteBackground`, `typography.typographyBody.color`, `typography.typographyHeadings.color`) via the connected Novamira MCP. Use when the user runs `/init-bricks`, or asks to set up / initialise the site's base theme colours (background + text + headings). Requires a connected Novamira MCP server.
---

# Initialise Bricks base colours

Collect three foundational colours and apply them to the site's **Theme Style** so every page inherits them — background on `html`, body text, and all headings (`h1–h6`). These are the inherited defaults the rest of the project relies on (per `CLAUDE.md`: don't set `color` on elements unless a wrapper has a solid background — let it inherit *these*).

## What to ask
Ask all three together (one `AskUserQuestion` call, three questions). **Favour design-system tokens** over raw hex (project convention: never a fixed value where a token exists) — build the options from the site's `brxw-*`/`brxp-*` inventory in `CLAUDE.md`, and always leave room for a custom value:

1. **Site background colour** — e.g. `var(--brxp-surface)` *(recommended)*, `var(--brxp-surface-l-1)`, `#ffffff`, or custom.
2. **Default text (body) colour** — e.g. `var(--brxp-ally-surface-text)` *(recommended — the accessible pairing for the chosen surface)*, `var(--brxw-color-neutral-900)`, or custom.
3. **Default heading colour** — e.g. `var(--brxp-primary)`, `var(--brxw-color-neutral-950)`, match-body, or custom.

When the user picks a background, **recommend its `ally` pairing** for the text colour (e.g. `--brxp-surface` → `--brxp-ally-surface-text`) so the defaults meet contrast out of the box. Accept a raw hex if the user insists, but if it maps to an existing token, suggest the token.

## Theme Style key map (what we apply)
Theme Style settings nest by group key. Set, **merging into existing settings** (never replace the whole style):

| Target | Key path (under the style's `settings`) | Value shape | Renders on |
|---|---|---|---|
| Site/page background | `general.siteBackground` | `{ "color": { "raw": "<bg>" } }` | `html` (whole page) |
| Default body text | `typography.typographyBody.color` | `{ "raw": "<text>" }` | body text |
| Default headings (h1–h6) | `typography.typographyHeadings.color` | `{ "raw": "<heading>" }` | all headings |

(Per-heading overrides live at `typography.typographyHeadingH1…H6` — not set here.)

## Procedure
1. **Prereq:** a connected Novamira MCP server (`mcp-adapter-execute-ability`). If several sites are connected, confirm which one.
2. **Find the target Theme Style.** A site can have several. Read them and pick the **site-wide / default** style (commonly the framework one, e.g. `brxwireframes`, or the style with no display conditions). If it's ambiguous, list them (id + label) and ask which to initialise. Discover with:
   ```php
   $styles = get_option('bricks_theme_styles', []);
   $out = [];
   foreach ((array)$styles as $id => $st) { $out[] = ['id'=>$id, 'label'=>$st['label'] ?? '', 'keys'=>array_keys($st['settings'] ?? [])]; }
   return $out;
   ```
3. **Ask the three questions** (see above) and confirm the final values back to the user.
4. **Apply (read-modify-write, merge).** Replace `<STYLE_ID>` / `<BG>` / `<TEXT>` / `<HEADING>`:
   ```php
   $styles = get_option('bricks_theme_styles', []);
   $id = '<STYLE_ID>';
   if (!isset($styles[$id])) return ['error'=>'style not found','available'=>array_keys((array)$styles)];
   $s = $styles[$id]['settings'] ?? [];
   $s['general'] = $s['general'] ?? [];
   $s['general']['siteBackground'] = ['color'=>['raw'=>'<BG>']];
   $s['typography'] = $s['typography'] ?? [];
   $s['typography']['typographyBody'] = $s['typography']['typographyBody'] ?? [];
   $s['typography']['typographyBody']['color'] = ['raw'=>'<TEXT>'];
   $s['typography']['typographyHeadings'] = $s['typography']['typographyHeadings'] ?? [];
   $s['typography']['typographyHeadings']['color'] = ['raw'=>'<HEADING>'];
   $styles[$id]['settings'] = $s;
   update_option('bricks_theme_styles', $styles);
   return ['ok'=>true,'siteBackground'=>$s['general']['siteBackground'],'body'=>$s['typography']['typographyBody']['color'],'headings'=>$s['typography']['typographyHeadings']['color']];
   ```
   (Prefer a dedicated Novamira theme-style write ability if one is available — discover with `mcp-adapter-discover-abilities`; otherwise the `execute-php` read-modify-write above is reliable.)
5. **Verify.** Read the option back (confirm the three values are stored) and fetch a frontend page — check the generated CSS sets the `html` background, body `color`, and `h1` `color` to the chosen values.
6. **CSS loading caveat.** If `get_option('bricks_css_loading_method')` is `file` (external CSS files), the change won't show until Bricks regenerates its CSS — trigger a regeneration (or tell the user to). If it's unset/`inline`, styles emit at render and no regeneration is needed.

## Notes
- Follow `CLAUDE.md`. These three are the **inherited theme defaults**; everything else should inherit from them rather than re-declaring `color`/background.
- **Builder-reload caveat:** if the Bricks builder is open, reload it before editing — and don't save from a stale builder session (it would overwrite these). Verify on the **frontend**.
- This only sets the base trio. Section striping, per-element colours, and component surfaces are handled elsewhere (Theme CSS / element settings / `nm-` classes).
