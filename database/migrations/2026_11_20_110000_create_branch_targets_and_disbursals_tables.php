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
        // 1. Branch Target & Achievements summary table (for Section I.1)
        Schema::create('branch_target_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('branch_name')->index();
            $table->string('state')->nullable()->index();
            $table->string('financial_quarter', 20)->default('Q1')->index();
            $table->string('financial_year', 20)->default('2026-2027')->index();
            $table->decimal('target_amount', 15, 2)->default(0.00);
            $table->decimal('disbursed_amount', 15, 2)->default(0.00);
            $table->decimal('portfolio_disbursed_amount', 15, 2)->nullable()->default(0.00);
            $table->decimal('incentive_disbursed_amount', 15, 2)->nullable()->default(0.00);
            $table->decimal('achievement_percentage', 8, 2)->default(0.00); // (disbursed_amount / target_amount) * 100
            $table->integer('total_loans')->default(0);
            $table->string('tier')->nullable();
            $table->decimal('marks_obtained', 8, 4)->default(0.0000);
            $table->decimal('carry_marks', 8, 4)->default(15.0000);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'financial_quarter', 'financial_year'], 'bta_branch_quarter_year_unique');
        });

        // 2. Individual Loan Disbursal Records (from Book1 sheet)
        Schema::create('loan_disbursal_records', function (Blueprint $table) {
            $table->id();
            $table->string('loan_account_number')->index();
            $table->string('customer_name')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('branch_name')->index();
            $table->string('state')->nullable();
            $table->decimal('disbursal_amount', 15, 2)->default(0.00);
            $table->date('disbursal_date')->nullable()->index();
            $table->string('disbursal_month', 30)->nullable();
            $table->string('product_name')->nullable();
            $table->string('financial_quarter', 20)->default('Q1')->index();
            $table->string('financial_year', 20)->default('2026-2027')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_disbursal_records');
        Schema::dropIfExists('branch_target_achievements');
    }
};
