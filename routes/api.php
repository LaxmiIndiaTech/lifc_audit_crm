<?php

use App\Http\Controllers\Api\V1\Admin\AuditInspectionController;
use App\Http\Controllers\Api\V1\Admin\BranchesController;
use App\Http\Controllers\Api\V1\Admin\CashChequeReceiptsController;
use App\Http\Controllers\Api\V1\Admin\CitiesController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\EmployeesController;
use App\Http\Controllers\Api\V1\Admin\FeedbackRepliesController;
use App\Http\Controllers\Api\V1\Admin\FeedbackThreadsController;
use App\Http\Controllers\Api\V1\Admin\HolidaysController;
use App\Http\Controllers\Api\V1\Admin\PersonalAccountTransactionsController;
use App\Http\Controllers\Api\V1\Admin\ScoringRulesController;
use App\Http\Controllers\Api\V1\Admin\StatesController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Map both /v1/... and /api/v1/... for universal compatibility
$defineRoutes = function () {
    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('request_otp', [AuthController::class, 'requestOtp']);
        Route::post('verify_otp', [AuthController::class, 'verifyOtp']);
        Route::get('validate_employee_roles', [AuthController::class, 'validateEmployeeRoles']);
    });

    // Admin Routes
    Route::prefix('admin')->group(function () {
        // Dashboard & Inspection (Exact CRM Vision endpoints)
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('dashboard/{id}/branch_inspection', [DashboardController::class, 'branchInspection']);
        Route::post('dashboard/{id}/send_inspection_report', [DashboardController::class, 'sendInspectionReport']);
        Route::get('branch_inspection/{id}', [DashboardController::class, 'branchInspection']);

        // Branches
        Route::get('branches', [BranchesController::class, 'index']);
        Route::post('branches', [BranchesController::class, 'store']);
        Route::get('branches/{id}', [BranchesController::class, 'show']);
        Route::put('branches/{id}', [BranchesController::class, 'update']);
        Route::get('branches/{id}/feedback_threads', [FeedbackThreadsController::class, 'index']);
        Route::post('branches/{id}/feedback_threads', [FeedbackThreadsController::class, 'create']);

        // Feedback Replies
        Route::get('feedback_threads/{threadId}/replies', [FeedbackRepliesController::class, 'index']);
        Route::post('feedback_threads/{threadId}/replies', [FeedbackRepliesController::class, 'create']);

        // Scoring Rules
        Route::post('scoring_rules/sync', [ScoringRulesController::class, 'sync']);
        Route::apiResource('scoring_rules', ScoringRulesController::class);

        // Cash Cheque Receipts (A.1)
        Route::post('cash_cheque_receipts/import', [CashChequeReceiptsController::class, 'import']);
        Route::get('cash_cheque_receipts', [CashChequeReceiptsController::class, 'index']);
        Route::get('cash_cheque_receipts/{id}', [CashChequeReceiptsController::class, 'show']);

        // Personal Account Transactions (A.2)
        Route::post('personal_account_transactions/import', [PersonalAccountTransactionsController::class, 'import']);
        Route::get('personal_account_transactions', [PersonalAccountTransactionsController::class, 'index']);

        // Holidays & Banking Calendar
        Route::get('holidays', [HolidaysController::class, 'index']);
        Route::post('holidays', [HolidaysController::class, 'store']);
        Route::delete('holidays/{id}', [HolidaysController::class, 'destroy']);
        Route::post('holidays/import', [HolidaysController::class, 'import']);
        Route::post('holidays/recalculate', [HolidaysController::class, 'recalculateReceipts']);

        // States & Cities
        Route::get('states', [StatesController::class, 'index']);
        Route::get('states/{id}', [StatesController::class, 'show']);
        Route::get('cities', [CitiesController::class, 'index']);
        Route::get('cities/{id}', [CitiesController::class, 'show']);

        // Employees
        Route::get('employees', [EmployeesController::class, 'index']);
        Route::get('employees/{id}', [EmployeesController::class, 'show']);
        Route::post('employees/add_employee_role', [EmployeesController::class, 'addEmployeeRole']);

        // Inspection specific updates
        Route::post('inspections/{inspectionId}/update_scores', [AuditInspectionController::class, 'updateCheckpoints']);
        Route::post('inspections/{inspectionId}/re_evaluate', [AuditInspectionController::class, 'reEvaluate']);
    });
};

Route::prefix('v1')->group($defineRoutes);
Route::prefix('api/v1')->group($defineRoutes);
