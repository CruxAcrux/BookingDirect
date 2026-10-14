<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Weekly hours are local wall-clock times in the shop's timezone (ISO weekday, 1 = Monday).
        Schema::create('opening_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('weekday');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->timestampsTz();

            $table->index(['shop_id', 'weekday']);
        });

        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestampsTz();

            $table->index(['barber_id', 'weekday']);
        });

        Schema::create('breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestampsTz();

            $table->index(['barber_id', 'weekday']);
        });

        Schema::create('time_off', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('reason', 20);
            $table->string('note')->nullable();
            $table->timestampsTz();

            $table->index(['barber_id', 'starts_at']);
        });

        DB::statement('ALTER TABLE opening_hours ADD CONSTRAINT opening_hours_weekday_valid CHECK (weekday BETWEEN 1 AND 7)');
        DB::statement('ALTER TABLE opening_hours ADD CONSTRAINT opening_hours_range_valid CHECK (closes_at > opens_at)');

        foreach (['working_hours', 'breaks'] as $table) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_weekday_valid CHECK (weekday BETWEEN 1 AND 7)");
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_range_valid CHECK (ends_at > starts_at)");
        }

        DB::statement('ALTER TABLE time_off ADD CONSTRAINT time_off_range_valid CHECK (ends_at > starts_at)');
    }

    public function down(): void
    {
        Schema::dropIfExists('time_off');
        Schema::dropIfExists('breaks');
        Schema::dropIfExists('working_hours');
        Schema::dropIfExists('opening_hours');
    }
};
