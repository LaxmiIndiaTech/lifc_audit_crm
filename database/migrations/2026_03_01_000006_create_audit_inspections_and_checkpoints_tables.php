<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master definition of audit checkpoints (Sections A to G, etc.)
        Schema::create('audit_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('section', 5)->index(); // A, B, C, D, E, F, G
            $table->string('section_name', 150);
            $table->string('checkpoint_code', 20)->unique(); // A.1, A.2, B.1, etc.
            $table->text('scope_point');
            $table->decimal('carry_marks', 8, 4);
            $table->decimal('weight', 8, 4)->default(1);
            $table->string('default_department', 50)->nullable(); // CMS, Ops, Admin, IT, etc.
            $table->boolean('is_auto_calculated')->default(false); // true for A.1, A.2
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Main inspection report card per branch per date/quarter
        Schema::create('audit_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('inspection_date');
            $table->string('inspection_month', 20)->nullable(); // e.g. "2026-05"
            $table->string('financial_quarter', 10)->default('Q4');
            $table->string('financial_year', 20)->default('2025-2026');
            $table->string('company_name')->default('Laxmi India Finance Ltd.');
            $table->string('branch_in_charge_name')->nullable();
            $table->string('branch_in_charge_code', 20)->nullable();
            $table->string('cluster_name')->nullable();
            $table->string('cluster_code', 20)->nullable();
            $table->string('inspection_officials')->default('Internal Audit Team.');
            
            // Section I: Process (A to G)
            $table->decimal('process_marks_carry', 8, 4)->default(40.00);
            $table->decimal('process_marks_obtained', 8, 4)->default(0.00);
            $table->decimal('process_score_pct', 8, 4)->default(0.00);

            // Section II: Performance (H to M)
            $table->decimal('performance_marks_carry', 8, 4)->default(60.00);
            $table->decimal('performance_marks_obtained', 8, 4)->default(0.00);
            $table->decimal('performance_score_pct', 8, 4)->default(0.00);

            // Overall Total
            $table->decimal('total_marks_carry', 8, 4)->default(100.00);
            $table->decimal('total_marks_obtained', 8, 4)->default(0.00);
            $table->decimal('total_score_pct', 8, 4)->default(0.00);
            $table->string('final_grade', 50)->nullable(); // Excellent, Good, Satisfactory, Unsatisfactory

            $table->string('status', 30)->default('draft'); // draft, submitted, completed
            $table->text('inspecting_official_signature')->nullable();
            $table->text('branch_incharge_signature')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'inspection_date']);
        });

        // Scores and findings for each checkpoint in an inspection
        Schema::create('inspection_checkpoint_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_inspection_id')->constrained('audit_inspections')->cascadeOnDelete();
            $table->foreignId('audit_checkpoint_id')->nullable()->constrained('audit_checkpoints')->nullOnDelete();
            $table->string('section', 5)->index();
            $table->string('checkpoint_code', 20)->index();
            $table->text('scope_point')->nullable();
            $table->decimal('carry_marks', 8, 4);
            $table->decimal('marks_obtained', 8, 4)->default(0.00);
            $table->decimal('percentage', 8, 4)->default(0.00);
            $table->decimal('weight', 8, 4)->default(1);
            $table->text('visitor_comment')->nullable();
            $table->text('immediate_action')->nullable();
            $table->string('department', 50)->nullable();
            $table->text('branch_comment')->nullable();
            $table->text('management_view')->nullable();
            $table->json('calculation_data')->nullable(); // stored details like total receipts, delayed count, etc.
            $table->timestamps();

            $table->unique(['audit_inspection_id', 'checkpoint_code'], 'chk_score_insp_code_unique');
        });

        // Feedback threads (linked to branch and score point code)
        Schema::create('feedback_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('audit_inspection_id')->nullable()->constrained('audit_inspections')->cascadeOnDelete();
            $table->string('score_point_code', 20)->index();
            $table->string('section', 500);
            $table->text('visitor_comment')->nullable();
            $table->text('immediate_action')->nullable();
            $table->string('inspection_month', 20);
            $table->timestamps();

            $table->index(['branch_id', 'inspection_month']);
        });

        // Feedback replies
        Schema::create('feedback_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_thread_id')->constrained('feedback_threads')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('author_type', 50)->default('employee'); // employee, auditor, admin
            $table->text('text');
            $table->timestamps();
        });

        // Import logs
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->index(); // cash_cheque_receipts, personal_account_transactions, etc.
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status', 30)->default('queued')->index(); // queued, processing, completed, failed
            $table->integer('created_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->json('error_details')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
        Schema::dropIfExists('feedback_replies');
        Schema::dropIfExists('feedback_threads');
        Schema::dropIfExists('inspection_checkpoint_scores');
        Schema::dropIfExists('audit_inspections');
        Schema::dropIfExists('audit_checkpoints');
    }
};
