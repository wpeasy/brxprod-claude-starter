---
name: bricks-design-tokens
description: Extract every Bricks global CSS variable name (excluding internal `*-fluid` aliases) and every Bricks global class name from a Novamira-connected Bricks WordPress site, then write/refresh the "Bricks Design System reference" inventory into the project's BRICKS-TOKENS.md (an on-demand reference linked from CLAUDE.md). Use when onboarding a new Bricks site, or whenever the site's variables/classes/color palette change. Requires a connected Novamira MCP server for the target site.
---

# Bricks Design Tokens → BRICKS-TOKENS.md

Generate a **names-only** inventory of a Bricks site's design system (global CSS variables + global classes) and record it in the project's `BRICKS-TOKENS.md` (an on-demand reference linked from `CLAUDE.md`), so future styling work reuses the site's tokens instead of hard-coded values.

> **Why a separate file:** the full enumeration is large and only needed to confirm an exact token name. `CLAUDE.md` keeps a compact *naming-patterns cheat-sheet* + a pointer to `BRICKS-TOKENS.md`; this skill writes the exhaustive list into `BRICKS-TOKENS.md` so it loads on demand, not on every session. **Do not** paste the full list back into `CLAUDE.md`.

## Prerequisites

- A connected **Novamira MCP server** for the target site (tools: `mcp-adapter-discover-abilities`, `mcp-adapter-get-ability-info`, `mcp-adapter-execute-ability`).
- If several Novamira servers are connected, confirm which site the inventory is for, and label the `BRICKS-TOKENS.md` section with that server name.

## Rules

1. **Scope to the `brxw-*` and `brxp-*` prefixes only.** Exclude Bricks core `bricks-color-*`, scale/config vars (`base-font`, `clamp-unit`, `min-viewport`, `max-viewport`, `shadow-primary`), and any non-prefixed global classes (component/showcase classes like `color-showcase*`, `new-section*`, `spacing-showcase*`, `typography-list*`).
2. **Names only** — never include variable values or class CSS in the output.
3. **Exclude every variable whose name contains `-fluid`.** These are internal scale primitives referenced only by their non-fluid counterparts (e.g. `--brxw-text-m: var(--brxw-text-fluid-m)`). Always surface the non-fluid name.
4. **Include color-palette-generated variables.** The brand color ramps (e.g. `brxp-primary-l-1 … -l-10`, `-d-1 … -d-10`, `-t-1 … -t-10`) are NOT in the Bricks global-variables store — they are emitted by the framework and only appear via the color palette. You must merge them in (they are `brxp-*`, so in scope).
5. Sort names alphabetically and de-duplicate.

## Procedure

### 1. Pull the canonical data

The most reliable single source is the Bricks options table, read via the `novamira/execute-php` ability. Call `mcp-adapter-execute-ability` with `ability_name: "novamira/execute-php"` and this `parameters.code` (it returns the fully formatted markdown body, already filtered/sorted/grouped):

```php
$gv = get_option('bricks_global_variables', []);
$varNames = [];
foreach ((array)$gv as $v){ if(!empty($v['name'])) $varNames[$v['name']]=true; }
$pal = get_option('bricks_color_palette', []);
foreach((array)$pal as $p){ foreach((array)($p['colors']??[]) as $c){ if(!empty($c['raw']) && preg_match('/var\(\s*--([A-Za-z0-9_-]+)\s*\)/',$c['raw'],$m)) $varNames[$m[1]]=true; } }
$vars = array_values(array_filter(array_keys($varNames), function($n){ return strpos($n,'-fluid')===false; }));
sort($vars, SORT_STRING);
$gc = get_option('bricks_global_classes', []);
$classes=[];
foreach((array)$gc as $c){ if(!empty($c['name'])) $classes[]=$c['name']; }
$classes=array_values(array_unique($classes)); sort($classes, SORT_STRING);
$grp=function($a,$pre){return array_values(array_filter($a,function($n)use($pre){return strpos($n,$pre)===0;}));};
$j=function($a){return implode(', ', $a);};
$brxw=$grp($vars,'brxw-'); $brxp=$grp($vars,'brxp-');
$cbrxw=$grp($classes,'brxw-'); $cbrxp=$grp($classes,'brxp-');
$nv=count($brxw)+count($brxp); $nc=count($cbrxw)+count($cbrxp);
$md ="**Totals:** {$nv} variables, {$nc} global classes (`brxw-*` and `brxp-*` only).\n\n### CSS Variables ({$nv})\n\n";
$md.="**Bricks Wireframes framework `brxw-*`** (".count($brxw).")\n\n```\n".$j($brxw)."\n```\n\n";
$md.="**BRXProd enhancement layer `brxp-*`** (".count($brxp).")\n\n```\n".$j($brxp)."\n```\n\n";
$md.="### Global Classes ({$nc})\n\n";
$md.="**`brxw-*`** (".count($cbrxw).")\n\n```\n".$j($cbrxw)."\n```\n\n";
$md.="**`brxp-*`** (".count($cbrxp).")\n\n```\n".$j($cbrxp)."\n```\n";
return $md;
```

> Fallback (if `execute-php` is unavailable or the option keys differ): call `novamira/bricks-list-variables` and `novamira/bricks-list-color-palette` (parse `var(--NAME)` out of each color's `raw`) and `novamira/bricks-list-global-classes`, then apply Rules 1–4 yourself.
>
> **In-scope prefixes** (everything else is excluded per Rule 1):
> - `brxw-*` — the **Bricks Wireframes framework** (an installable framework). Absent if Wireframes isn't installed.
> - `brxp-*` — **BRXProd**, an enhancement layer built on top of Bricks Wireframes. Absent if BRXProd isn't installed.
>
> A site may have either, both, or neither (e.g. Wireframes only → no `brxp-*`; plain Bricks → neither, so the inventory is empty and that's correct to report). Bricks core `bricks-color-*` and non-prefixed classes are intentionally out of scope.

### 2. Write BRICKS-TOKENS.md

Take the returned markdown body and wrap it as the contents of the project's **`BRICKS-TOKENS.md`** (create the file if missing; otherwise replace the existing "Bricks Design System reference" section in place — do not duplicate it):

```markdown
## Bricks Design System reference — `<server-name>`

> Auto-generated inventory of the Bricks global CSS variables and global classes. Names only; `*-fluid` aliases excluded (use the non-fluid name). Regenerate with the `bricks-design-tokens` skill when the design system changes. *(On-demand reference; `CLAUDE.md` keeps the patterns cheat-sheet + a pointer here.)*

<MARKDOWN BODY FROM STEP 1>
```

Leave `CLAUDE.md` untouched **except** to confirm it still has the design-system pointer + the naming-patterns cheat-sheet (do not inline the full list there). If `CLAUDE.md` still contains a full inline inventory (older projects), replace it with the cheat-sheet + a pointer to `BRICKS-TOKENS.md`.

### 3. Confirm

Report the totals (variable count, class count) and the path written (`BRICKS-TOKENS.md`), so the user can sanity-check against the live site.
