# SHADERS.md — shaders.com GPU backgrounds on Bricks/WordPress

How to add **shaders.com** GPU visual effects (animated gradient / cloud / ASCII / glass backgrounds) to a Bricks site. The system: **one shared runtime + per-instance declarative canvases**, with **independent** editor and frontend behaviour.

> **Two gotchas that waste the most time — read first:**
> 1. **Load the bundled build, never `…/js`.** The runtime imports the resolved bundle directly — `…/es2022/js/bundle.mjs` — (skips a re-export hop) with `…/js/bundle` as fallback. The plain `…/js` entry expects the host to supply `three`; a CDN pairs an incompatible `three` and the shader fails with WGSL `expected ':'` errors. See [The WGSL gotcha](#the-wgsl-gotcha).
> 2. **In a Bricks Code element, the config goes in the "PHP & HTML" field — NOT the JavaScript field.** The JS field wraps and *executes* it; the HTML field outputs the inert data block the runtime reads.

---

## The snippets to create (all via Fluent Snippets, inactive until reviewed)

Referenced **by name** — FluentSnippets renumbers the `N-…​.php` files when you reorder them in the UI, so never rely on the number.

| Snippet | Type | Role |
|---|---|---|
| **NM Bricks Allow Canvas Tag** | PHP | `add_filter('bricks/allowed_html_tags', …)` → lets a Bricks element render a real `<canvas>` |
| **NM Shader Presets** | JS (`load_as_file`) | `window.nmShaderPresets` — named preset configs keyed by slug |
| **NM Shader Runtime** | JS (`load_as_file`) | the engine: mounts tagged canvases, imports the bundle once, manages pause/freeze/resize, DPR cap, per-preset poster |
| **NM Shader Pause/Play (toolbar)** | JS (inline) | builder toolbar button → drives the editor pause |
| **NM Shader Preload** | PHP (`wp_head`) | on shader pages only: resource hints (preconnect/modulepreload) + the server-rendered load poster |

**Per instance** = a `<canvas>` behind your content + a config marker. The runtime mounts every `canvas[data-nm-shader-preset]` / `canvas[data-nm-shader-config]`. It **imports the bundle once** (shared, direct `.mjs` + fallback), **eager-warms** the import as soon as it runs if a shader is present, uses a **`MutationObserver`** (so it works in the builder canvas, which renders client-side), is idempotent, **destroys** offscreen canvases, and **re-inits on significant viewport changes** (below). *(Pin a known-good library version; validated against `shaders@2.5.129` + its bundled `three`.)*

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

⚠️ **The library writes a fixed pixel size onto the canvas.** shaders.com is three.js-based, and `three`'s `renderer.setSize()` writes inline `width`/`height` (px) **onto the `<canvas>`**. A normal CSS `width:100%/height:100%` loses to that inline style, so the canvas would **freeze at its initial size** when its container (e.g. a `100vh` section) resizes — and the library's own resize watcher then sees a canvas whose box never changes, so it never re-fits. (Symptom: an `inlineStyle: width: …px; height: …px;` that never updates.)

**The fix is one CSS rule — beat the inline style with `!important`.** The runtime injects (once, at start) a stylesheet rule:

```css
canvas[data-nm-shader-preset], canvas[data-nm-shader-config] {
  width: 100% !important;
  height: 100% !important;
}
```

An **author `!important`** declaration outranks a **normal inline** style in the cascade, so the canvas's *display* size is locked to its container and tracks it live — `three.setSize()` can keep writing inline px, but those are display-overridden (and it can't clobber a stylesheet rule the way it clobbers our own inline styles). With the box now following the container, **the library's own `ResizeObserver` (`observeElement`, on by default) re-fits the drawing buffer** on its own. No JS resize handler, no debounce, no re-init.

> **Why this works where `ResizeObserver` "didn't":** the observer was never broken — it was *starved*. The inline-px lock meant the canvas box never changed, so it never fired. Making CSS win the cascade lets the box follow the container, which feeds the observer. (`object-fit: cover` from `brxp-has-bg-media__media` covers the sub-frame moment between a resize and three repainting the buffer, so there's no stretch.) Also covers the Bricks builder: the canvas fills the resized preview, and three's observer repaints.

---

## First-render performance

The library is a **~1.6 MB three.js/WebGPU bundle**, and the dominant first-frame cost is **WebGPU adapter init + shader compile** — intrinsic, and highly variable run-to-run (esp. Windows/Chrome; the `powerPreference … ignored` console note). You can't make WebGPU compile instant; the kit shrinks the **network path** and **masks** the compile. It's all snippet-based and portable — **don't self-host the bundle** (needs a file write, and a 1.6 MB ESM module can't be a normal JS snippet either: an active JS snippet is enqueued as a classic global `<script>`, which both breaks the ESM and loads on every page).

- **Direct `.mjs` import + fallback.** The runtime's `LIB` is `…/es2022/js/bundle.mjs` (skips the `…/js/bundle` re-export hop = one fewer serial round-trip); `LIB_FALLBACK` is `…/js/bundle` if esm.sh changes its internal path.
- **Eager-warm.** As soon as the runtime runs, if a shader canvas is on the page it kicks off `import()` immediately (instead of waiting for viewport-intersection). Scoped to canvas presence, so non-shader pages download nothing.
- **DPR cap.** The library hardcodes the buffer to `rect × min(window.devicePixelRatio, 2)` with **no option** to change it, so the runtime caps what it reads for `window.devicePixelRatio` (default **1**; override with `window.nmShaderDprCap`). This only affects JS that reads DPR (canvas buffer sizing) — **not** text/CSS/image sharpness — and on a soft shader the smaller buffer is imperceptible while cutting first-frame draw and ongoing FPS.
- **NM Shader Preload snippet** (PHP, `wp_head`), emitted **only on pages whose Bricks content contains a shader canvas** (detected server-side — `get_post_meta('_bricks_page_content_2')` returns the **unserialized array**, so `json_encode()` it before `strpos`, never `is_string()`):
  - **Resource hints:** `dns-prefetch` + `preconnect` + cross-origin `modulepreload` of the bundle → starts the download during HTML parse.
  - **Server-rendered poster:** a dark radial gradient on the shader's container (`.brxp-has-bg-media:has(canvas[data-nm-shader-preset], …​[config])`), painted from first paint and covered by the shader's first frame — hides the compile gap end-to-end. Toggle via the **`$nm_poster`** constant at the top of the snippet. (Needs `:has()` — current browsers only.)
- **Per-preset JS poster.** The runtime also derives a gradient from the preset's own colours and sets it on the canvas at mount (cleared on first frame) — a per-preset colour layer on top of the server dark base.

Bottom line: compile time is unchanged (intrinsic) but no longer *visible*. For an actually-instant first frame you'd need a lighter effect (fewer components) or a non-WebGPU (CSS / canvas-2D) background.

---

## The shaders.com MCP (Pro) — getting preset configs

```bash
claude mcp add --transport http shaders https://shaders.com/mcp
```
Authenticate via `/mcp` → `shaders` → Authenticate (OAuth; local callback completes automatically). Tools: `get-user-info`, `search-presets`, `get-preset {id, format:"js"}`, component docs. The base entry of a collection is the unnumbered title (e.g. "Lost Rays" *is* #1). Separate from the site's own Novamira MCP server (`<MCP_SERVER_NAME>`).

---

## Adding a shader background

### 1. The canvas element (Bricks)
- A **Div** with **Tag = Custom, Custom tag = `canvas`** → real `<canvas>` (needs *Allow Canvas Tag* active).
- Put **`brxp-has-bg-media`** on the Section and **`brxp-has-bg-media__media`** on the canvas itself, as the Section's **last child**. The container class gives `position:relative; isolation:isolate; overflow:clip`; **`__media` is what positions the canvas** behind the content (`position:absolute; inset:0; object-fit:cover; z-index:-1000`) — so you do **not** need a custom positioning class on the canvas.
- ⚠️ **The pairing is mandatory:** if the Section has `brxp-has-bg-media` but the canvas is missing `brxp-has-bg-media__media`, the canvas is never positioned. Don't add the container class without the `__media` class on its media child.
- Attributes: `aria-hidden="true"` + one config marker below.

### 2. Config — two methods
- **A. Named preset (recommended — the default route):** canvas attr `data-nm-shader-preset="<slug>"`; add `{ components, options? }` under that slug in **NM Shader Presets** (paste the Shaders object as-is — it's valid JS, nested `children` supported). Reusable across canvases, no Code element, no signature.
- **B. Inline Code element (one-off only):** canvas attr `data-nm-shader-config="#my-cfg"`; a Bricks **Code** element with **Execute code ON / Render without wrapper ON**, content pasted into the **PHP & HTML** field:
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
- An unresolvable token logs `[nm-shader] unresolved CSS var: …` and falls back to the literal string.
- **Caveat — read once at mount.** A runtime token change (e.g. a dark-mode toggle) won't update a *live* shader unless a re-init fires.

---

## The WGSL gotcha

`Error while parsing WGSL: … expected ':' … seed_…_0.4274… : f32` — the plain `…/js` entry imports `three` externally; a CDN supplies an incompatible `three` whose WebGPU path emits invalid identifiers. **Fix:** load the **bundled** build — the runtime imports the resolved `https://esm.sh/shaders@<version>/es2022/js/bundle.mjs` directly (with `…/js/bundle` as fallback). The Windows `powerPreference … ignored` line is harmless (WebGPU active).

---

## Maintaining the snippets (data layer)

Fluent Snippets via `execute-php`:
- `Helper::createSnippet(['code'=>…, 'meta'=>…])` → **forces `status: draft`** (create inactive; user activates).
- `Helper::updateSnippet(['file_name'=>…, 'code'=>…, 'meta'=>[…full…], 'reactivate'=>false])` → updates in place; for `js`/`css` with `load_as_file:'yes'` it regenerates the cached file and bumps `updated_at` (→ new `?ver` = cache-bust). Pass the **full** meta or it resets.
- ⚠️ **OPcache.** If the host runs OPcache, after editing a PHP snippet file via the data layer call `opcache_invalidate($file, true)` **and** invalidate `…/fluent-snippet-storage/index.php` — the enqueued `?ver` is read from `index.php`, so if it's OPcache-stale the `?ver` never bumps and the browser keeps the **old** cached `.js`/`.css` even though you regenerated it (symptom: same `?ver=NNN` across edits, "my change did nothing"). The cached `.js`/`.css` files themselves aren't OPcache'd.
- ⚠️ **Snippet type matters for output.** A **`type: PHP`** snippet runs at **`setup_theme`** and **ignores `run_at`** for top-level output — so a top-level `echo`/inline HTML fires during early bootstrap (no query, breaks the page). For HTML at a hook, register inside the snippet via `add_action('wp_head', …)` (works whether the body runs at `setup_theme` or the hook), or use **`type: js`** / **`type: php_content`**. (This is why the toolbar is `type: js` and the preload snippet uses `add_action('wp_head')`.)
- `meta` notes: required `name,status,type,run_at`; `tags` is a **string**; `load_as_file:'yes'` enqueues an external file (else inline).
- Storage: `wp-content/fluent-snippet-storage/` (`index.php` + one `.php` per snippet; `cached/`).

---

## Verify / debug
- Element renders a real `<canvas …>` on the frontend → *Allow Canvas Tag* active.
- `[nm-shader] could not parse inline config` → bad config (Method B: paste into **PHP & HTML**, keep the `<script>` wrapper).
- `mount failed` + WGSL errors → not on the bundled build, or stale cache.
- Blank, no frame → globally paused **in the editor**, `prefers-reduced-motion` on the frontend, or no WebGL/WebGPU.
- **Timing logs** (optional instrumentation): `[nm-shader] lib loaded in …ms` = network/parse; `createShader to first frame: …ms` = WebGPU compile (intrinsic, noisy).
- **No poster** → `$nm_poster` off, a browser without `:has()`, or the Preload snippet inactive / OPcache-stale (see Maintaining).
- `[nm-shader] editor canvas: PAUSED/running` logs only inside the builder canvas iframe.

## Conventions
- All code via **Fluent Snippets**, created **inactive**; prefix `nm_`/`novamira-`.
- Decorative canvas: `aria-hidden`, last in source order, behind content (`z-index:-1000`).
- Pair with overlay + matching `brxp-a11y-…-text` for legibility; mask **in-shader** where hero text sits.
