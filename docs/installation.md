# Installation

The package installs from the laranail VCS repositories and ships one migration.

## Requirements

| Dimension | Supported |
|---|---|
| PHP | `^8.4.1 \|\| ^8.5` |
| Laravel | `^13.0` |

## Install

laranail packages resolve through git VCS repositories, not Packagist. Add the transitive
closure to your root `composer.json`:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/laranail/password-history" },
        { "type": "vcs", "url": "https://github.com/laranail/package-tools" },
        { "type": "vcs", "url": "https://github.com/laranail/console" }
    ]
}
```

```bash
composer require laranail/password-history
php artisan migrate
```

The migration creates `password_histories` (configurable name/connection). Publish the config
when defaults need changing:

```bash
php artisan vendor:publish --tag=laranail::password-history-config
```

---

[← Docs index](../README.md#documentation)
