# Your own store

History already lives somewhere else? Implement the contract and bind it — the shipped model
and migration become dead weight you simply don't run.

```php
$this->app->singleton(PasswordHistoryStore::class, LegacyCredentialStore::class);
```

Reference: [the store contract](../tools/store.md).

---

[← Docs index](../../README.md#documentation)
