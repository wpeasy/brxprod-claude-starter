# BRICKS-RECIPES.md — niche Bricks element recipes

On-demand reference (split out of `CLAUDE.md`). Read the relevant entry **only** when you're working with that specific element/task. The always-needed data-layer internals (custom tags, `_cssId`, `_attributes`, theme-style keys, `bodyClasses`, CSS-loading mode, section striping, the builder-reload caveat) stay in `CLAUDE.md`.

## Related posts & meta → Query Loop (never a code snippet)
**To display a related post — or its fields/meta, *including the current user's related post* — wrap the element(s) in a Bricks Query Loop. Do NOT write a custom snippet / helper function / `{echo:fn}` tag for it.** The loop establishes the post context, so plain dynamic tags resolve from the looped post: `{post_url}`, `{post_title}`, `{featured_image}`, `{mb_<field>}`, `{acf_<field>}`, etc. A **list** of related items = a loop; a **single** related item = a **1-item loop**.

- **Static relation** (a fixed post, a taxonomy term's posts, a whole post-type) → set it with the loop element's visual **Query** controls: `post_type`, `post__in`, `tax_query`, `orderby`, `posts_per_page`, …

- **Dynamic / per-user relation** (the post id lives in a *user-meta* field, "my line", "my profile X") → Bricks query controls can't express "the current user's related post", but its **PHP query mode** can — and it lives on the element, so still **no snippet**:
  - `query.useQueryEditor: true`
  - `query.queryEditor: "<php>"` — PHP that **`return`s an array of `WP_Query` args**. Bricks `array_merge`s your return over the visual query vars (so your keys win; you can return just the deltas).
  - `query.signature: wp_hash( <the exact queryEditor string> )` — required; Bricks runs `Helpers::verify_code_signature( $signature, $code )` = `wp_hash($code) === $signature` (hashes the code only, so one signature is valid across instances). Compute it in the **same** write so it matches byte-for-byte.
  - Requires Bricks **Code Execution** enabled (`Helpers::code_execution_enabled()`); otherwise the editor returns nothing.

  ```php
  // queryEditor body — show ONLY the current user's related CPT post
  $rel = (int) get_user_meta( get_current_user_id(), '<user_meta_key>', true );
  return [
    'post_type'           => '<cpt>',
    'post__in'            => $rel ? [ $rel ] : [ 0 ], // [0] => render nothing when unset
    'posts_per_page'      => 1,
    'orderby'             => 'post__in',
    'ignore_sticky_posts' => true,
  ];
  ```
  Then inside the loop: a **button/link** → `link: {type:'external', url:'{post_url}'}`; **text** → `{post_title}`; **image** → the CPT image field; **any field** → `{mb_<field>}`. To keep a looped single element inline in a flex button-group, give the loop wrapper `display: contents`.

- **Why a loop, not code:** the data binding travels with the page/template, stays editable in the builder, needs **no snippet to install or activate**, and sidesteps `{echo:fn}` allow-list + signature plumbing. Reserve PHP/snippets for genuine business logic — never to merely fetch a related post or its meta.

- **Verify** (query editor runs in a normal front-end render, not the builder): `wp_set_current_user($id); $html = \Bricks\Frontend::render_data( get_post_meta($page,'_bricks_page_content_2',true) );` then assert the right item rendered. ⚠️ Bricks keeps **one Query instance per element id per request**, so calling `render_data` twice in one request reuses the first run's result — test each user/state in a **separate** request.

## Icons (Bricks `icon` element)
The `icon` setting is `{library, icon}`. **`library` MUST be a Bricks key — `fontawesomeSolid` / `fontawesomeRegular` / `fontawesomeBrands` (FA6), `themify`, `ionicons`** — *not* `fontawesome6Solid` etc. A wrong/unknown `library` still prints the `<i class="fas fa-…">` but Bricks **won't enqueue the icon font**, so it renders blank. Verify the `font-awesome-*` CSS actually `<link>`s on the page — don't trust that the `<i>` is merely present in the DOM.

## Sourcing images
Prefer the **Instant Images** plugin (Unsplash / Pixabay / Pexels) when it's installed — pick in WP admin, then use the resulting media-library attachment. Otherwise source from **Pixabay or Unsplash**. To add one programmatically, sideload a **direct image URL** with `download_url()` + `media_handle_sideload()` using a forced `.jpg` filename and a `getimagesize()` guard (plain `media_sideload_image()` rejects extensionless / query-string URLs). Note: Instant Images' keyless proxy is gated to its own browser flow, so it can't be called from the data layer.

## Maps
The Bricks **Google Map** element renders a single-`address` map via a **keyless Google Maps embed iframe** (`maps.google.com/maps?q=…&output=embed`) — **no site API key required**, and it geocodes the address for you. A **Google Maps JavaScript API key** is only needed for JS-API features (multiple/dynamic markers, clustering, custom map styles). The **Leaflet** element (`map-leaflet`) is also keyless but has **no geocode** — position it by `center: "lat,lng"`. (Verified: a single-address Google map loads `google.com/maps` embed with no `maps.googleapis.com` and no key notice.)

## Nested accordion (`accordion-nested`)
Build each item as a `div` containing two child `div`s: a **Title** `div` with `_cssClasses: "accordion-title-wrapper"` (set `_display: flex`, `_direction: row`, `_justifyContent: space-between`, `_alignItems: center`) holding a `heading` (the question) + an `icon` with `isAccordionIcon: true` (the chevron); and a **Content** `div` with `_cssClasses: "accordion-content-wrapper"` holding the answer `text`. (Use `div` per the no-Block rule — the native default uses `block`, which would add an unwanted title↔content `row-gap`.) Those two classes are **required** by the toggle JS. Style via the element's **native controls** — `title*` / `titleActive*` / `content*` (background/padding/typography/border), `transition`, `expandFirstItem`, `independentToggle` — not custom CSS; enable **`faqSchema`** for FAQ‑page structured data. The native element outputs an accessible disclosure (`role="button"` + `aria-expanded` + `aria-controls`) — don't add ARIA. Title headings default to `h3`; set the correct level (no skips) and give them the title surface's `a11y` text var (the global heading colour overrides inheritance otherwise).
