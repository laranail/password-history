<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PasswordHistory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One archived password hash. Rows carry the same bcrypt/argon hashes the
 * application's users table already holds — storing them in a side table
 * adds no new secret material — and each hash is algorithm-tagged by its
 * own format, so `Hash::check()` keeps verifying entries recorded under a
 * previous hashing scheme.
 *
 * The user side is a morph, not a foreign key: applications with several
 * authenticatable models (users, admins) share one table, and the purge
 * on user deletion is the trait's job rather than a constraint's (a morph
 * cannot cascade).
 */
class PasswordHistory extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function getTable(): string
    {
        $table = config('laranail.password-history.table', 'password_histories');

        return is_string($table) ? $table : 'password_histories';
    }

    public function getConnectionName(): ?string
    {
        $connection = config('laranail.password-history.connection');

        return is_string($connection) ? $connection : parent::getConnectionName();
    }

    /** @return MorphTo<Model, $this> */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
