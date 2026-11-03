<?php

use App\Models\AuditInspection;
use App\Models\Branch;
use App\Services\AuditInspectionService;
use App\Services\CashChequeReceiptsImportService;
use App\Services\PersonalAccountTransactionsImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (AuditInspectionService $inspectionService) {
    $branch = Branch::first();
    if (!$branch) {
        return 'Please run php artisan db:seed first.';
    }
    return redirect()->route('web.scorecard', ['branchId' => $branch->id]);
});

Route::get('/dashboard', function () {
    return redirect()->route('web.scorecard');
});

Route::get('/scorecard/{branchId?}', function (Request $request, AuditInspectionService $inspectionService, ?int $branchId = null) {
    $branch = $branchId ? Branch::findOrFail($branchId) : Branch::firstOrFail();
    $inspection = $inspectionService->getOrCreateInspection($branch->id, '2026-05-28', 'Q4', '2025-2026');
    $summary = $inspectionService->getSectionWiseSummary($inspection);

    return view('scorecard', compact('branch', 'inspection', 'summary'));
})->name('web.scorecard');

Route::post('/scorecard/upload-receipts', function (Request $request, CashChequeReceiptsImportService $importService, AuditInspectionService $inspectionService) {
    $request->validate(['file' => 'required|file', 'branch_id' => 'required|integer']);
    $branchId = (int)$request->input('branch_id');
    $file = $request->file('file');

    $importService->import($file->getRealPath(), $branchId);

    $inspection = $inspectionService->getOrCreateInspection($branchId, '2026-05-28', 'Q4', '2025-2026');
    $inspectionService->evaluateAutoCheckpoints($inspection);
    $inspection->recalculateTotals();

    return back()->with('success', 'LMS Receipts uploaded and A.1 auto-scored successfully!');
})->name('web.upload_receipts');

Route::post('/scorecard/upload-personal', function (Request $request, PersonalAccountTransactionsImportService $importService, AuditInspectionService $inspectionService) {
    $request->validate(['file' => 'required|file', 'branch_id' => 'required|integer']);
    $branchId = (int)$request->input('branch_id');
    $file = $request->file('file');

    $importService->import($file->getRealPath(), $branchId, 'Q4', '2025-2026');

    $inspection = $inspectionService->getOrCreateInspection($branchId, '2026-05-28', 'Q4', '2025-2026');
    $inspectionService->evaluateAutoCheckpoints($inspection);
    $inspection->recalculateTotals();

    return back()->with('success', 'Personal Account Transactions uploaded and A.2 auto-scored successfully!');
})->name('web.upload_personal');

Route::post('/scorecard/{inspectionId}/save-scores', function (Request $request, AuditInspectionService $inspectionService, int $inspectionId) {
    $inspection = AuditInspection::findOrFail($inspectionId);
    $scores = $request->input('scores', []);

    $inspectionService->bulkUpdateScores($inspection, $scores);

    return back()->with('success', 'Manual scores (B to G) saved and total grade recalculated successfully!');
})->name('web.save_scores');

Route::fallback(function () {
    return redirect()->route('web.scorecard');
});
