# Getting started

The rule guards password CHANGES; the trait records them. Signup is deliberately out of
scope — a brand-new user has no history, so the rule passes and first-password quality is a
strength rule's job.

## 1. The trait

```php
use Simtabi\Laranail\PasswordHistory\Concerns\HasPasswordHistory;

class User extends Authenticatable
{
    use HasPasswordHistory;
}
```

## 2. The rule on the change form

```php
use Simtabi\Laranail\PasswordHistory\Rules\UnusedPassword;

$validated = $request->validate([
    'password' => ['required', 'string', 'confirmed', new UnusedPassword()],
]);
```

The rule resolves the authenticated user by default and rejects a candidate matching the
CURRENT password or any of the last N archived hashes.

## 3. Record on accept — in this order

```php
$old = $user->password;                                       // the hash being replaced
$user->update(['password' => Hash::make($validated['password'])]);
$user->recordPassword($old);                                  // archive AFTER acceptance
```

Validate first, persist, then archive the old hash. Recording before acceptance would poison
history with a rejected candidate. `recordPassword()` is idempotent against the newest entry
and prunes inline to keep-N.

Apps that cannot touch their reset flow can opt into the observer instead
([Configuration](configuration.md), `record_on_save`).

## With laranail/validation

```php
FluentRule::password()->min(12)->uncompromised()->notReused();
```

The `->notReused()` macro appears when this package is installed; the validator itself has no
knowledge of it.

---

[← Docs index](../README.md#documentation)
