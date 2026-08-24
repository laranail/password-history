# `UnusedPassword`

The reuse rule: fails when the candidate matches the user's live password or any of their
last N archived hashes.

## Constructor

```php
new UnusedPassword(
    ?Authenticatable $user = null,   // explicit user
    ?int $keep = null,               // override config keep-N
    ?string $userField = null,       // resolve from a form field (admin resets)
    ?string $message = null,         // override the failure message
);
```

User resolution order: explicit `$user` → `$userField` in the form data (looked up on the
configured model) → the authenticated user → nobody, in which case the rule PASSES (signup is
not a history concern).

## Failure behaviour

- The reuse message never says which previous password matched.
- A store error fails closed by default (`messages.unavailable`); `on_store_error =>
  'degrade'` reports the exception and passes instead.
- Database-tier: this rule never runs client-side and is not exported to
  `laranail/validation-js` as anything but a server rule name.

---

[← Docs index](../../README.md#documentation)
