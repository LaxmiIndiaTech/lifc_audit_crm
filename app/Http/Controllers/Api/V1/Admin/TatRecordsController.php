<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AuditInspection;
use App\Models\Branch;
use App\Models\LoanTatRecord;
use App\Services\AuditInspectionService;
use App\Services\TatRecordsImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TatRecordsController extends ApiController
{
    /**
     * List and filter TAT records.
     */
    public function index(Request $request): JsonResponse
    {
        $query = LoanTatRecord::with('branch');

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($w) use ($q) {
                $w->where('loan_account_number', 'like', "%{$q}%")
                  ->orWhere('customer_name', 'like', "%{$q}%")
                  ->orWhere('mobile_no', 'like', "%{$q}%")
                  ->orWhere('branch_name', 'like', "%{$q}%")
                  ->orWhere('sourcing_rm_name', 'like', "%{$q}%")
                  ->orWhere('sourcing_rm_id', 'like', "%{$q}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int)$request->input('branch_id'));
        }

        if ($request->filled('product_name')) {
            $query->where('product_name', $request->input('product_name'));
        }

        if ($request->filled('financial_quarter')) {
            $query->where('financial_quarter', $request->input('financial_quarter'));
        }

        if ($request->filled('financial_year')) {
            $query->where('financial_year', $request->input('financial_year'));
        }

        $perPage = min(100, max(10, (int)$request->input('per_page', 25)));
        $records = $query->latest('disbursal_date')->paginate($perPage);

        return $this->sendSuccess('TAT records fetched successfully', [
            'tat_records' => $records->items(),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'total_count' => $records->total(),
                'per_page' => $records->perPage(),
            ],
        ]);
    }

    /**
     * Get branch TAT summary and score calculation.
     */
    public function branchSummary(int $branchId, Request $request): JsonResponse
    {
        $branch = Branch::findOrFail($branchId);
        $tatLoans = LoanTatRecord::where('branch_id', $branchId)->get();
        $totalLoans = $tatLoans->count();

        if ($totalLoans === 0) {
            return $this->sendSuccess('No TAT data found for branch', [
                'branch_id' => $branch->id,
                'branch_name' => $branch->branch_name,
                'total_loans' => 0,
                'average_days' => 0.0,
                'min_days' => 0.0,
                'max_days' => 0.0,
                'tier' => 'No Data',
                'marks_obtained' => 0.0,
                'carry_marks' => 5.0,
                'percentage' => 0.0,
            ]);
        }

        $avgDays = round((float)$tatLoans->avg('days'), 2);
        $minDays = (float)$tatLoans->min('days');
        $maxDays = (float)$tatLoans->max('days');

        // Dynamic rules for TAT
        $tatRules = \App\Models\ScoringRule::activeRules()
            ->where('rule_type', 'tat_disbursement')
            ->orderBy('min_days', 'asc')
            ->get();

        $matchedRule = null;
        if ($tatRules->isNotEmpty()) {
            foreach ($tatRules as $rule) {
                if ($avgDays >= $rule->min_days && $avgDays <= $rule->max_days) {
                    $matchedRule = $rule;
                    break;
                }
            }
        }

        if ($matchedRule) {
            $pct = (float)$matchedRule->score;
            $obtained = round(($pct / 100.0) * 5.0, 2);
            $tier = $matchedRule->description ?: "{$matchedRule->min_days}-{$matchedRule->max_days} Days ({$pct}% Marks)";
        } else {
            if ($avgDays <= 10.00) {
                $obtained = 5.0000;
                $pct = 100.00;
                $tier = '0-10 Days (100% Marks)';
            } elseif ($avgDays <= 20.00) {
                $obtained = 3.7500;
                $pct = 75.00;
                $tier = '11-20 Days (75% Marks)';
            } elseif ($avgDays <= 30.00) {
                $obtained = 2.5000;
                $pct = 50.00;
                $tier = '21-30 Days (50% Marks)';
            } else {
                $obtained = 1.0000;
                $pct = 20.00;
                $tier = '31 and above Days (20% Marks)';
            }
        }

        return $this->sendSuccess('Branch TAT summary calculated successfully', [
            'branch_id' => $branch->id,
            'branch_name' => $branch->branch_name,
            'total_loans' => $totalLoans,
            'average_days' => $avgDays,
            'min_days' => $minDays,
            'max_days' => $maxDays,
            'tier' => $tier,
            'marks_obtained' => $obtained,
            'carry_marks' => 5.0,
            'percentage' => $pct,
        ]);
    }

    /**
     * Upload and import TAT Sheet (CSV or Excel).
     */
    public function import(Request $request, TatRecordsImportService $service): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $file = $request->file('file');
        $branchId = $request->filled('branch_id') ? (int)$request->input('branch_id') : null;

        try {
            $result = $service->import($file->getRealPath(), $branchId);

            return $this->sendSuccess('TAT sheet uploaded and scored successfully', [
                'created' => $result['created'],
                'updated' => $result['updated'],
                'failed' => $result['failed'],
                'total_processed' => $result['total_processed'],
                'branches_affected_count' => $result['branches_affected_count'],
                'evaluated_branches' => $result['evaluated_branches'],
                'errors' => $result['errors'],
            ]);
        } catch (\Throwable $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
