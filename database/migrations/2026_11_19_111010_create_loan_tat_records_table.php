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
        Schema::create('loan_tat_records', function (Blueprint $table) {
            $table->id();
            $table->string('loan_account_number', 50)->index();
            $table->string('customer_name', 150);
            $table->string('mobile_no', 30)->nullable();
            $table->string('product_name', 100)->nullable();
            $table->date('login_date')->index();
            $table->date('disbursal_date')->index();
            $table->string('sourcing_rm_id', 50)->nullable();
            $table->string('sourcing_rm_name', 150)->nullable();
            $table->string('branch_name', 100)->index();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('state', 100)->nullable();
            $table->string('loan_status', 50)->default('Active');
            $table->decimal('days', 8, 2)->default(0);
            $table->decimal('branch_average_days', 8, 2)->nullable();
            $table->string('financial_quarter', 20)->nullable();
            $table->string('financial_year', 20)->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'disbursal_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_tat_records');
    }
};
