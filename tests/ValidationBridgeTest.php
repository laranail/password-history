<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Simtabi\Laranail\Validation\FluentRule;
use Simtabi\Laranail\PasswordHistory\Tests\Fixtures\User;
use Simtabi\Laranail\Package\Tools\Testing\TestCredentials;
use Simtabi\Laranail\Validation\Builder\Nodes\PasswordRule;

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
    $user = User::create(['email' => 'alice@example.com', 'password' => Hash::make(TestCredentials::password('current'))]);
    $user->recordPassword(Hash::make(TestCredentials::password('archived')));
    Auth::setUser($user);

    $rules = ['password' => [FluentRule::password(defaults: false)->notReused()]];

    // The last expectation passes only because the new password is neither of the others.
    expect(TestCredentials::password('novel'))
        ->not->toBe(TestCredentials::password('archived'))
        ->not->toBe(TestCredentials::password('current'));

    expect(Validator::make(['password' => TestCredentials::password('archived')], $rules)->passes())->toBeFalse()
        ->and(Validator::make(['password' => TestCredentials::password('current')], $rules)->passes())->toBeFalse()
        ->and(Validator::make(['password' => TestCredentials::password('novel')], $rules)->passes())->toBeTrue();
});
