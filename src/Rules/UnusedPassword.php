<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PasswordHistory\Rules;

use Closure;
use Throwable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Simtabi\Laranail\PasswordHistory\Contracts\PasswordHistoryStore;

/**
 * Fails when the candidate matches one of the user's last N password
 * hashes OR the live current one — a password-CHANGE rule, not a signup
 * rule. On signup there is no user to have a history: the rule resolves
 * nobody and passes, deliberately (enforce first-password quality with a
 * strength rule, not history).
 *
 * Database-tier by nature: it reads stored hashes and can never run
 * client-side. It never reveals WHICH previous password matched, and the
 * comparison is `Hash::check()` per hash — salts differ per entry, so a
 * WHERE-equality shortcut is impossible and would be wrong.
 *
 * A store failure FAILS CLOSED by default: a password change is a
 * security operation, and silently allowing a possibly-reused password
 * because a table was unreachable is the worse error. The application
 * opts into degrading via config `on_store_error => 'degrade'`.
 */
final class UnusedPassword implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(
        private readonly ?Authenticatable $user = null,
        private readonly ?int $keep = null,
        private readonly ?string $userField = null,
        private readonly ?string $message = null,
    ) {}

    /** @param array<string, mixed> $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $user = $this->resolveUser();

        if (! $user instanceof Authenticatable) {
            return;
        }

        try {
            $used = $this->usedBefore($user, $value);
        } catch (Throwable $exception) {
            if ($this->degradeOnStoreError()) {
                report($exception);

                return;
            }

            $fail($this->message ?? 'laranail/password-history::messages.unavailable')->translate();

            return;
        }

        if ($used) {
            $fail($this->message ?? 'laranail/password-history::messages.reused')->translate();
        }
    }

    private function usedBefore(Authenticatable $user, string $plain): bool
    {
        $current = $user->getAuthPassword();

        if (is_string($current) && $current !== '' && Hash::check($plain, $current)) {
            return true;
        }

        /** @var PasswordHistoryStore $store */
        $store = app(PasswordHistoryStore::class);

        foreach ($store->recent($user, $this->effectiveKeep()) as $hash) {
            if (Hash::check($plain, $hash)) {
                return true;
            }
        }

        return false;
    }

    private function resolveUser(): ?Authenticatable
    {
        if ($this->user instanceof Authenticatable) {
            return $this->user;
        }

        if ($this->userField !== null) {
            $id = $this->data[$this->userField] ?? null;

            if ($id === null) {
                return null;
            }

            $model = config('laranail.password-history.model');

            if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
                return null;
            }

            $found = $model::query()->find($id);

            return $found instanceof Authenticatable ? $found : null;
        }

        return Auth::user();
    }

    private function effectiveKeep(): int
    {
        if ($this->keep !== null) {
            return $this->keep;
        }

        $keep = config('laranail.password-history.keep', 5);

        return is_int($keep) ? $keep : 5;
    }

    private function degradeOnStoreError(): bool
    {
        return config('laranail.password-history.on_store_error') === 'degrade';
    }
}
