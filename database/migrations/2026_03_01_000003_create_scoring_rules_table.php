<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->integer('min_days');
            $table->integer('max_days');
            $table->string('description', 255)->nullable();
            $table->integer('score')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['min_days', 'max_days']);
            $table->index('is_deleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_rules');
    }
};
