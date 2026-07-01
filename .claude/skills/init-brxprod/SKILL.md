---
name: init-brxprod
description: Bootstrap a new BRXProd Bricks project from this starter — ask the setup questions and tailor CLAUDE.md, detect the OS (Mac/Windows/Linux), install the starter's skills at user or project level, list the available skills, then explain how to connect the project to a Novamira MCP server. Use when the user runs `/init-brxprod`, or asks to set up / bootstrap / initialise the starter in a new project. (Named `init-brxprod` to avoid clashing with Claude Code's built-in `/init`.)
---

# Initialise a BRXProd Bricks project (starter bootstrap)

One command to set up a new project created from **brxprod-claude-starter**: tailor `CLAUDE.md`, choose where the skills live, install them, list them, and point the user at the Novamira connection step.

Run the steps **in order**, and confirm each result (read-back / directory listing) before moving on. Keep the tone friendly — this is someone's first run.

## Prerequisites
- The starter's contents (`CLAUDE.starter.md`, `skills/`, reference MD files, `.claude/`) must be at the **project root** — the same folder Claude Code has open as its working directory.
- If both `CLAUDE.starter.md` and `skills/` are missing from the CWD, say this doesn't look like a fresh starter copy and stop (don't guess paths).

## Step 0 — Confirm the project root (run before anything else)

The starter is sometimes cloned into a **subfolder** of the actual project (e.g. `my-project/brxprod-claude-starter/`). If init runs from inside that subfolder, all reference MD files and `.claude/` land in the wrong place. Detect and fix this before proceeding.

**Check:** does the CWD look like a subfolder of the real project root?
- List the parent directory (`../`). If it contains project files (a `src/`, `wp-content/`, another `.git/`, etc.) it is likely the true project root.
- Or simply ask the user: *"Is this folder the root of your project, or did you clone the starter into a subfolder?"*

**If the starter IS in a subfolder** — move its contents up to the project root first, then continue init from there:

- **macOS / Linux (bash)** — run from *inside* the starter subfolder:
  ```bash
  # e.g. starter is at my-project/brxprod-claude-starter/
  shopt -s dotglob
  mv * ../
  cd ..
  rmdir brxprod-claude-starter   # remove the now-empty subfolder
  ```
- **Windows (PowerShell)** — run from *inside* the starter subfolder:
  ```powershell
  # e.g. starter is at my-project\brxprod-claude-starter\
  Get-ChildItem -Force | Move-Item -Destination ..\
  Set-Location ..
  Remove-Item brxprod-claude-starter -Force   # remove the now-empty subfolder
  ```

Verify that `CLAUDE.starter.md`, `skills/`, and the reference MD files (`BRICKS-COMPONENTS.md`, `BRICKS-RECIPES.md`, etc.) are now in the project root before continuing.

## Step 1 — Startup questions → tailor `CLAUDE.md`
First make sure `CLAUDE.md` exists:
- If `CLAUDE.md` is **absent** and `CLAUDE.starter.md` is present → copy `CLAUDE.starter.md` to `CLAUDE.md`.
- If `CLAUDE.md` **already exists** → it may already be tailored; ask whether to re-run tailoring before changing anything.

Then ask the setup questions. **Batch the choice-style ones into a single `AskUserQuestion`** (each `AskUserQuestion` option always offers an "Other" escape for custom values); ask the free-text ones (server name, prefix) in chat. Apply each answer to `CLAUDE.md`:

| Question | Default | Edit to make in `CLAUDE.md` |
|---|---|---|
| **Novamira MCP server name** (free text) | — | Replace **every** `<MCP_SERVER_NAME>` with it |
| **Data / field tool + edition** | Meta Box AIO (Pro) | Set the "CPTs & Custom Fields" rule to the chosen tool — Meta Box AIO Pro / ACF Pro / ACF Free / Pods / other. Note: this can be re-verified once Novamira is connected (check active plugins). |
| **Code / snippets tool** | Fluent Snippets | If different, swap the "Adding code" rule to the chosen snippets manager |
| **WP Reset installed?** | Yes | If **No**, remove the WP Reset safety note from the "Safety & workflow" section |
| **Project prefix / text domain** | `nm-` / `novamira-`, text domain `nm` | If custom, replace the prefix + text-domain references throughout |

Finally, **delete the "How to use this starter" block** (the intro numbered list and its trailing `---`) from `CLAUDE.md` — it's setup-only scaffolding.

## Step 2 — Detect the OS
The OS sets the user-level skills path and the path style. Detect it:
- Run `uname -s` (or inspect the environment): `Darwin` → **macOS**, `Linux` → **Linux**, `MINGW*` / `MSYS*` / `CYGWIN*` (or `$OS = Windows_NT`) → **Windows**.
- Resolve the **user-level skills dir**:
  - macOS / Linux → `~/.claude/skills/`
  - Windows → `%USERPROFILE%\.claude\skills\` (in the bash shell `$HOME` usually resolves to `C:\Users\<name>`; PowerShell uses `$env:USERPROFILE`)
- Tell the user which OS you detected and the path you'll use.

## Step 3 — Ask where the skills should live
Use `AskUserQuestion` — **User level** vs **Project level**:
- **User level (recommended)** — `~/.claude/skills/` (Win: `%USERPROFILE%\.claude\skills\`). Shared across every project on this machine; install once, reuse everywhere.
- **Project level** — `<project>/.claude/skills/`. Scoped to this project only and travels with the repo (commit them) — good when different projects need different versions.

## Step 4 — Move the skills into place
Relocate these **five** skill folders from the starter's `skills/` source into the chosen target:
`add-overlay`, `add-pattern`, `bricks-design-tokens`, `bricks-elements`, `init-bricks`.

> Leave **`init-brxprod`** where it is — it lives in `.claude/skills/init-brxprod/` and stays **project-level** as the bootstrap.

Target dir (`<TARGET>`):
- User level → `~/.claude/skills/` (mac/Linux) or `%USERPROFILE%\.claude\skills\` (Windows)
- Project level → `<project>/.claude/skills/`

Procedure:
1. Create `<TARGET>` if missing.
2. For each of the four: if it **already exists** at the target, show the user and **ask before overwriting**.
3. **Copy** each folder to `<TARGET>`, then **verify** each `SKILL.md` landed.
4. Once verified, **remove the source `skills/` folder** (it was only the delivery vehicle) — that completes the "move".

Commands (adapt to OS; substitute the resolved `<TARGET>`):
- **macOS / Linux (bash):**
  ```bash
  mkdir -p <TARGET>
  cp -R skills/add-overlay skills/add-pattern skills/bricks-design-tokens skills/bricks-elements skills/init-bricks <TARGET>/
  rm -rf skills
  ```
- **Windows (PowerShell):**
  ```powershell
  New-Item -ItemType Directory -Force <TARGET> | Out-Null
  Copy-Item skills\add-overlay,skills\add-pattern,skills\bricks-design-tokens,skills\bricks-elements,skills\init-bricks <TARGET> -Recurse -Force
  Remove-Item skills -Recurse -Force
  ```

## Step 5 — List the available skills
Read `<TARGET>` (and `.claude/skills/`) and confirm what's now installed:
- **`/bricks-design-tokens`** — reads the site's Bricks `brxw-*` / `brxp-*` variables + global classes and writes/refreshes the "Design System reference" section in `CLAUDE.md`.
- **`/init-bricks`** — sets the base Theme Style colours (site background, body text, headings), favouring design tokens.
- **`/add-overlay`** `.class|#id` — merges the standard dark overlay scrim onto a global class or element id (pairs with `brxp-has-bg-media`).
- **`/add-pattern`** `.class|#id` — merges a decorative SVG background-pattern layer onto a global class or element id.
- **`/bricks-elements`** — fetches the controls/settings schema for every registered Bricks element and writes individual `BRICKS_EL_{name}.md` reference files + an index in `BRICKS-COMPONENTS.md`.
- **`/init-brxprod`** — this bootstrap (stays project-level).

Tell the user a **reload may be needed** for newly installed skills to show up (restart the client, or reload the session / run `/mcp`-style reconnect per their client).

## Step 6 — Connect the project to Novamira
Explain the one remaining step (you can't do it for them — it's client config + their credentials):
- The project still needs a **Novamira MCP server** connection to the WordPress site — every skill and the `CLAUDE.md` tooling reads/writes through it.
- Easiest path: open the site's **Novamira Configuration page**, expand **"Need the JSON config for a specific client?"**, pick this client, and copy the snippet into the client's MCP config — **or** paste the connection details (Server URL, Username, Application Password) here and ask Claude to write them into the client's MCP config (credentials as env vars: `WP_API_URL`, `WP_API_USERNAME`, `WP_API_PASSWORD`).
- The MCP **server name must match** the `<MCP_SERVER_NAME>` entered in Step 1.
- After connecting **and reloading**, run **`/bricks-design-tokens`** to generate the design-system reference, then optionally **`/init-bricks`** to set the base theme colours.

## Notes
- Safe to re-run: it asks before overwriting existing skills or re-tailoring an existing `CLAUDE.md`.
- Never write credentials into `CLAUDE.md` or any committed file — they belong only in the client's MCP config (env vars).
- Follow `CLAUDE.md` conventions for any edits you make.
