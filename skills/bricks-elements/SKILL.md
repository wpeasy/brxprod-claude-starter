---
name: bricks-elements
description: Fetch the controls/settings schema for every registered Bricks element via the Novamira MCP, write a BRICKS_EL_{name}.md reference file for each element, and update BRICKS-COMPONENTS.md with a linked index table. Use when onboarding a new Bricks site, when custom elements are added or changed, or when you need a schema reference to configure elements programmatically. Requires a connected Novamira MCP server.
---

# Bricks Elements → BRICKS_EL_*.md

Enumerate every element registered in the Bricks element registry, capture its full controls (settings) schema, write one `BRICKS_EL_{name}.md` per element into the project root, and add or refresh an index section in `BRICKS-COMPONENTS.md`.

## Prerequisites

- A connected **Novamira MCP server** (`mcp-adapter-discover-abilities`, `mcp-adapter-execute-ability`).
- If several Novamira servers are connected, confirm which site before proceeding.

---

## Step 1 — Fetch all registered element names and labels

Call `mcp-adapter-execute-ability` with `ability_name: "novamira/execute-php"` and the `parameters.code` below. It returns a JSON-encoded array keyed by element name with just `label` and `category` — no controls yet (keeps the response small):

```php
$out = [];
if (class_exists('\Bricks\Elements')) {
    foreach (\Bricks\Elements::$elements as $name => $class) {
        if (!class_exists($class)) continue;
        try {
            $el = new $class();
            $out[$name] = [
                'label'    => property_exists($el, 'label')    ? $el->label    : $name,
                'category' => property_exists($el, 'category') ? $el->category : 'general',
            ];
        } catch (\Throwable $e) {
            $out[$name] = ['label' => $name, 'category' => '', 'error' => $e->getMessage()];
        }
    }
}
ksort($out);
return json_encode($out);
```

Decode the JSON. If `\Bricks\Elements::$elements` is unavailable (empty array or class missing), fall back to calling `novamira/bricks-list-elements` to retrieve at least element names, then proceed with whatever metadata is available.

> **Filter option:** if the user asks to limit to a prefix (e.g. `brxc-`, `nm-`), apply `strpos($name, $prefix) === 0` inside the loop. Default is all registered elements.

---

## Step 2 — Fetch controls schema for each element (batched)

Fetch controls in **batches of 15 elements** to avoid PHP timeouts. For each batch call `mcp-adapter-execute-ability` with `ability_name: "novamira/execute-php"`:

```php
// $targets = array of element names for this batch (pass as JSON-encoded string in code)
$targets = json_decode('TARGETS_JSON_HERE', true);
$out = [];
foreach ($targets as $name) {
    if (!isset(\Bricks\Elements::$elements[$name])) { $out[$name] = null; continue; }
    $class = \Bricks\Elements::$elements[$name];
    if (!class_exists($class)) { $out[$name] = null; continue; }
    try {
        $el = new $class();
        $out[$name] = property_exists($el, 'controls') ? $el->controls : [];
    } catch (\Throwable $e) {
        $out[$name] = ['_error' => $e->getMessage()];
    }
}
return json_encode($out);
```

Replace `TARGETS_JSON_HERE` with the JSON-encoded array of 15 element names for that batch. Collect all batch results into a single `controls_by_name` map.

---

## Step 3 — Write BRICKS_EL_{name}.md for each element

For every element in the name list (skip any whose controls returned `_error`), write `BRICKS_EL_{name}.md` in the **project root**. Overwrite if the file already exists.

### File template

```markdown
# Bricks Element: `{name}`

**Label:** {label}  
**Category:** {category}

## Controls (Settings Schema)

{controls table}
```

### Controls table format

Render the element's `controls` array as a markdown table. A control entry is a keyed array; the array key is the setting key stored on `settings`:

```markdown
| Key | Label | Type | Default | Notes |
|-----|-------|------|---------|-------|
```

Map each control entry:

| Controls field | → Table column |
|---|---|
| array key | **Key** |
| `label` | **Label** |
| `type` | **Type** |
| `default` (if set) | **Default** |
| `options` (for `select`/`radio`) → comma-list of `value: label` | **Notes** |
| `description` (if set) | append to **Notes** |
| `required: true` | append `(required)` to **Notes** |

For `repeater` and `group` type controls, add a sub-table or indented rows prefixed `↳` for each entry in their `fields` array.

If an element has no controls write: `_No controls defined._`

**Example output:**

```markdown
| Key | Label | Type | Default | Notes |
|-----|-------|------|---------|-------|
| tag | HTML Tag | select | div | div, section, article, main, aside, header, footer, nav |
| width | Width | text | 100% | |
| ↳ fields.gap | Gap | number | | Inside repeater `items` |
```

---

## Step 4 — Update BRICKS-COMPONENTS.md

Add (or replace) a section at the **end** of `BRICKS-COMPONENTS.md`. The section marker is the heading `## Registered Element Schemas` — if it already exists, replace everything from that heading to end-of-file.

```markdown
---

## Registered Element Schemas

Auto-generated by the `bricks-elements` skill. Regenerate when elements are added or their controls change.

| Element | Label | Category | Schema file |
|---------|-------|----------|-------------|
| `name` | Label | category | [BRICKS_EL_name.md](BRICKS_EL_name.md) |
```

Sort rows alphabetically by element name. Skip any elements whose schema file was not written (error during controls fetch).

---

## Step 5 — Confirm

Report:
- Total elements found in the registry
- Number of `BRICKS_EL_*.md` files written (vs. skipped with reason)
- Confirmation that `BRICKS-COMPONENTS.md` was updated
- Any elements that errored during instantiation (list names + errors)
