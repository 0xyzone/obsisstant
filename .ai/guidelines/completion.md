# Post-Task Completion, Versioning, Git & Notification Rules

## 1. Semantic Versioning (`changelog.md`)
- Maintain all application releases in `changelog.md` starting from `v1.0.0` (initial baseline).
- Every release heading must follow:
  ```markdown
  ## [vX.Y.Z] - YYYY-MM-DD | type: <major|minor|macro>

  > Concise summary of the release
  ```
- Classify every task accurately before updating `changelog.md` (or running `php artisan version:bump <type> --write`):
  - **`major` (`X.0.0`)**: Breaking architectural changes, major rewrites, or incompatible database/API/auth changes.
  - **`minor` (`X.Y.0`)**: New user-facing features, Filament resources/pages, endpoints, or non-breaking capabilities.
  - **`macro` (`X.Y.Z`)**: Bug fixes, UI refinements, config/chore adjustments, refactors, and incremental patches.
- The `/changelog` route renders `changelog.md` directly — always record completed changes in `changelog.md` before committing.

## 2. Verification Before Finalizing
- Run `vendor/bin/pint --dirty --format agent` after PHP edits.
- Run `composer test` (or targeted `php artisan test`) and verify everything passes.
- Never run `npm run build`.

## 3. Git Commit & Push (Every Task Completion)
- Stage all changes, commit with a descriptive message (referencing the version when bumped), and push to `origin main`:
  ```sh
  git add -A
  git commit -m "<descriptive message>"
  git push origin main
  ```

## 4. `ntfy.sh` Push Notification (Every Task Completion)
- Always send a completion notification to the `obsisstant-tasks` channel via:
  ```sh
  php scripts/notify_ntfy.php success "<concise task summary>"
  ```
- On failure or blocked state that needs user intervention, send:
  ```sh
  php scripts/notify_ntfy.php failure "<concise reason>"
  ```
