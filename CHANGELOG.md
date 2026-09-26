# Changelog

All notable changes to `laranail/password-history` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

### Fixed

- **`suggest` named `laranail/validation ^1.0`, which resolves nothing.** `v1.0.0` was withdrawn in the floor-to-`v0.1.0` reset; the only tag on the remote is the moving `v0.1.0`, so the suggestion now reads `^0.1`. Composer never resolves a suggestion, so no CI run could catch it.

## v0.1.0 - 2026-08-24

Initial release.

### Added

- `Rules\UnusedPassword` — rejects a candidate matching any of the user's last N password
  hashes OR the live current one. Resolves the user from the constructor, the authenticated
  request, or a form field (admin resets); with nobody to resolve (signup) it passes by
  design. A history-store failure **fails closed** by default (`on_store_error => 'degrade'`
  opts into availability), and the failure message never reveals which password matched.
- `Concerns\HasPasswordHistory` — the relation, idempotent `recordPassword()`,
  `hasUsedPassword()`, `prunePasswordHistory()`, and the deletion purge: removing a user
  removes their credential history (soft deletes keep it until the force delete).
- `Contracts\PasswordHistoryStore` with the Eloquent default store (`singletonIf`, so an
  application-bound store wins). `record()` prunes inline to keep-N — pruning is a privacy
  control, not optional hygiene.
- `Observers\PasswordChangeObserver`, opt-in via `record_on_save`: archives the OLD hash when
  the password column actually changes, and nothing otherwise.
- `laranail::password-history.prune {--user=} {--keep=}` — the schedulable keep-N sweep.
- The morph-based `password_histories` migration, the `laranail-password-history::`
  translations, the flat `laranail.password-history.*` config, and a live-registry naming
  guard.
- The guarded bridge onto `laranail/validation`'s `password()` builder: `->notReused()`
  appears when the validator is installed and the validator knows nothing of this package.
