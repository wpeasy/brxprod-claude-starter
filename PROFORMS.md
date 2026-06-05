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
   - `bricks-list-elements element="brf-pro-forms"` (wrapper settings + submit actions)
   - `bricks-list-elements element="brf-pro-forms-field-<type>"` for each field type used
   Control keys/shapes change between Bricksforge versions, so always introspect.
3. **Build nested:** `brf-pro-forms` (wrapper) → field elements as children (wrap radio/checkbox sets in their `…-wrapper`; steps inside `brf-pro-forms-steps`).
4. **Configure actions** on the wrapper (email/redirect/etc.) from the discovered controls; never hardcode values a field should carry.
5. **Verify via read-back** (`bricks-get-content`) and on the **frontend** — confirm fields render, the submit action is set, and validation works. Reload the builder before any in-builder edits.

## Layout
- Lay out field rows with a **grid + `auto-fit`** on a wrapper around the fields: `grid-template-columns: repeat(auto-fit, minmax(<brxw token>, 1fr))`, `gap: var(--brxw-grid-gap)`, `align-items: start`.
- Cap the form with a **sensible `max-width`** (snap to a `brxw-*` width / text-width token) so input lines don't get over-long.
- Full-width fields (textarea, submit, consent checkbox) **span all columns** (`grid-column: 1 / -1`).
- **No `@media`** — `auto-fit` reflows; use `@container` only if a real query is needed.
- Style via our own `nm-` global classes where custom styling is needed; prefer Pro Forms' native style controls first (typed controls over raw CSS).

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
