<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('laranail.password-history.table', 'password_histories');
        $connection = config('laranail.password-history.connection');

        Schema::connection(is_string($connection) ? $connection : null)->create(
            is_string($table) ? $table : 'password_histories',
            function (Blueprint $table): void {
                $table->id();
                // A morph, not a foreign key: several authenticatable models
                // share the table, and the deletion purge is the trait's job
                // (a morph cannot cascade).
                $table->morphs('user');
                $table->string('hash');
                $table->timestamp('created_at');

                $table->index(['user_type', 'user_id', 'created_at']);
            },
        );
    }

    public function down(): void
    {
        $table = config('laranail.password-history.table', 'password_histories');
        $connection = config('laranail.password-history.connection');

        Schema::connection(is_string($connection) ? $connection : null)->dropIfExists(
            is_string($table) ? $table : 'password_histories',
        );
    }
};
