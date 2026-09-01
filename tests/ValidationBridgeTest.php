<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Simtabi\Laranail\PasswordHistory\Tests\Fixtures\User;
use Simtabi\Laranail\Validation\Builder\Nodes\PasswordRule;
use Simtabi\Laranail\Validation\FluentRule;

/**
 * The §4.2 bridge, proven end to end: with laranail/validation installed,
 * its password() builder node carries ->notReused(). The dependency
 * direction stays one-way — this suite installs the validator as a DEV
 * dependency; the validator never knows this package exists, and every
 * other test in this suite passes with the validator absent.
 */
beforeEach(function (): void {
    if (! class_exists(PasswordRule::class)) {
        $this->markTestSkipped('laranail/validation not installed');
    }
});

it('teaches password() the notReused macro', function (): void {
    expect(PasswordRule::hasMacro('notReused'))->toBeTrue();
});

it('rejects reuse through the fluent chain, end to end', function (): void {
    $user = User::create(['email' => 'alice@example.com', 'password' => Hash::make('current-secret')]);
    $user->recordPassword(Hash::make('archived-secret'));
    Auth::setUser($user);

    $rules = ['password' => [FluentRule::password(defaults: false)->notReused()]];

    expect(Validator::make(['password' => 'archived-secret'], $rules)->passes())->toBeFalse()
        ->and(Validator::make(['password' => 'current-secret'], $rules)->passes())->toBeFalse()
        ->and(Validator::make(['password' => 'genuinely-new-choice'], $rules)->passes())->toBeTrue();
});
