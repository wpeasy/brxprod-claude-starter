# PROFORMS.md — Bricksforge Pro Forms playbook

> **Conditional doc.** Read this **only when Bricksforge Pro Forms is active** on the site — i.e. the Bricks element **`brf-pro-forms`** is registered (check with `novamira/bricks-list-elements`). If it isn't, ignore this file and build forms with the **core Bricks `form` element** instead.
>
> All the general project rules in `CLAUDE.md` still apply here (nm- BEM + one global class per styled element, `brxw-`/`brxp-` variables, A11y/semantics, no `@media`/use `@container`, verify via read-backs, code via Fluent Snippets, builder-reload caveat).

## What Pro Forms is
A **nestable** form system that replaces the core Bricks Form with advanced fields, multi-step, conditional logic, calculations, integrations and anti-spam. You compose a form from a **wrapper element + nested field elements** (not a single element with a fields repeater).

## Element map (discovered — re-verify per site/version)
- **Wrapper / structure**
  - `brf-pro-forms` — the **form wrapper**; holds the form-level settings & **actions** (what happens on submit: email, redirect, save, integrations, …). Everything else nests inside it.
  - `brf-pro-forms-steps` + `brf-pro-forms-field-step` / `-previous` / `-next` / `-summary-button` — **multi-step** forms.
  - `brf-pro-forms-field-conditional-wrapper` — show/hide groups via conditional logic.
  - `brf-pro-forms-field-repeater` — repeatable field groups.
- **Inputs**
  - `…-field-text`, `-textarea`, `-number`, `-email`, `-tel`, `-url`, `-date`, `-password`, `-hidden`, `-file`
  - `-select` (+ `-option-group`, `-option`)
  - `-checkbox-wrapper` (+ `-checkbox`, `-card-checkbox`, `-image-checkbox`)
  - `-radio-wrapper` (+ `-radio`, `-card-radio`, `-image-radio`)
  - `-rating`, `-slider`, `-richtext`, `-color-picker`, `-signature`, `-map`, `-calculation`, `-live-value`, `-file-download`
- **Submit & anti-spam**
  - `-submit-button`
  - `-turnstile`, `-hcaptcha`, `-honeypot`

## Build workflow (MUST follow)
1. **Confirm availability:** `bricks-list-elements` → ensure `brf-pro-forms` exists. If not, fall back to core `form`.
2. **Discover controls at build time — never from memory.** Pull the real control schema for each element you'll use:
   - `bricks-list-elements element="brf-pro-forms"` works for the **wrapper** (settings + submit actions) — though it's large (may save to a file; read it with jq/python or via a subagent).
   - **Field child elements (`brf-pro-forms-field-*`) can NOT be introspected** via `bricks-list-elements` (adapter throws `array_merge(): … null given`) or standalone class instantiation (they need builder context). Get their control keys from **`\Bricksforge\ProForms\Helper::get_default_controls('<type>')`** via `execute-php` (read-only), plus the per-type file (e.g. `…/pro-forms/elements/Text.php`) for type-specific extras. See the verified key reference below.
   Control keys/shapes change between Bricksforge versions, so always introspect.
3. **Build nested:** `brf-pro-forms` (wrapper) → field elements as children (wrap radio/checkbox sets in their `…-wrapper`; steps inside `brf-pro-forms-steps`).
4. **Configure actions** on the wrapper (email/redirect/etc.) from the discovered controls; never hardcode values a field should carry.
5. **Verify via read-back** (`bricks-get-content`) and on the **frontend** — confirm fields render, the submit action is set, and validation works. Reload the builder before any in-builder edits.

## Layout
- **Use each field's native `width` control** (a percentage string, e.g. `"50%"` / `"100%"`) for row layout — Bricksforge flex-wraps fields by width, so two `50%` fields sit side-by-side and `100%` fields (textarea, submit) take a full row. This is the idiomatic way; **don't impose your own CSS grid** on the form wrapper (it fights Pro Forms' field structure).
- ⚠️ The Novamira adapter warns `width` *"is not a Bricks control / dropped at render"* — that's a **false positive** for Bricksforge field elements; `width` is a real Bricksforge control and applies (Bricks emits `#brxe-<id>{width:…}`). Verify on the frontend.
- **Let the rails band / grid column set the form's width** — don't add a layout `max-width`. Only if the form is otherwise full-width and inputs get over-long, constrain line-length with a **text-measure** `max-width` (`brxw-text-width-*`). Give the panel a solid `brxp-surface-*` background + its `ally` text var so labels read clearly.
- **No `@media`** — use `@container` only if a real query is needed.
- Prefer Pro Forms' native style controls first (typed controls over raw CSS); add `nm-` global classes for panel-level styling.

## Verified element keys (Bricksforge 3.1.x — re-verify per version)
Source of truth: `\Bricksforge\ProForms\Helper::get_default_controls('<type>')` (call via `execute-php`) + the per-type element file.
- **Common field keys** (text / email / tel / number / url / textarea …): `id` (the field key/name → renders as `data-custom-id`, used in email merge tags — keep unique), `label`, `showLabel` (checkbox), `placeholder`, `value`, `required` (checkbox), `width` (text %, e.g. `"50%"`), `icon`, `disabled`, `readonly`. **Textarea** adds `height`; **text** adds `maxlength`, `pattern`, `autocomplete`.
- **Submit button** (`brf-pro-forms-field-submit-button`): `label` = button text (NOT `submitButtonText` — that's the wrapper's legacy built-in button).
- **Form wrapper** (`brf-pro-forms`) submit actions: `actions` (multi-select array of action keys, e.g. `["email"]`), `successMessage`; **email action** → `emailTo` (select: `admin_email` | `custom` | `dynamic`), `emailToCustom` (if custom), `emailSubject`, `emailContent` (textarea — confirm merge-tag syntax in the Pro Forms UI), `fromEmail` / `fromName` / `replyToEmail`, `emailBcc`, `htmlEmail`. Other actions exist: `redirect`, `webhook`, `mailchimp`, `post_create`, `create_submission`, etc.
- Fields are **child elements** of `brf-pro-forms` — don't use the wrapper's legacy `fields` repeater.

## Accessibility (forms)
- Every field has a **programmatic `<label>`** (Pro Forms field label control) — never placeholder-as-label.
- **Required** fields: set the field's required control *and* make the requirement visible in the label, not by colour alone.
- Group **radio/checkbox** sets in their `…-wrapper` (maps to a group with a legend/accessible name).
- Validation **errors as text** associated with the field (`aria-describedby`), not colour-only.
- Keep a visible **focus** indicator on inputs/buttons (≥3:1).
- Prefer **honeypot** (invisible to users) and/or **Turnstile/hCaptcha** for spam — never a CAPTCHA users must solve manually if avoidable.

## Notes
- Submit/validation is JS-driven by Pro Forms (`brfProForms` scripts) — don't hand-roll form JS; if you need extra behaviour, add it via **Fluent Snippets** (enqueued), per `CLAUDE.md`.
- Meta Box also registers `mbup_*` (login/registration/profile) and `mbfs-*` (submission/user-dashboard) form elements — those are for **user-account / front-end-submission** flows, not general contact forms. Use Pro Forms for general forms; reach for the Meta Box ones only for their specific account/submission purpose.
