<?php

declare(strict_types=1);

namespace Simtabi\Laranail\PasswordHistory\Observers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * OPT-IN (config `record_on_save`): snapshots the OLD password hash when
 * the column actually changes. Explicit `recordPassword()` at the point
 * of change remains the recommended path — this observer exists for the
 * application that cannot touch its reset flow.
 *
 * The old hash is captured at `updating` (the only moment both values
 * are visible) and recorded at `updated` (only once the change is real).
 * A save that touches other columns records nothing.
 */
final class PasswordChangeObserver
{
    /** @var array<string, string> spl_object_hash => old hash */
    private array $pending = [];

    public function updating(Model $model): void
    {
        $column = $this->column();

        if (! $model->isDirty($column)) {
            return;
        }

        $original = $model->getOriginal($column);

        if (is_string($original) && $original !== '') {
            $this->pending[spl_object_hash($model)] = $original;
        }
    }

    public function updated(Model $model): void
    {
        $key = spl_object_hash($model);
        $old = $this->pending[$key] ?? null;
        unset($this->pending[$key]);

        if ($old === null || ! $model instanceof Authenticatable) {
            return;
        }

        if (method_exists($model, 'recordPassword')) {
            $model->recordPassword($old);
        }
    }

    private function column(): string
    {
        $column = config('laranail.password-history.password_column', 'password');

        return is_string($column) ? $column : 'password';
    }
}
