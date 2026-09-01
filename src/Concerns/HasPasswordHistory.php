<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PasswordHistory\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Hash;
use Simtabi\Laranail\PasswordHistory\Contracts\PasswordHistoryStore;
use Simtabi\Laranail\PasswordHistory\Models\PasswordHistory;

/**
 * For the User model: the relation plus the record/check/prune helpers,
 * and — because a morph cannot cascade — the deletion purge: removing a
 * user removes their credential history, so no orphaned hashes linger
 * past the account they belonged to (the design's GDPR line).
 */
trait HasPasswordHistory
{
    public static function bootHasPasswordHistory(): void
    {
        static::deleted(function (Model $user): void {
            // Soft deletes keep the account and therefore the history; only
            // a real removal purges. forceDeleted() covers the soft path's
            // final deletion below.
            if (method_exists($user, 'isForceDeleting') && ! $user->isForceDeleting()) {
                return;
            }

            /** @var PasswordHistoryStore $store */
            $store = app(PasswordHistoryStore::class);
            $store->forget($user);
        });
    }

    /** @return MorphMany<PasswordHistory, $this> */
    public function passwordHistory(): MorphMany
    {
        return $this->morphMany(PasswordHistory::class, 'user');
    }

    /**
     * Append an already-hashed password. Idempotent against the newest
     * entry: recording the hash that is already history[0] — a double
     * submit, an observer racing an explicit call — records nothing.
     */
    public function recordPassword(string $hashed): void
    {
        /** @var PasswordHistoryStore $store */
        $store = app(PasswordHistoryStore::class);

        foreach ($store->recent($this, 1) as $newest) {
            if (hash_equals($newest, $hashed)) {
                return;
            }
        }

        $store->record($this, $hashed);
    }

    /** Whether the PLAIN candidate matches any of the last $keep hashes or the live password. */
    public function hasUsedPassword(string $plain, ?int $keep = null): bool
    {
        $keep ??= $this->configuredKeep();

        $current = $this->getAuthPassword();

        if (is_string($current) && $current !== '' && Hash::check($plain, $current)) {
            return true;
        }

        /** @var PasswordHistoryStore $store */
        $store = app(PasswordHistoryStore::class);

        foreach ($store->recent($this, $keep) as $hash) {
            if (Hash::check($plain, $hash)) {
                return true;
            }
        }

        return false;
    }

    /** Trim to keep-N; returns rows removed. */
    public function prunePasswordHistory(?int $keep = null): int
    {
        /** @var PasswordHistoryStore $store */
        $store = app(PasswordHistoryStore::class);

        return $store->prune($this, $keep ?? $this->configuredKeep());
    }

    private function configuredKeep(): int
    {
        $keep = config('laranail.password-history.keep', 5);

        return is_int($keep) ? $keep : 5;
    }
}
