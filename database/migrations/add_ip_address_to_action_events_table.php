<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('action_events', 'ip_address')) {
            return;
        }

        Schema::table('action_events', function (Blueprint $table): void {
            $table->string('ip_address', 45)->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('action_events', 'ip_address')) {
            return;
        }

        Schema::table('action_events', function (Blueprint $table): void {
            $table->dropColumn('ip_address');
        });
    }
};
