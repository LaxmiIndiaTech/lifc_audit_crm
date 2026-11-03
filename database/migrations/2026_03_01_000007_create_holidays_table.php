<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->index();
            $table->string('holiday_name');
            $table->string('state_name')->nullable()->comment('Null means National/All-India holiday');
            $table->unsignedBigInteger('state_id')->nullable();
            $table->string('type')->default('bank_holiday')->comment('bank_holiday, public_holiday, regional');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['holiday_date', 'state_id'], 'unique_holiday_date_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
