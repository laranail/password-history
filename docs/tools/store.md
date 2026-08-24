# The store contract

`Contracts\PasswordHistoryStore` is the seam every component speaks.

```php
interface PasswordHistoryStore
{
    /** @return iterable<string> newest first, at most $keep */
    public function recent(Authenticatable $user, int $keep): iterable;
    public function record(Authenticatable $user, string $hash): void;
    public function prune(Authenticatable $user, int $keep): int;
    public function forget(Authenticatable $user): int;
}
```

Every value crossing the boundary is an already-hashed password — no implementation may
receive or return plaintext.

The default `EloquentPasswordHistoryStore` persists to the shipped morph model and prunes
inline on `record()` (keep-N is a privacy bound, enforced even when the sweep command never
runs). Bind your own implementation in a provider — the package's `singletonIf` defers to it.

---

[← Docs index](../../README.md#documentation)
