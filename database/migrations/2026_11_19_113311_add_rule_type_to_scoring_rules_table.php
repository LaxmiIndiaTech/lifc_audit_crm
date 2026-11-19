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
        Schema::table('scoring_rules', function (Blueprint $table) {
            $table->string('rule_type', 50)->default('cash_cheque')->after('id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scoring_rules', function (Blueprint $table) {
            $table->dropColumn('rule_type');
        });
    }
};
