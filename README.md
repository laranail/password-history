# laranail/password-history

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/password-history.svg)](https://packagist.org/packages/laranail/password-history)
[![Tests](https://github.com/laranail/password-history/actions/workflows/run-tests.yml/badge.svg)](https://github.com/laranail/password-history/actions/workflows/run-tests.yml)
[![Static analysis](https://github.com/laranail/password-history/actions/workflows/phpstan.yml/badge.svg)](https://github.com/laranail/password-history/actions/workflows/phpstan.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> Prevent a user from reusing their last N passwords — a validation rule, a model + migration, a user-model trait, and a schedulable pruning command, all behind a swappable store.

Targets PHP `^8.4.1 || ^8.5` on Laravel `^13`. No dependencies beyond `illuminate/*` and the laranail foundation.

## Install

```bash
composer require laranail/password-history
php artisan migrate
```

laranail packages resolve through git VCS repositories — see [Installation](docs/installation.md)
for the `repositories` entries.

## Quick start

```php
use Simtabi\Laranail\PasswordHistory\Rules\UnusedPassword;

// A password-change form: rejects the current password and the last N.
$request->validate([
    'password' => ['required', 'string', 'confirmed', new UnusedPassword()],
]);
```

```php
// On the User model:
use Simtabi\Laranail\PasswordHistory\Concerns\HasPasswordHistory;

class User extends Authenticatable
{
    use HasPasswordHistory;
}

// At the point of change — validate first, persist, then archive the OLD hash:
$old = $user->password;
$user->update(['password' => Hash::make($validated['password'])]);
$user->recordPassword($old);
```

With [`laranail/validation`](https://github.com/laranail/validation) installed, the fluent
builder gains the same rule as `->notReused()`:

```php
FluentRule::password()->min(12)->uncompromised()->notReused();
```

Three properties are non-negotiable and tested: only **hashes** are stored (never plaintext),
the rule never reveals **which** previous password matched, and a store failure **fails
closed** by default — a password change is a security operation.

## <a name="documentation"></a>Documentation

### Guides

- [Installation](docs/installation.md) — requirements, VCS repositories, the migration
- [Getting started](docs/getting-started.md) — the change-flow in full, signup vs change
- [Configuration](docs/configuration.md) — keep-N, the store binding, the failure policy
- [Architecture](docs/architecture.md) — why a morph, why fail-closed, why `Hash::check()` loops
- [Release](docs/release.md) — versioning and tags

### Reference

- [`UnusedPassword`](docs/tools/unused-password.md) — the rule, user resolution, failure policy
- [The store contract](docs/tools/store.md) — `PasswordHistoryStore`, the Eloquent default, swapping
- [`laranail::password-history.prune`](docs/tools/prune.md) — the schedulable keep-N sweep

### Recipes

- [A password reset flow](docs/recipes/reset-flow.md) · [Admin resets another user](docs/recipes/admin-reset.md) · [Your own store](docs/recipes/custom-store.md)

## Sister packages

- [`laranail/password-tools`](https://github.com/laranail/password-tools) — zxcvbn scoring and secure generators for the same `password()` chain
- [`laranail/validation`](https://github.com/laranail/validation) — the fluent rule builder both compose into

## Contributing & security

See [CONTRIBUTING.md](CONTRIBUTING.md); report vulnerabilities per [SECURITY.md](SECURITY.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
