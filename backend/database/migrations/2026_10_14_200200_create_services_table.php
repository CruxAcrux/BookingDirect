<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('buffer_minutes')->default(0);
            $table->unsignedInteger('price_ore');
            $table->unsignedSmallInteger('rebook_after_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement('ALTER TABLE services ADD CONSTRAINT services_duration_positive CHECK (duration_minutes > 0)');

        Schema::create('barber_service', function (Blueprint $table) {
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price_ore')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();

            $table->primary(['barber_id', 'service_id']);
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barber_service');
        Schema::dropIfExists('services');
    }
};
