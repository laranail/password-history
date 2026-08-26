<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PasswordHistory\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Simtabi\Laranail\PasswordHistory\Providers\PasswordHistoryServiceProvider;
use Simtabi\Laranail\PasswordHistory\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('laranail.password-history.model', User::class);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });

        $this->artisan('migrate')->run();
    }

    protected function getPackageProviders($app): array
    {
        return [PasswordHistoryServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        // bcrypt at minimum cost: these tests hash constantly and assert
        // logic, not work factors.
        $app['config']->set('hashing.bcrypt.rounds', 4);
    }
}
