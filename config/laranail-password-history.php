<?php

declare(strict_types=1);
use Simtabi\Laranail\PasswordHistory\Stores\EloquentPasswordHistoryStore;

/*
 * Read under the flat `laranail.password-history.*` key, per the org config
 * convention; the file is prefixed so `vendor:publish` cannot clobber an
 * application's own config.
 */
return [

    // How many previous passwords to remember and forbid.
    'keep' => (int) env('LARANAIL_PASSWORD_HISTORY_KEEP', 5),

    // The PasswordHistoryStore binding — swap for an app-owned store.
    'store' => EloquentPasswordHistoryStore::class,

    'table' => 'password_histories',

    // The user model the trait lives on — the prune command and the opt-in
    // observer both read this.
    'model' => 'App\Models\User',

    'password_column' => 'password',

    // The opt-in observer: record automatically when the password column
    // changes on save. Explicit recordPassword() at the point of change is
    // the recommended path; this exists for apps that cannot touch their
    // reset flow.
    'record_on_save' => false,

    'connection' => null,

    /*
     * What the reuse rule does when the history store itself errors.
     * 'fail' (default) FAILS CLOSED: a password change is a security
     * operation, and silently allowing a possibly-reused password because
     * a table was unreachable is the worse error. 'degrade' opts into
     * availability: the error is report()ed and the rule passes.
     */
    'on_store_error' => 'fail',
];
