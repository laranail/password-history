<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PasswordHistory\Providers;

use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\PasswordHistory\Commands\PruneCommand;
use Simtabi\Laranail\PasswordHistory\Contracts\PasswordHistoryStore;
use Simtabi\Laranail\PasswordHistory\Observers\PasswordChangeObserver;
use Simtabi\Laranail\PasswordHistory\Rules\UnusedPassword;
use Simtabi\Laranail\PasswordHistory\Stores\EloquentPasswordHistoryStore;

class PasswordHistoryServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/password-history')
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        // The prefixed file with the flat org key, per the family pattern.
        $this->mergeConfigFrom($this->configPath(), 'laranail.password-history');

        // singletonIf: an application (or another package) that bound its
        // own store wins regardless of provider order.
        $this->app->singletonIf(PasswordHistoryStore::class, EloquentPasswordHistoryStore::class);

        // The observer MUST be a singleton: Model::observe registers
        // Class@method strings the dispatcher resolves per event, and the
        // old-hash stash captured at `updating` has to be the same object
        // answering at `updated`.
        $this->app->singleton(PasswordChangeObserver::class);
    }

    public function bootingPackage(): void
    {
        $this->loadMigrationsFrom($this->packagePath('database/migrations'));

        $this->registerObserver();
        $this->registerValidationBridge();

        if ($this->app->runningInConsole()) {
            $this->publishes(
                [$this->configPath() => config_path('laranail-password-history.php')],
                $this->package->getNamespacedPublishTag('config'),
            );

            $this->publishes(
                [$this->packagePath('database/migrations') => database_path('migrations')],
                $this->package->getNamespacedPublishTag('migrations'),
            );

            $this->commands([PruneCommand::class]);
        }
    }

    /** The opt-in observer (config `record_on_save`) on the configured model. */
    private function registerObserver(): void
    {
        if (config('laranail.password-history.record_on_save') !== true) {
            return;
        }

        $model = config('laranail.password-history.model');

        if (is_string($model) && class_exists($model)) {
            $model::observe(PasswordChangeObserver::class);
        }
    }

    /**
     * The one-way, guarded bridge (design §4.2): when laranail/validation
     * is installed, its password() builder node gains ->notReused(). The
     * validator itself knows nothing of this package.
     */
    private function registerValidationBridge(): void
    {
        $node = '\Simtabi\Laranail\Validation\Builder\Nodes\PasswordRule';

        if (! class_exists($node)) {
            return;
        }

        $node::macro('notReused', fn (?int $keep = null): mixed => $this->rule(new UnusedPassword(keep: $keep)));
    }

    private function configPath(): string
    {
        return $this->packagePath('config/laranail-password-history.php');
    }
}
