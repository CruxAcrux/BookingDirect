<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('timezone')->default('Europe/Stockholm');
            $table->string('locale', 5)->default('sv');
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->unsignedInteger('min_notice_minutes')->default(60);
            $table->unsignedSmallInteger('max_days_ahead')->default(60);
            $table->unsignedSmallInteger('cancellation_window_hours')->default(24);
            $table->unsignedSmallInteger('slot_interval_minutes')->default(15);
            $table->boolean('walk_ins_enabled')->default(true);
            $table->timestampTz('deactivated_at')->nullable();
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE shops ADD CONSTRAINT shops_slot_interval_positive CHECK (slot_interval_minutes > 0)');

        Schema::create('shop_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->timestampsTz();

            $table->unique(['shop_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_user');
        Schema::dropIfExists('shops');
    }
};
