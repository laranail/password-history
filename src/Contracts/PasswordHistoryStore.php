<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PasswordHistory\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The injectable seam between the reuse rule and wherever hashes live. An
 * application with an existing history table, a different schema, or an
 * external credential service implements this instead of adopting the
 * shipped model — the rule, the trait and the prune command all speak
 * only this contract.
 *
 * Every value crossing this boundary is an ALREADY-HASHED password. No
 * implementation may ever receive or return plaintext.
 */
interface PasswordHistoryStore
{
    /**
     * The user's most recent stored hashes, newest first, at most $keep.
     *
     * @return iterable<string>
     */
    public function recent(Authenticatable $user, int $keep): iterable;

    /** Append a hash to the user's history. */
    public function record(Authenticatable $user, string $hash): void;

    /** Trim the user's history to the newest $keep rows; returns rows removed. */
    public function prune(Authenticatable $user, int $keep): int;

    /** Remove the user's entire history — the user-deletion purge. */
    public function forget(Authenticatable $user): int;
}
