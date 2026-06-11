# SHADERS.md — shaders.com GPU backgrounds on Bricks/WordPress

How to add **shaders.com** GPU visual effects (animated gradient / cloud / ASCII / glass backgrounds) to a Bricks site. The system: **one shared runtime + per-instance declarative canvases**, with **independent** editor and frontend behaviour.

> **Two gotchas that waste the most time — read first:**
> 1. **Load the `…/js/bundle` CDN build, not `…/js`.** The default entry expects the host to provide `three`; a CDN pairs it with an incompatible `three` and the shader fails with WGSL `expected ':'` errors. See [The WGSL gotcha](#the-wgsl-gotcha).
> 2. **In a Bricks Code element, the config goes in the "PHP & HTML" field — NOT the JavaScript field.** The JS field wraps and *executes* it; the HTML field outputs the inert data block the runtime reads.

---

## The snippets to create (all via Fluent Snippets, inactive until reviewed)

| Snippet | Type | Role |
|---|---|---|
| **NM Bricks Allow Canvas Tag** | PHP | `add_filter('bricks/allowed_html_tags', …)` → lets a Bricks element render a real `<canvas>` |
| **NM Shader Presets** | JS (`load_as_file`) | `window.nmShaderPresets` — named preset configs keyed by slug |
| **NM Shader Runtime** | JS (`load_as_file`) | the engine: mounts tagged canvases, loads the bundle once, manages pause/freeze/resize |
| **NM Shader Pause/Play (toolbar)** | JS (inline, no gating) | builder toolbar button → drives the editor pause |

**Per instance** = a `<canvas>` behind your content + a config marker. The runtime mounts every `canvas[data-nm-shader]` / `canvas[data-nm-shader-config]`. It **loads `https://esm.sh/shaders@<version>/js/bundle` once** (shared), uses a **`MutationObserver`** (so it works in the builder canvas, which renders client-side), is idempotent, **destroys** offscreen canvases, and **re-inits on significant viewport changes** (below). *(Pin a known-good library version; this was validated against `shaders@2.5.129` + its bundled `three@0.184`.)*

---

## Editor vs frontend — two INDEPENDENT controls

The runtime runs both **inside the Bricks builder canvas iframe** and on the **real frontend**. Tell them apart by **iframe-ness** — `window.self !== window.top` — **not** the `bricks_preview` query param (Bricks adds that to its "View on frontend" links too, so it leaks). The two controls do not share effective state:

| | Editor (builder canvas iframe) | Frontend (top-level page) |
|---|---|---|
| **Pause** (toolbar / `window.nmShaders`) | **applies** — performance control while editing | **ignored** |
| **`prefers-reduced-motion`** | ignored (you still see motion while designing) | **applies** — freezes the first frame |

- **Editor pause** uses the library's native **`shader.pause()` / `resume()`** → freezes the current frame (keeps the image, halts the loop, instant resume). **On load while paused**, it mounts, paints the first frame (`onReady`), then pauses → a frozen frame, not a blank canvas. Offscreen canvases are `destroy()`d to free the GPU.
- **Frontend reduced-motion** mounts, paints the first frame, then `pause()` → a **static frozen frame** (not hidden).

### Pause controls (editor)
- **Console:** `window.nmShaders.pause() / .resume() / .toggle()`, plus `.paused` / `.count`. The editor pause flag persists in `localStorage` (read only inside the canvas iframe), so it survives builder reloads.
- **Toolbar button:** a **`type: js`** snippet (inline at `wp_footer`, no gating) waits for `ul.group-wrapper.start` via a `MutationObserver`, appends one `<li>` (styled with `bricks-svg-wrapper`/`bricks-svg` + a `data-balloon` tooltip), and from the builder's parent window reaches into the canvas iframe — `document.getElementById('bricks-builder-iframe').contentWindow.nmShaders` (fallback: any iframe whose `src` matches `bricks_preview`) — to toggle. ⚠️ It must be **`type: js`**, not `type: PHP` (see the snippet-type gotcha under Maintaining).

---

## Viewport / resize behaviour

Once mounted, a shader won't reliably re-fit a changed viewport on its own. The runtime adds a debounced `resize`/`orientationchange` handler:
- **Significant change** (width changed, or height changed by more than `max(150px, 20%)`) → **re-init** each shader (destroy → recreate) so all parameters recompute for the new dimensions (frozen ones re-freeze the new first frame).
- **Minor height jitter** (mobile URL bar show/hide during scroll) → lightweight `shader.resize()`, no flash.

---

## The shaders.com MCP (Pro) — getting preset configs

```bash
claude mcp add --transport http shaders https://shaders.com/mcp
```
Authenticate via `/mcp` → `shaders` → Authenticate (OAuth; local callback completes automatically). Tools: `get-user-info`, `search-presets`, `get-preset {id, format:"js"}`, component docs, `shaders://guidelines` + the `hero-section-masking` Pro Note. Separate from the site's own Novamira MCP server (`<MCP_SERVER_NAME>`).

---

## Adding a shader background

### 1. The canvas element (Bricks)
- A **Div** with **Tag = Custom, Custom tag = `canvas`** → real `<canvas>` (needs *Allow Canvas Tag* active).
- A global class placing it behind content, e.g. `nm-hero-shader`: `position:absolute; inset:0; width:100%; height:100%; display:block; z-index:-1000; pointer-events:none;`
- Make it the **last child** of a Section with **`brxp-has-bg-media`** (provides `relative; isolation; overflow:clip`).
- Attributes: `aria-hidden="true"` + one config marker below.

### 2. Config — two methods
- **A. Named preset (recommended):** canvas attr `data-nm-shader="<slug>"`; add `{ components, options? }` under that slug in **NM Shader Presets** (paste the Shaders object as-is — it's valid JS).
- **B. Inline (one-off):** canvas attr `data-nm-shader-config="#my-cfg"`; a Bricks **Code** element with **Execute code ON / Render without wrapper ON**, content pasted into the **PHP & HTML** field:
  ```html
  <script type="application/json" class="nm-shader-config" id="my-cfg"> …config… </script>
  ```
  `id` must match. Bricks signs the Code element on save (`wp_hash`); on "Invalid signature" → Bricks → Settings → Regenerate code signatures. Code Execution must be enabled in Bricks settings.

### 3. What to paste (tolerant — no JSON conversion)
Inside the `<script>` (or under a registry slug) you may paste **the whole shaders.com copy verbatim** (`import …` + `createShader(…)`), **just the object** `{ components: […] }` (+ optional options), or **strict JSON**. The runtime strips the `import`, captures the config via a `createShader` shim (incl. 3rd-arg `options` like `{ colorSpace: 'srgb' }`), and mounts it. Keep every component `id`; keep `type="application/json"`.

### 4. Using design-system tokens — CSS `var(--…)` in the config
Any config value may reference a Bricks/BRXProd token with normal CSS `var()` syntax. The runtime **resolves it against the canvas's computed styles just before mounting**, so the shader tracks the brand palette instead of hardcoded hex:
```js
{ id: 'gradient', props: { colorBack: 'var(--brxp-primary)', colorTint: 'var(--brxp-secondary-l-3)' } }
```
- **Colours** are normalized to a value the library parses (hex / `rgb()`); **numeric** tokens become numbers; `var(--x, fallback)` and nested-`var()` fallbacks are honoured.
- Resolved **per (re)mount**, so a re-init (e.g. on viewport resize) re-reads the *current* values. The **source config keeps the `var()` strings** untouched.
- An unresolvable token (typo / not in scope on the canvas) logs `[nm-shader] unresolved CSS var: …` and falls back to the literal string.
- **Caveat — read once at mount.** A runtime token change (e.g. a dark-mode toggle) won't update a *live* shader unless a re-init fires. Add a theme-change re-resolve hook if you switch themes at runtime.

---

## The WGSL gotcha

`Error while parsing WGSL: … expected ':' … seed_…_0.4274… : f32` — the default `…/js` entry imports `three` externally; a CDN supplies an incompatible `three` whose WebGPU path emits invalid identifiers. **Fix:** load the self-contained `https://esm.sh/shaders@<version>/js/bundle` (the runtime does). The Windows `powerPreference … ignored` console line is harmless (WebGPU active).

---

## Maintaining the snippets (data layer)

Fluent Snippets via `execute-php`:
- `Helper::createSnippet(['code'=>…, 'meta'=>…])` → **forces `status: draft`** (create inactive; user activates).
- `Helper::updateSnippet(['file_name'=>…, 'code'=>…, 'meta'=>[…full…], 'reactivate'=>false])` → updates in place; for `js`/`css` with `load_as_file:'yes'` it regenerates the cached file and bumps `updated_at` (→ new `?ver` = cache-bust). Pass the **full** meta or it resets.
- ⚠️ **Snippet type matters for output.** A **`type: PHP`** snippet with no condition is `require_once`'d at **`setup_theme`** (very early) and its **`run_at` is ignored** — so any top-level `echo` / inline `?>…<?php` HTML fires during early bootstrap and **breaks the page/builder**. For HTML/JS output use **`type: js`** (or **`type: php_content`** with `run_at` = `wp_head`/`wp_footer`/`wp_body_open`), which Fluent runs at the right hook. (This is why the toolbar button is `type: js`.)
- `meta` notes: required `name,status,type,run_at`; `tags` is a **string**; `load_as_file:'yes'` enqueues an external file (else inline).
- Storage: `wp-content/fluent-snippet-storage/` (`index.php` + one `.php` per snippet; `cached/`).

---

## Verify / debug
- Element renders a real `<canvas …>` on the frontend → *Allow Canvas Tag* active.
- `[nm-shader] could not parse inline config` → bad config (Method B: paste into **PHP & HTML**, keep the `<script>` wrapper).
- `mount failed` + WGSL errors → not on `…/js/bundle`, or stale cache (bump version).
- Blank, no frame → globally paused **in the editor**, `prefers-reduced-motion` on the frontend, or no WebGL/WebGPU.
- `[nm-shader] editor canvas: PAUSED/running` logs only inside the builder canvas iframe.

## Conventions
- All code via **Fluent Snippets**, created **inactive**; prefix `nm_`/`novamira-`.
- Decorative canvas: `aria-hidden`, last in source order, behind content (`z-index:-1000`).
- Pair with overlay + matching `brxp-a11y-…-text` for legibility; mask **in-shader** (Pro Note) where hero text sits.
