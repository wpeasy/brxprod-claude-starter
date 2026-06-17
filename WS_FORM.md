# WS_FORM.md — WS Form (data-layer) reference

> Hard-won notes for building/editing **WS Form** forms through the data layer (Novamira `execute-php` + the `WS_Form_Form` class), and — just as important — where **not** to bother and use the builder instead. Read this before scripting WS Form changes. (Only relevant on sites where WS Form is the form tool.)

## Golden rule — build interactive logic in the WS Form BUILDER, read it back to learn the format
**Conditional logic**, and any field whose **type** must change, are a few clicks in the WS Form builder and *guaranteed* valid. By hand from the data layer they are fragile (the rule JSON has builder-managed internals; get one key wrong and the builder shows the rule as **incomplete** and it silently never fires). The data layer is excellent for **fields, values, actions, dedupe, and bulk edits**. For conditional logic / type changes: do it in the builder, then `db_read` it back to capture the exact JSON.

## Conditional logic — form-level `meta.conditional`
A **data_grid** on the form object: `meta.conditional.groups[0].rows[]`, each row = `{id, data:["<label>", "<rule JSON string>"]}`. Verified working rule (show a section of fields when a select option is chosen):

```json
{
  "if":   [ { "conditions": [ { "id":1, "object":"field", "object_id":<SELECT_FIELD_ID>, "object_row_id":"<OPTION_ROW_ID>", "logic":"selected", "value":"", "case_sensitive":true, "logic_previous":"||" } ], "logic_previous":"||" } ],
  "then": [ { "id":1, "object":"section", "object_id":"<SECTION_ID>", "object_row_id":"", "action":"visibility", "value":"",    "variable":"", "multiple":false } ],
  "else": [ { "id":1, "object":"section", "object_id":"<SECTION_ID>", "object_row_id":"", "action":"visibility", "value":"off" } ]
}
```

**The three things that trip you up:**
1. **Select / radio / checkbox conditions are ROW-based, not value-based.** Use `logic:"selected"` (select) or `"checked"` (radio/checkbox) with **`object_row_id` = the option's data-grid ROW id** (e.g. the 2nd option = row `"2"`), and `value:""`. Do **NOT** use `==` / `selected_value_equals` + a value string for these — the builder renders that as an **incomplete** condition (operator/value dropdowns stay on "Select…") and it never fires. `==,!=,>,<,contains,starts,ends,regex,blank` are for **text/number** fields only.
2. **Toggle a SECTION, not individual fields.** Put the conditional fields inside their own **section** and target that one section (`object:"section"`, `object_id:"<section id>"`). Cleaner than one action per field.
3. **The action is always `"visibility"`, value-driven, with BOTH `then` and `else`.** `value:""` = **show**, `value:"off"` = **hide**. There is no `action:"show"`/`"hide"` — it's one `"visibility"` action and the **value** flips it. Provide `then` (show) and `else` (hide).

Operator list lives in `ws-form-pro/includes/config/class-ws-form-config-conditional.php` (per object type). Field events/state logics also exist: `wsf-rendered`, `click`, `blank`, `selected_value_equals`, `rc>` (selected-count), etc.

## Field TYPE cannot be changed on an existing field
`db_update_from_object` (and even a direct `wsf_field` table write) **revert** a changed type — `text→file` and `select→radio` snap straight back. To change a type you must **recreate the field**: rebuild the section's field array with a fresh field (new id, full replace), or change it in the builder. Types: `text, textarea, number, tel, email, url, select, checkbox, radio, datetime, file, signature, hidden, password, submit, …`.
- A **file** field can't be conjured from scratch reliably — create a throwaway form from a template that has one (`employment-application` includes a `file`), clone that field object, then delete the throwaway.
- **Recreate via `WS_Form_Field::db_create(<next_sibling_id>)`** — the type sticks on a *new* id (in-place change still reverts). Re-point any **conditional rules** and **Create Post mappings** that referenced the old field id, then delete the old. `db_delete()` commits, but a **subsequent `db_read` in the same script throws** — do deletes last or in a separate call.
- **`select → radio` as segmented buttons works from the data layer:** recreate as `radio` with `radio_style:"button"` (or `"button-full"`), `orientation:"horizontal"`, `default_value_radio:"<value>"`, a `data_grid_radio` of rows, and `radio_field_value`/`radio_field_label` column mapping (keep the same stored values). Radio conditions use `logic:"checked"` (not `"selected"`).

## Calc / aggregating one value (e.g. select-or-"Other" → a single entry value)
- **WS Form calc / `IF()` CANNOT be authored from the data layer** — calc text in `default_value` is **not evaluated** (renders verbatim); set calcs in the builder's calculator (**fx**) UI and leave `default_value` empty from code.
- **Conditional logic has no "set value" action** (only row select/check, visibility, class, date min/max). To aggregate "an option or a typed Other" into one mapped value: add a **hidden field**, map *it* in Create Post, and compute it with a builder calc like `IF(#field(<select>) = "Other", #field(<other_text>), #field(<select>))` (insert `#field(id)` tokens via the field picker).

## Actions — `meta.action` (also a data_grid)
`meta.action.groups[0].rows[]`, each row = `{id, data:["<label>", "<action JSON>"]}`; action JSON = `{ "id":"<action_id>", "meta":{…}, "events":["submit"] }`.
- **Create Post** (`ws-form-post`): `id:"post"`; `meta.action_post_list_id` = **post-type slug**; `action_post_status`; **`action_post_field_mapping_meta_box`** = `[{ws_form_field:"<field id>", action_post_meta_box_field_id:"<mb field key>"}]` for Meta Box fields (uses `rwmb_set_meta`). (ACF/Pods/etc. have parallel `action_post_field_mapping_*` keys.)
- **Log In** (`ws-form-user`): `id:"user"`; `meta.action_user_list_id:"signon"`; `action_user_field_mapping` = `[{ws_form_field, action_user_list_fields:"username"|"password"|"remember_me"}]`.
- **Redirect**: `id:"redirect"`; `action_redirect_type:"page"` + `action_redirect_page:"<post id>"` (or default type + `action_redirect_url`). On a failed prior action (e.g. signon error) the chain halts, so a trailing redirect only fires on success.
- The nested action JSON is a **string** in `data[1]`; once you `wp_json_encode` the whole form its quotes escape to `\"id\":\"post\"`, so don't grep the encoded blob for the literal `"id":"post"`.

## One submission per value (deduplication)
On the field meta: `dedupe:"on"`, `dedupe_period:""` (all-time), `dedupe_message:"…"`. Requires the **Save Submission** (database) action active — it checks prior submissions of that form for the same value. Good for "one registration per email".

## Publish model & the builder-overwrite caveat
- `db_publish()` snapshots the working form into a **published JSON** (the `published` column of `wsf_form`); the **frontend renders that snapshot** (`db_read_published`). Always `db_update_from_object(...)` **then** `db_publish()`, and verify against the published snapshot / the rendered page, not just the working copy.
- ⚠️ **A save or publish from an open WS Form builder overwrites data-layer changes** it didn't load (same trap as the Bricks builder). After any data-layer edit, **reload the builder** before editing there.
- `select_field_value` (`'0'` = label column, `'1'` = value column) decides what a select submits/stores.

## Synthetic events don't drive conditional logic
When testing in a headless/automation context, dispatching a native or jQuery `change` on a select does **not** reliably trigger WS Form's conditional engine — it ignores untrusted events. Validate conditional logic with a **real interaction** (or trust a builder-built rule), not a scripted `dispatchEvent`.
