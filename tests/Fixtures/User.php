<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PasswordHistory\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Simtabi\Laranail\PasswordHistory\Concerns\HasPasswordHistory;

class User extends Authenticatable
{
    use HasPasswordHistory;

    protected $table = 'users';

    protected $guarded = [];
}
