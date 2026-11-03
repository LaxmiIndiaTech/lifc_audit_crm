<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_name')->nullable();
            $table->string('decentro_transaction_id')->unique()->index();
            $table->dateTime('transaction_date')->nullable()->index();
            $table->string('collection_type')->nullable(); // e.g. Dynamic QR
            $table->decimal('transaction_amount', 12, 2)->default(0);
            $table->string('payer_vpa')->nullable();
            $table->string('mobile_number', 20)->nullable()->index();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employee_code', 20)->nullable()->index();
            $table->string('employee_name')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('branch_name')->nullable();
            $table->string('department')->nullable();
            $table->string('quarter', 10)->nullable(); // e.g. Q4
            $table->string('financial_year', 15)->nullable(); // e.g. 2025-2026
            $table->boolean('is_violation')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_account_transactions');
    }
};
