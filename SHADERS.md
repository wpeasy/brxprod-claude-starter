# SHADERS.md — shaders.com GPU backgrounds on Bricks/WordPress

How to add **shaders.com** GPU visual effects (animated gradient / cloud / ASCII / glass backgrounds) to a Bricks WordPress site. The recipe sets up a small, reusable system: **one shared runtime + per-instance declarative canvases**.

> **Two gotchas that waste the most time — read these first:**
> 1. **Load the `…/js/bundle` CDN build, not `…/js`.** The default entry expects the host app to provide `three`; a CDN pairs it with an incompatible `three` and the shader fails with WGSL `expected ':'` errors. See [The WGSL gotcha](#the-wgsl-gotcha).
> 2. **In a Bricks Code element, the config goes in the "PHP & HTML" field — NOT the JavaScript field.** The JS field wraps and *executes* it; the HTML field outputs the inert data block the runtime reads. See [Method B](#method-b--inline-config-via-a-bricks-code-element).

---

## Architecture — the snippet set to create

Three Fluent Snippets + a per-instance declarative marker. Create them **inactive** (per the project conventions); the user reviews and activates. The heavy library loads **once per page**, shared across all instances.

| Snippet | Type | Role |
|---|---|---|
| **NM Bricks Allow Canvas Tag** | PHP | `add_filter('bricks/allowed_html_tags', …)` → lets a Bricks element render a real `<canvas>` |
| **NM Shader Presets** | JS (`load_as_file`) | `window.nmShaderPresets` — named preset configs keyed by slug |
| **NM Shader Runtime** | JS (`load_as_file`) | generic runtime: finds tagged canvases, loads the bundle once, mounts each |

**Per instance** = a `<canvas>` behind your content + a config. The runtime mounts every `canvas[data-nm-shader]` / `canvas[data-nm-shader-config]` on the page. It:
- **loads `https://esm.sh/shaders@<version>/js/bundle` once** (a shared singleton dynamic `import()`) for any number of instances;
- uses a **`MutationObserver`**, so it mounts canvases that appear/re-render **after** load — which is why it works in the **Bricks builder canvas** (Bricks renders elements client-side there), not just the static frontend;
- is **idempotent** and **cleans up** (`shader.destroy()`) when a canvas is removed;
- applies guards: **`prefers-reduced-motion`** (no shader), a **WebGL capability** check (graceful fallback), and a **per-canvas `IntersectionObserver`** (pauses off-screen / on tab-hide).

> Pin a known-good library version (this recipe was validated against `shaders@2.5.129` + its bundled `three@0.184`); bump deliberately.

---

## Pause / resume from the console

Running shaders in the **Bricks builder** (or any heavy page) costs GPU. The runtime exposes a control on `window`:

```js
window.nmShaders.pause();   // destroy all live shaders (frees GPU, stops render loops)
window.nmShaders.resume();  // re-mount the in-view shaders
window.nmShaders.toggle();
window.nmShaders.paused;    // current state (boolean)
window.nmShaders.count;     // number of shader canvases tracked
```

The paused state is stored in `localStorage`, so it **persists across builder reloads** — pause once while editing and it stays paused until you `resume()`. It's per-browser (affects only your machine, not visitors). In the builder canvas a one-line console hint reminds you the control exists.

### Builder toolbar button (optional)

A small Fluent Snippet (**builder-only**, gated to `?bricks=run`) adds a Pause/Resume icon as the **last `<li>` in the Bricks toolbar's `ul.group-wrapper.start`**, so you can toggle without opening the console. Recipe:

- **Type PHP, `run_at: wp_footer`, created inactive.** First line guards the context: `if (empty($_GET['bricks']) || $_GET['bricks'] !== 'run') return;` — then it prints an inline `<script>` (builder *chrome*; it never reaches the live frontend).
- The script waits for `ul.group-wrapper.start` via a `MutationObserver` (the toolbar mounts after page load), then appends one `<li>` styled with the toolbar's `bricks-svg-wrapper` / `bricks-svg` classes + a `data-balloon` tooltip so it matches the native buttons.
- **Cross-window:** the toolbar is in the builder's *parent* window, but `nmShaders` lives in the **canvas iframe** — reach it via `document.getElementById('bricks-builder-iframe').contentWindow.nmShaders` (fall back to any `iframe` whose `src` matches `bricks_preview`), then call `.pause()` / `.resume()`.
- **State + icon:** read/write the same `localStorage` key (`nmShadersPaused`). It's the same origin as the iframe, so state stays in sync and survives canvas reloads; swap the icon (pause bars ↔ play triangle) based on that key.

## The shaders.com MCP (Pro) — getting preset configs

Connect as an HTTP MCP server:
```bash
claude mcp add --transport http shaders https://shaders.com/mcp
```
Authenticate via `/mcp` → `shaders` → Authenticate (OAuth; the local callback completes automatically). Reload so the tools load.

Useful tools/resources: `get-user-info` (confirm `isPro`), `search-presets`, `get-preset {id, format:"js"}`, component docs, and `shaders://guidelines` + the `hero-section-masking` Pro Note (required reading when a shader sits behind hero text — mask **in-shader**, never CSS `mask-image`/`clip-path`). This is separate from the site's own Novamira MCP server (`<MCP_SERVER_NAME>`).

---

## Adding a shader background — step by step

### 1. The canvas element (Bricks)
- Add a **Div**; set **Tag = Custom**, **Custom tag = `canvas`** → renders a real `<canvas>` (needs the *Allow Canvas Tag* snippet active; otherwise Bricks falls back to `<div>`).
- Give it a global class that places it **behind content**, e.g. `nm-hero-shader`:
  ```css
  .nm-hero-shader {
    position: absolute; inset: 0; width: 100%; height: 100%;
    display: block; z-index: -1000; pointer-events: none;
  }
  ```
- Make it the **last child** of a Section carrying the **`brxp-has-bg-media`** class (that provides `position:relative; isolation:isolate; overflow:clip`).
- Canvas **Attributes**: `aria-hidden = true` (decorative), plus **one** of the config markers below.

### 2. Pick a config method

#### Method A — named preset (recommended; reusable, no signature)
- Canvas attribute: **`data-nm-shader = <slug>`**.
- Add the config to the **NM Shader Presets** snippet, keyed by slug. Paste the Shaders object **as-is** (it's valid JavaScript — no conversion):
  ```js
  window.nmShaderPresets = Object.assign(window.nmShaderPresets || {}, {
    'my-effect': {
      // optional: options: { colorSpace: 'srgb' },
      components: [ /* paste the Shaders `components` array, single quotes & all */ ]
    }
  });
  ```
- Use for anything reused across the site. Two canvases with the same slug share one config.

#### Method B — inline config via a Bricks Code element (one-offs)
- Canvas attribute: **`data-nm-shader-config = #my-shader-cfg`**.
- Add a Bricks **Code** element. Settings: **Execute code = ON**, **Render without wrapper = ON**.
- ⚠️ **Paste into the "PHP & HTML" field — NOT the JavaScript field, NOT CSS.** The JS field would wrap and execute the content; the HTML field outputs it as inert data.
- Content (the `<script>` wrapper is required; `type="application/json"` keeps the browser from executing it — the runtime reads it):
  ```html
  <script type="application/json" class="nm-shader-config" id="my-shader-cfg">
  ...paste your shaders.com config here (see "What to paste")...
  </script>
  ```
- `id` must match the canvas's `data-nm-shader-config="#…"`.
- **Signature:** Bricks signs the Code element on save (signature = `wp_hash(code)`). If the frontend shows **"Invalid signature"** (e.g. after a site migration, rotating `AUTH` salts, or editing the code outside the builder), go to **Bricks → Settings → Regenerate code signatures**. Code Execution must be enabled in Bricks settings.

### 3. What to paste (the runtime is tolerant — no JSON conversion)
Inside the `<script>` (Method B) or under a slug (Method A) you can paste **any** of:
- **(a)** the **whole shaders.com copy verbatim** — `import …` + `const shader = await createShader(document.getElementById("canvas"), { … }, { …options })`;
- **(b)** just the object: `{ components: [ … ] }` (+ optional options object);
- **(c)** strict JSON.

The runtime strips the `import`, intercepts `createShader` with a shim to capture the config (incl. a 3rd-arg `options` like `{ colorSpace: 'srgb' }`), and mounts it. **No need** to convert quotes, quote keys, or remove trailing commas. **Keep every component `id`** (stable GPU identifiers + `shader.update()`), and **keep `type="application/json"`**.

> The eval (`Function()`) only runs on content from a **signed, Code-Execution-gated** Bricks Code element (admin-authored) or the registry — not on visitor input.

---

## The WGSL gotcha

**Symptom:** console fills with `Error while parsing WGSL: … expected ':' for struct member  seed_FlowingGradient_<ts>_0.4274… : f32`. The `_<timestamp>_<random>` suffix contains a `.` (from `Math.random()`), illegal in a WGSL identifier.

**Cause:** the default CDN entry `shaders@<ver>/js` imports `three` as an external dep; a CDN satisfies it with its own standalone `three`, whose WebGPU/TSL path emits those invalid names.

**Fix:** load the **self-contained bundle** which ships its own tested `three`:
```
https://esm.sh/shaders@<version>/js/bundle
```
(The runtime already does this.) The `…/powerPreference … ignored on Windows` console line is harmless — it just means WebGPU is active.

---

## Maintaining the snippets (data layer)

Fluent Snippets has no Novamira ability; use its API via `execute-php`:
- `FluentSnippets\App\Helpers\Helper::createSnippet(['code'=>…, 'meta'=>[…]])` — **forces `status: draft`** (create inactive; the user activates).
- `Helper::updateSnippet(['file_name'=>'N-slug.php','code'=>…,'meta'=>[…full meta…],'reactivate'=>false])` — updates an existing snippet; for `js`/`css` it **regenerates the cached file and bumps `updated_at`** (→ new enqueue version = cache-bust). Pass the **full** meta (incl. `status`) or `parseInputMeta` resets it.
- Meta: required `name, status, type, run_at`; `tags` is a **string** (it runs `str_contains`); `type` ∈ `PHP | php_content | css | js`; `load_as_file: 'yes'` serves a JS/CSS snippet as an **enqueued external file** (not inline).
- Call `Helper::cacheSnippetIndex()` after create/update.
- Storage: `wp-content/fluent-snippet-storage/` (`index.php` + one `.php` per snippet; `cached/` for `load_as_file` assets).
- **Adding a preset:** `get-preset … format:"js"` → drop `{ components, options? }` under a slug in **NM Shader Presets**. **New instance:** just add another tagged canvas; the bundle still loads once per page.

---

## Verify / debug

- Element renders a real `<canvas …>` on the frontend → *Allow Canvas Tag* active.
- Page has the import of `esm.sh/shaders…/js/bundle` and your config block.
- Console `[nm-shader] could not parse inline config` → bad config; for Method B confirm you pasted into **PHP & HTML** and kept the `<script>` wrapper.
- `[nm-shader] mount failed` + WGSL errors → not on the `…/js/bundle` build, or a stale cache (bump the snippet version via `updateSnippet`).
- Silent, no paint → `prefers-reduced-motion` on, or no WebGL/WebGPU (both intentional fallbacks).
- Editing live config in the builder re-renders the element; the runtime tears down + remounts the GPU context (expected churn while editing).

## Conventions (per `CLAUDE.md`)
- All code via **Fluent Snippets**, created **inactive**; prefix `nm_`/`novamira-`.
- Decorative canvas: `aria-hidden="true"`, last in source order, behind content (`z-index:-1000`).
- Pair the effect with an overlay + the matching `brxp-a11y-…-text` so hero copy stays readable; mask **in-shader** (Pro Note) where text sits.
