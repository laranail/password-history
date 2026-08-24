<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Simtabi\Laranail\PasswordHistory\Contracts\PasswordHistoryStore;
use Simtabi\Laranail\PasswordHistory\Models\PasswordHistory;
use Simtabi\Laranail\PasswordHistory\PasswordHistoryServiceProvider;
use Simtabi\Laranail\PasswordHistory\Rules\UnusedPassword;
use Simtabi\Laranail\PasswordHistory\Tests\Fixtures\User;

function makeUser(string $password = 'current-secret'): User
{
    return User::create([
        'email' => 'alice@example.com',
        'password' => Hash::make($password),
    ]);
}

/** @param array<string, mixed> $data */
function passes(UnusedPassword $rule, string $candidate, array $data = []): bool
{
    return Validator::make(
        ['password' => $candidate, ...$data],
        ['password' => [$rule]],
    )->passes();
}

// =========================================================================
// Reuse detection
// =========================================================================

it('rejects a password from the recorded history', function (): void {
    $user = makeUser();
    $user->recordPassword(Hash::make('old-one'));
    $user->recordPassword(Hash::make('old-two'));

    expect(passes(new UnusedPassword(user: $user), 'old-one'))->toBeFalse()
        ->and(passes(new UnusedPassword(user: $user), 'old-two'))->toBeFalse()
        ->and(passes(new UnusedPassword(user: $user), 'genuinely-new'))->toBeTrue();
});

it('rejects the CURRENT password — changing to what you already have is not a change', function (): void {
    $user = makeUser('current-secret');

    expect(passes(new UnusedPassword(user: $user), 'current-secret'))->toBeFalse();
});

it('forgets beyond keep-N — an old enough password becomes reusable', function (): void {
    config()->set('laranail.password-history.keep', 2);
    $user = makeUser();

    $user->recordPassword(Hash::make('ancient'));
    sleep(0);
    $user->recordPassword(Hash::make('older'));
    $user->recordPassword(Hash::make('newest'));

    // keep=2: 'ancient' was pruned inline by the third record.
    expect(passes(new UnusedPassword(user: $user), 'ancient'))->toBeTrue()
        ->and(passes(new UnusedPassword(user: $user), 'newest'))->toBeFalse();
});

it('passes with no user to resolve — signup is not a history concern', function (): void {
    expect(passes(new UnusedPassword, 'anything'))->toBeTrue();
});

it('resolves the authenticated user when none is given', function (): void {
    $user = makeUser('current-secret');
    Auth::setUser($user);

    expect(passes(new UnusedPassword, 'current-secret'))->toBeFalse()
        ->and(passes(new UnusedPassword, 'brand-new'))->toBeTrue();
});

it('resolves the user from a form field for admin-reset flows', function (): void {
    $user = makeUser('current-secret');

    $rule = new UnusedPassword(userField: 'user_id');

    expect(passes($rule, 'current-secret', ['user_id' => $user->id]))->toBeFalse()
        ->and(passes(new UnusedPassword(userField: 'user_id'), 'fresh', ['user_id' => $user->id]))->toBeTrue();
});

it('never reveals which previous password matched', function (): void {
    $user = makeUser();
    $user->recordPassword(Hash::make('old-one'));

    $validator = Validator::make(
        ['password' => 'old-one'],
        ['password' => [new UnusedPassword(user: $user)]],
    );

    $message = $validator->errors()->first('password');

    expect($message)->not->toContain('old-one')
        ->and($message)->toContain('used recently');
});

// =========================================================================
// Failure handling — fail closed by default (owner decision, 2026-08-24)
// =========================================================================

final class BrokenStore implements PasswordHistoryStore
{
    public function recent(Authenticatable $user, int $keep): iterable
    {
        throw new RuntimeException('history store is down');
    }

    public function record(Authenticatable $user, string $hash): void
    {
        throw new RuntimeException('history store is down');
    }

    public function prune(Authenticatable $user, int $keep): int
    {
        throw new RuntimeException('history store is down');
    }

    public function forget(Authenticatable $user): int
    {
        throw new RuntimeException('history store is down');
    }
}

it('fails CLOSED when the store errors — a password change is a security operation', function (): void {
    $user = makeUser();
    app()->instance(PasswordHistoryStore::class, new BrokenStore);

    $validator = Validator::make(
        ['password' => 'candidate-password'],
        ['password' => [new UnusedPassword(user: $user)]],
    );

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->first('password'))->toContain('could not be verified');
});

it('degrades only when the application explicitly opts in', function (): void {
    config()->set('laranail.password-history.on_store_error', 'degrade');
    $user = makeUser();
    app()->instance(PasswordHistoryStore::class, new BrokenStore);

    expect(passes(new UnusedPassword(user: $user), 'candidate-password'))->toBeTrue();
});

// =========================================================================
// Trait helpers, pruning, purge
// =========================================================================

it('records idempotently — the newest hash is never doubled', function (): void {
    $user = makeUser();
    $hash = Hash::make('old-one');

    $user->recordPassword($hash);
    $user->recordPassword($hash);

    expect(PasswordHistory::query()->count())->toBe(1);
});

it('prunes to keep-N through the trait and reports removals', function (): void {
    config()->set('laranail.password-history.keep', 10);
    $user = makeUser();

    foreach (range(1, 6) as $i) {
        $user->recordPassword(Hash::make("old-{$i}"));
    }

    expect($user->prunePasswordHistory(2))->toBe(4)
        ->and($user->passwordHistory()->count())->toBe(2);
});

it('answers hasUsedPassword without a validator in sight', function (): void {
    $user = makeUser('current-secret');
    $user->recordPassword(Hash::make('archived'));

    expect($user->hasUsedPassword('archived'))->toBeTrue()
        ->and($user->hasUsedPassword('current-secret'))->toBeTrue()
        ->and($user->hasUsedPassword('never-used'))->toBeFalse();
});

it('purges the whole history when the user is deleted', function (): void {
    $user = makeUser();
    $user->recordPassword(Hash::make('old-one'));
    expect(PasswordHistory::query()->count())->toBe(1);

    $user->delete();

    expect(PasswordHistory::query()->count())->toBe(0);
});

it('stores only hashes — the plaintext never lands in the table', function (): void {
    $user = makeUser();
    $user->recordPassword(Hash::make('super-plain-secret'));

    $stored = PasswordHistory::query()->value('hash');
    $hash = is_string($stored) ? $stored : '';

    expect($hash)->not->toBe('')
        ->and($hash)->not->toContain('super-plain-secret')
        ->and(Hash::check('super-plain-secret', $hash))->toBeTrue();
});

// =========================================================================
// The prune command
// =========================================================================

it('prunes every user through the org-named command', function (): void {
    config()->set('laranail.password-history.keep', 10);
    $alice = makeUser();
    $bob = User::create(['email' => 'bob@example.com', 'password' => Hash::make('x')]);

    foreach (range(1, 4) as $i) {
        $alice->recordPassword(Hash::make("a{$i}"));
        $bob->recordPassword(Hash::make("b{$i}"));
    }

    $this->artisan('laranail::password-history.prune', ['--keep' => 1])->assertSuccessful();

    expect($alice->passwordHistory()->count())->toBe(1)
        ->and($bob->passwordHistory()->count())->toBe(1);
});

it('prunes a single user when asked', function (): void {
    config()->set('laranail.password-history.keep', 10);
    $alice = makeUser();
    $bob = User::create(['email' => 'bob@example.com', 'password' => Hash::make('x')]);
    $alice->recordPassword(Hash::make('a1'));
    $alice->recordPassword(Hash::make('a2'));
    $bob->recordPassword(Hash::make('b1'));
    $bob->recordPassword(Hash::make('b2'));

    $this->artisan('laranail::password-history.prune', ['--user' => $alice->id, '--keep' => 1])
        ->assertSuccessful();

    expect($alice->passwordHistory()->count())->toBe(1)
        ->and($bob->passwordHistory()->count())->toBe(2);
});

// =========================================================================
// The opt-in observer
// =========================================================================

it('records the old hash on a real password change when opted in', function (): void {
    config()->set('laranail.password-history.record_on_save', true);
    app()->register(PasswordHistoryServiceProvider::class, force: true);

    $user = makeUser('first-secret');
    $oldHash = $user->password;

    $user->update(['password' => Hash::make('second-secret')]);

    expect(PasswordHistory::query()->count())->toBe(1)
        ->and(PasswordHistory::query()->value('hash'))->toBe($oldHash);
});

it('records nothing when a save does not touch the password', function (): void {
    config()->set('laranail.password-history.record_on_save', true);
    app()->register(PasswordHistoryServiceProvider::class, force: true);

    $user = makeUser();
    $user->update(['email' => 'renamed@example.com']);

    expect(PasswordHistory::query()->count())->toBe(0);
});

it('stays inert without the opt-in', function (): void {
    $user = makeUser('first-secret');
    $user->update(['password' => Hash::make('second-secret')]);

    expect(PasswordHistory::query()->count())->toBe(0);
});

// =========================================================================
// The swappable store
// =========================================================================

it('speaks only the store contract — a fake store carries the whole rule', function (): void {
    $user = makeUser('current-secret');

    $fake = new class implements PasswordHistoryStore
    {
        /** @var list<string> */
        public array $hashes = [];

        public function recent(Authenticatable $user, int $keep): iterable
        {
            return array_slice($this->hashes, 0, $keep);
        }

        public function record(Authenticatable $user, string $hash): void
        {
            array_unshift($this->hashes, $hash);
        }

        public function prune(Authenticatable $user, int $keep): int
        {
            $removed = max(0, count($this->hashes) - $keep);
            $this->hashes = array_slice($this->hashes, 0, $keep);

            return $removed;
        }

        public function forget(Authenticatable $user): int
        {
            $count = count($this->hashes);
            $this->hashes = [];

            return $count;
        }
    };

    app()->instance(PasswordHistoryStore::class, $fake);
    $fake->record($user, Hash::make('remembered-elsewhere'));

    expect(passes(new UnusedPassword(user: $user), 'remembered-elsewhere'))->toBeFalse()
        ->and(PasswordHistory::query()->count())->toBe(0);
});
