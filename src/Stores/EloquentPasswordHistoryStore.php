<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PasswordHistory\Stores;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Simtabi\Laranail\PasswordHistory\Contracts\PasswordHistoryStore;
use Simtabi\Laranail\PasswordHistory\Models\PasswordHistory;

/**
 * The default store over the shipped model. `record()` prunes inline to
 * keep-N — pruning is a privacy control, not optional hygiene (§3.4 of
 * the design): keep-N bounds how long old credential hashes linger, and
 * an app that never schedules the command still gets the bound.
 */
final class EloquentPasswordHistoryStore implements PasswordHistoryStore
{
    public function recent(Authenticatable $user, int $keep): iterable
    {
        $hashes = $this->query($user)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($keep)
            ->pluck('hash')
            ->all();

        return array_values(array_filter($hashes, is_string(...)));
    }

    public function record(Authenticatable $user, string $hash): void
    {
        $model = new PasswordHistory([
            'user_type' => $user instanceof Model ? $user->getMorphClass() : $user::class,
            'user_id' => $user->getAuthIdentifier(),
            'hash' => $hash,
            'created_at' => now(),
        ]);
        $model->save();

        $keep = config('laranail.password-history.keep', 5);
        $this->prune($user, is_int($keep) ? $keep : 5);
    }

    public function prune(Authenticatable $user, int $keep): int
    {
        $keepIds = $this->query($user)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(max(0, $keep))
            ->pluck('id')
            ->all();

        $deleted = $this->query($user)
            ->when($keepIds !== [], fn (Builder $query) => $query->whereNotIn('id', $keepIds))
            ->delete();

        return is_numeric($deleted) ? (int) $deleted : 0;
    }

    public function forget(Authenticatable $user): int
    {
        $deleted = $this->query($user)->delete();

        return is_numeric($deleted) ? (int) $deleted : 0;
    }

    /** @return Builder<PasswordHistory> */
    private function query(Authenticatable $user): Builder
    {
        return PasswordHistory::query()
            ->where('user_type', $user instanceof Model ? $user->getMorphClass() : $user::class)
            ->where('user_id', $user->getAuthIdentifier());
    }
}
