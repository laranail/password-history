# `laranail::password-history.prune`

```
php artisan laranail::password-history.prune {--user=} {--keep=}
```

Trims every user's history (or one user's, `--user=<id>`) to the newest keep-N. The store
already prunes inline on record, so this sweep exists for history written before the package,
imported data, or a lowered `keep`. Schedulable:

```php
Schedule::command('laranail::password-history.prune')->daily();
```

---

[← Docs index](../../README.md#documentation)
