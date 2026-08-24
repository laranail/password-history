# Configuration

All keys live under the flat `laranail.password-history.*`; the publishable file is
`config/laranail-password-history.php`.

| Key | Default | Meaning |
|---|---|---|
| `keep` | `5` | Previous passwords remembered and forbidden (`LARANAIL_PASSWORD_HISTORY_KEEP`) |
| `store` | `EloquentPasswordHistoryStore::class` | The `PasswordHistoryStore` binding |
| `table` | `password_histories` | Histories table |
| `model` | `App\Models\User` | The user model (prune command + observer) |
| `password_column` | `password` | Column the observer watches |
| `record_on_save` | `false` | The opt-in observer |
| `connection` | `null` | Database connection |
| `on_store_error` | `'fail'` | `'fail'` = fail closed (default); `'degrade'` = report and pass |

The failure policy is the one security decision here: `'fail'` rejects a password change when
history cannot be checked, because silently allowing a possibly-reused password is the worse
error. Opt into `'degrade'` only when availability genuinely outranks the reuse guarantee.

---

[← Docs index](../README.md#documentation)
