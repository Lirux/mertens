---
name: semantic-commit
description: >-
  Stage only files changed by Codex during the current session and create an
  atomic Conventional Commit without pushing. Use when the user asks to commit
  changes, create a semantic or conventional commit, or generate and use a
  commit message without publishing it. Preserve pre-existing user changes,
  reject unrelated concerns, and report the resulting commit.
---

# Semantic Commit

## Identify Session Changes

1. Verify the current directory is in a Git worktree with
   `git rev-parse --is-inside-work-tree`.
2. Inspect `git status --short`, `git diff`, and `git diff --cached` without
   modifying the worktree or index.
3. Build an explicit list of paths Codex created, edited, renamed, or deleted
   during the current session. Use session history and actual tool actions; do
   not treat every dirty path as a session change.
4. Exclude changes that existed before Codex began working, changes made by the
   user, and unrelated changes from another task or session.
5. Stop if no eligible session changes exist.

If Codex edited a file that already contained user changes, do not stage the
whole file. Stage only Codex-owned hunks when they can be isolated with certainty.
Otherwise stop and ask the user to separate or explicitly include the mixed
changes. Never overwrite, discard, restore, or unstage user work.

## Stage Explicit Paths

If the index already contains changes outside the eligible session paths, stop
and tell the user which staged paths would be included. Do not alter the existing
index automatically.

Stage eligible files by passing their literal paths:

```bash
git add -- <path> [<path> ...]
```

Use interactive or patch staging only when necessary to isolate known
Codex-owned hunks. Never use `git add .`, `git add -A`, `git add -u`, a directory,
or a wildcard. These broad forms can capture pre-existing user changes.

After staging, inspect:

```bash
git diff --cached --name-status
git diff --cached --stat
git diff --cached
```

Verify that every staged path and hunk belongs to the current session. Stop
before committing if the staged diff contains anything else.

## Enforce an Atomic Commit

Determine whether every staged change serves one logical purpose. If the staged
diff contains unrelated concerns, do not commit. Identify the concerns and
recommend how to split them, but do not alter already-staged user work without
explicit approval.

## Compose the Message

Use this structure:

```text
<type>(<scope>): <description>

[optional body]

[optional footer]
```

Choose exactly one type:

- `feat`: add or change user-facing behavior
- `fix`: correct user-facing behavior
- `docs`: change documentation only
- `style`: change formatting without changing behavior
- `refactor`: restructure production code without changing behavior
- `perf`: improve performance
- `test`: add or change tests only
- `build`: change the build system or dependencies
- `ci`: change continuous-integration configuration
- `chore`: make maintenance changes not covered by a more specific type
- `revert`: revert an earlier commit

Use a short, lowercase scope when one clearly describes the affected component.
Omit the scope when no meaningful single scope exists.

Write the description in imperative mood, start it with a lowercase letter, do
not end it with a period, and keep the complete header at 72 characters or
fewer. Prefer a specific type over `chore`.

Add a body only when it clarifies what changed or why. Do not narrate the diff
line by line. Add footers only for issue references, breaking changes, or other
required metadata. Mark breaking changes with `!` in the header and a
`BREAKING CHANGE: <description>` footer.

Base every statement on the staged diff. Never mention AI, Codex, or generated
content, and never add attribution trailers unless the user requests them.

## Commit

Show the proposed message, then create the commit with `git commit`. Pass the
header, body, and footer as separate message paragraphs when they exist. Do not
amend an existing commit unless the user explicitly requests it.

After committing, run:

```bash
git show --stat --oneline --summary HEAD
git status --short
```

Report the commit hash and subject. Mention any remaining unstaged or untracked
changes. Never push from this skill.
