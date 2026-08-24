<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

/**
 * The org naming guard, read from the LIVE registries — never by grepping
 * the provider. Registries are flat maps; a bare name is a silent
 * collision waiting for a sibling package.
 */
it('registers only org-namespaced Artisan commands', function (): void {
    $ours = array_filter(
        array_keys(Artisan::all()),
        static fn (string $name): bool => str_contains($name, 'password-history'),
    );

    expect($ours)->not->toBeEmpty()
        ->and($ours)->toContain('laranail::password-history.prune');

    foreach ($ours as $name) {
        expect($name)->toStartWith('laranail::password-history.');
    }
});

it('reads configuration only from the flat org key', function (): void {
    expect(config('laranail.password-history'))->toBeArray()
        ->and(config('password-history'))->toBeNull();
});

it('resolves translations only under the vendored namespace', function (): void {
    $namespaced = 'laranail-password-history::messages.reused';
    $bare = 'password-history::messages.reused';

    expect(trans($namespaced))->not->toBe($namespaced)
        ->and(trans($bare))->toBe($bare);
});

it('namespaces its publish tags', function (): void {
    // Read the raw registry: publishableGroups() proved empty under
    // testbench while the groups were demonstrably registered.
    $reflection = new ReflectionClass(ServiceProvider::class);
    $groups = array_keys($reflection->getProperty('publishGroups')->getValue());
    $ours = array_filter($groups, fn (int|string $tag): bool => str_contains((string) $tag, 'password-history'));

    expect($ours)->not->toBeEmpty();

    foreach ($ours as $tag) {
        expect((string) $tag)->toStartWith('laranail::password-history');
    }
});
