<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Encrypted with the app key so later messages (reminders) can link to the
            // manage page. Lookups still go through manage_token_hash.
            $table->text('manage_token')->nullable()->after('manage_token_hash');
            $table->timestampTz('scheduled_at')->nullable()->after('blocked_until');
            $table->timestampTz('reminded_24h_at')->nullable()->after('cancellation_message');
            $table->timestampTz('reminded_2h_at')->nullable()->after('reminded_24h_at');
        });

        DB::table('bookings')->update(['scheduled_at' => DB::raw('created_at')]);

        Schema::table('bookings', function (Blueprint $table) {
            $table->timestampTz('scheduled_at')->nullable(false)->change();
            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['status', 'starts_at']);
            $table->dropColumn(['manage_token', 'scheduled_at', 'reminded_24h_at', 'reminded_2h_at']);
        });
    }
};
