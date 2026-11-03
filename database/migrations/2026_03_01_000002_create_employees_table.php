<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_first_name', 50);
            $table->string('employee_last_name', 50)->nullable();
            $table->string('employee_code', 20)->unique();
            $table->string('email', 100)->nullable();
            $table->string('mobile_number', 20)->nullable()->index();
            $table->string('designation', 100)->nullable();
            $table->string('department', 50)->nullable();
            $table->date('date_of_joining')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('remarks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
