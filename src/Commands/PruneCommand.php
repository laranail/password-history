<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PasswordHistory\Commands;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;
use Simtabi\Laranail\PasswordHistory\Contracts\PasswordHistoryStore;

/**
 * Keep-N pruning as a schedulable command. Pruning is a privacy control
 * (keep-N bounds how long old credential hashes linger), so the store
 * already prunes inline on record — this command is the sweep for
 * history written before the package, imported data, or a lowered keep.
 */
final class PruneCommand extends Command
{
    use SupportsNamespacedNames;

    protected $signature = 'laranail::password-history.prune
        {--user= : Prune one user by id; omit for all}
        {--keep= : Override the configured keep-N}';

    protected $description = 'Trim stored password history to the newest keep-N entries per user.';

    public function handle(PasswordHistoryStore $store): int
    {
        $model = config('laranail.password-history.model');

        if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
            $this->error('laranail.password-history.model does not name an Eloquent model.');

            return self::FAILURE;
        }

        $keepOption = $this->option('keep');
        $configured = config('laranail.password-history.keep', 5);
        $keep = is_numeric($keepOption) ? (int) $keepOption : (is_int($configured) ? $configured : 5);

        $removed = 0;
        $users = 0;

        $query = $model::query();

        if (is_numeric($this->option('user'))) {
            $query->whereKey((int) $this->option('user'));
        }

        foreach ($query->lazyById() as $user) {
            if (! $user instanceof Authenticatable) {
                continue;
            }

            $removed += $store->prune($user, $keep);
            $users++;
        }

        $this->info("Pruned {$removed} entr".($removed === 1 ? 'y' : 'ies')." across {$users} user".($users === 1 ? '' : 's').", keeping {$keep} per user.");

        return self::SUCCESS;
    }
}
