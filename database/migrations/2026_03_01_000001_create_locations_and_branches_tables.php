<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('state_name', 100);
            $table->string('state_code', 20)->unique()->nullable();
            $table->string('country')->default('India');
            $table->integer('city_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('city_name', 100);
            $table->string('city_slug', 100)->nullable();
            $table->string('city_code', 20)->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->integer('branch_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_name', 100);
            $table->string('branch_slug', 100)->nullable();
            $table->string('branch_code', 20)->unique()->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('address', 255)->nullable();
            $table->string('pin_code', 10)->nullable();
            $table->integer('employee_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->json('meta')->nullable(); // in_charge_name, in_charge_code, cm_asm_name, cm_asm_code
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
    }
};
