<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_cheque_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('loan_no')->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_mobile_no', 20)->nullable();
            $table->date('created_date')->nullable();
            $table->dateTime('payment_date')->nullable()->index();
            $table->string('payment_month', 20)->nullable();
            $table->decimal('emi_amount_paid', 12, 2)->nullable();
            $table->decimal('late_payment_charges_paid', 12, 2)->nullable();
            $table->decimal('bounce_charges_paid', 12, 2)->nullable();
            $table->decimal('penalty_charges_paid', 12, 2)->nullable();
            $table->decimal('other_charges_paid', 12, 2)->nullable();
            $table->decimal('total_amount_paid', 12, 2)->nullable();
            $table->decimal('charges', 12, 2)->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_channel')->nullable();
            $table->string('receipt_purpose')->nullable();
            $table->string('lms_receipt_id')->nullable()->index();
            $table->dateTime('lms_req_id_updated_at')->nullable();
            $table->decimal('deposit_amount', 12, 2)->nullable();
            $table->string('deposit_status')->nullable();
            $table->date('deposit_date')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('collected_by_name')->nullable();
            $table->unsignedBigInteger('collected_by_id')->nullable();
            $table->string('state_name')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('scoring_rules_id')->nullable()->constrained('scoring_rules')->nullOnDelete();
            $table->integer('gap_in_days')->nullable();
            $table->string('tat_status', 20)->nullable(); // e.g. T+1, T+2, T+3
            $table->boolean('is_over_tat')->default(false); // true if gap_in_days > 1
            $table->timestamps();

            $table->index('deposit_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_cheque_receipts');
    }
};
