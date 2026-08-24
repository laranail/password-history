# Admin resets another user

The form carries the target's id; the rule resolves it instead of the session user.

```php
$request->validate([
    'user_id' => ['required', 'exists:users,id'],
    'password' => ['required', 'confirmed', new UnusedPassword(userField: 'user_id')],
]);
```

Reference: [`UnusedPassword`](../tools/unused-password.md).

---

[← Docs index](../../README.md#documentation)
