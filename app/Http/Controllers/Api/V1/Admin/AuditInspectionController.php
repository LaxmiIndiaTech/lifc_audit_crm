<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AuditInspection;
use App\Models\Branch;
use App\Services\AuditInspectionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditInspectionController extends ApiController
{
    protected AuditInspectionService $inspectionService;

    public function __construct(AuditInspectionService $inspectionService)
    {
        $this->inspectionService = $inspectionService;
    }

    /**
     * Get or create branch inspection report card (Sections A through G).
     */
    public function show(Request $request, int $branchId): JsonResponse
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $quarter = $request->input('quarter', 'Q4');
        $finYear = $request->input('financial_year', '2025-2026');

        $inspection = $this->inspectionService->getOrCreateInspection($branchId, $date, $quarter, $finYear);
        $summary = $this->inspectionService->getSectionWiseSummary($inspection);

        $branch = $inspection->branch;

        $response = [
            'inspection_id' => $inspection->id,
            'status' => $inspection->status,
            'branch_info' => [
                'branch_id' => $branch->id,
                'branch_name' => $branch->branch_name,
                'branch_code' => $branch->branch_code,
                'company_name' => $inspection->company_name,
                'branch_address' => $branch->address,
                'in_charge_code' => trim(($inspection->branch_in_charge_name ?: '') . ' & ' . ($inspection->branch_in_charge_code ?: '')),
                'cm_asm_code' => trim(($inspection->cluster_name ?: '') . ' & ' . ($inspection->cluster_code ?: '')),
                'inspection_date' => $inspection->inspection_date->format('Y-m-d'),
                'inspection_officials' => $inspection->inspection_officials,
                'quarter' => $inspection->financial_quarter,
                'financial_year' => $inspection->financial_year,
            ],
            'section_wise_summary' => $summary['sections'],
            'process_subtotal' => $summary['sub_total_i'],
            'final_grading' => [
                'percentage' => (float)$inspection->process_score_pct,
                'grade' => $inspection->final_grade,
            ],
            'all_checkpoints' => $inspection->scores()->orderBy('checkpoint_code')->get(),
        ];

        return $this->sendSuccess('Branch inspection report card fetched successfully', $response);
    }

    /**
     * Update manual checkpoint marks (Sections B through G).
     */
    public function updateCheckpoints(Request $request, int $inspectionId): JsonResponse
    {
        $inspection = AuditInspection::findOrFail($inspectionId);

        $request->validate([
            'scores' => 'required|array',
            'scores.*.checkpoint_code' => 'required|string',
            'scores.*.marks_obtained' => 'required|numeric|min:0',
            'scores.*.visitor_comment' => 'nullable|string',
            'scores.*.immediate_action' => 'nullable|string',
            'scores.*.department' => 'nullable|string',
            'scores.*.branch_comment' => 'nullable|string',
        ]);

        $this->inspectionService->bulkUpdateScores($inspection, $request->input('scores'));

        $summary = $this->inspectionService->getSectionWiseSummary($inspection->fresh());

        return $this->sendSuccess('Checkpoints updated and scores recalculated successfully', [
            'inspection_id' => $inspection->id,
            'section_wise_summary' => $summary['sections'],
            'process_subtotal' => $summary['sub_total_i'],
            'final_grade' => $inspection->fresh()->final_grade,
            'all_checkpoints' => $inspection->scores()->orderBy('checkpoint_code')->get(),
        ]);
    }

    /**
     * Trigger re-evaluation of auto checkpoints (A.1 and A.2).
     */
    public function reEvaluate(int $inspectionId): JsonResponse
    {
        $inspection = AuditInspection::findOrFail($inspectionId);

        $this->inspectionService->evaluateAutoCheckpoints($inspection);
        $inspection->recalculateTotals();

        $summary = $this->inspectionService->getSectionWiseSummary($inspection->fresh());

        return $this->sendSuccess('Auto checkpoints A.1 and A.2 re-evaluated successfully', [
            'inspection_id' => $inspection->id,
            'section_wise_summary' => $summary['sections'],
            'process_subtotal' => $summary['sub_total_i'],
            'final_grade' => $inspection->fresh()->final_grade,
            'all_checkpoints' => $inspection->scores()->orderBy('checkpoint_code')->get(),
        ]);
    }
}
