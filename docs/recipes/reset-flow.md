# A password reset flow

Validate → persist → archive, in that order.

```php
$validated = $request->validate([
    'password' => ['required', 'confirmed', new UnusedPassword()],
]);

$old = $user->password;
$user->update(['password' => Hash::make($validated['password'])]);
$user->recordPassword($old);
```

Reference: [`UnusedPassword`](../tools/unused-password.md).

---

[← Docs index](../../README.md#documentation)
