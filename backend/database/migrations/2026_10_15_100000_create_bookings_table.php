<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barber_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            // End of the service plus its cleanup buffer: the barber is busy until then.
            $table->timestampTz('blocked_until');
            $table->string('status', 20);
            $table->string('source', 20);
            $table->unsignedInteger('price_ore');
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('buffer_minutes');
            $table->text('customer_note')->nullable();
            $table->text('staff_note')->nullable();
            $table->string('manage_token_hash', 64)->unique();
            $table->timestampTz('cancelled_at')->nullable();
            $table->string('cancelled_by', 20)->nullable();
            $table->text('cancellation_message')->nullable();
            $table->timestampsTz();

            $table->index(['shop_id', 'starts_at']);
            $table->index(['barber_id', 'starts_at']);
            $table->index(['customer_id', 'starts_at']);
        });

        DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_range_valid CHECK (ends_at > starts_at AND blocked_until >= ends_at)');

        // The database, not the application, guarantees a barber is never double-booked:
        // two active bookings for the same barber may not have overlapping time ranges.
        DB::statement("ALTER TABLE bookings ADD COLUMN during tstzrange GENERATED ALWAYS AS (tstzrange(starts_at, blocked_until, '[)')) STORED");
        DB::statement(<<<'SQL'
            ALTER TABLE bookings ADD CONSTRAINT bookings_no_overlap
            EXCLUDE USING gist (barber_id WITH =, during WITH &&)
            WHERE (status IN ('pending', 'confirmed'))
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
