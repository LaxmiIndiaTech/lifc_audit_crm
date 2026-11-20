<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Branch;
use App\Models\BranchTargetAchievement;
use App\Models\LoanDisbursalRecord;
use App\Models\ScoringRule;
use App\Services\TargetAchievementImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TargetRecordsController extends ApiController
{
    /**
     * List and filter Branch Target Achievements.
     */
    public function index(Request $request): JsonResponse
    {
        $query = BranchTargetAchievement::with('branch');

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($w) use ($q) {
                $w->where('branch_name', 'like', "%{$q}%")
                  ->orWhere('state', 'like', "%{$q}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int)$request->input('branch_id'));
        }

        if ($request->filled('financial_quarter')) {
            $query->where('financial_quarter', $request->input('financial_quarter'));
        }

        if ($request->filled('financial_year')) {
            $query->where('financial_year', $request->input('financial_year'));
        }

        $perPage = min(100, max(10, (int)$request->input('per_page', 25)));
        $records = $query->orderBy('branch_name', 'asc')->paginate($perPage);

        return $this->sendSuccess('Branch target records fetched successfully', [
            'target_records' => $records->items(),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'total_count' => $records->total(),
                'per_page' => $records->perPage(),
            ],
        ]);
    }

    /**
     * Get branch Target vs Achievement summary and score calculation.
     */
    public function branchSummary(int $branchId, Request $request): JsonResponse
    {
        $branch = Branch::findOrFail($branchId);
        $bta = BranchTargetAchievement::where('branch_id', $branchId)->first();

        // Also check if loan disbursal records exist
        $loansCount = LoanDisbursalRecord::where('branch_id', $branchId)->count();
        $loansSum = (float)LoanDisbursalRecord::where('branch_id', $branchId)->sum('disbursal_amount');

        if (!$bta) {
            return $this->sendSuccess('No Target vs Achievement data found for branch', [
                'branch_id' => $branch->id,
                'branch_name' => $branch->branch_name,
                'target_amount' => 0.00,
                'disbursed_amount' => $loansSum,
                'portfolio_disbursed_amount' => 0.00,
                'incentive_disbursed_amount' => 0.00,
                'achievement_percentage' => 0.00,
                'total_loans' => $loansCount,
                'tier' => 'No Data',
                'marks_obtained' => 0.0000,
                'carry_marks' => 15.0000,
                'percentage' => 0.00,
            ]);
        }

        // If loan records exist, ensure disbursed_amount and total_loans are up to date
        $targetAmt = (float)$bta->target_amount;
        $disbAmt = ($loansCount > 0) ? $loansSum : (float)$bta->disbursed_amount;
        $totalLoans = ($loansCount > 0) ? $loansCount : (int)$bta->total_loans;

        $achPct = 0.00;
        if ($targetAmt > 0) {
            $achPct = round(($disbAmt / $targetAmt) * 100.0, 2);
        }

        // Dynamic Scoring Rules from Scoring Master (Section I.1)
        $rules = ScoringRule::activeRules()
            ->where('rule_type', 'target_achievement')
            ->orderBy('min_days', 'asc')
            ->get();

        $matchedRule = null;
        if ($rules->isNotEmpty()) {
            foreach ($rules as $rule) {
                if ($achPct >= $rule->min_days && $achPct <= $rule->max_days) {
                    $matchedRule = $rule;
                    break;
                }
            }
        }

        $carryMarks = 15.0000;
        if ($matchedRule) {
            $scorePct = (float)$matchedRule->score;
            $obtained = round(($scorePct / 100.0) * $carryMarks, 2);
            $tier = $matchedRule->description ?: "{$matchedRule->min_days}%-{$matchedRule->max_days}% ({$scorePct}% Marks)";
        } else {
            if ($achPct > 90.0) {
                $obtained = 15.0000;
                $scorePct = 100.00;
                $tier = '> 90% Achievement (100% Marks)';
            } elseif ($achPct >= 60.0) {
                $obtained = 11.2500;
                $scorePct = 75.00;
                $tier = '60%-90% Achievement (75% Marks)';
            } elseif ($achPct >= 40.0) {
                $obtained = 7.5000;
                $scorePct = 50.00;
                $tier = '40%-60% Achievement (50% Marks)';
            } else {
                $obtained = 3.0000;
                $scorePct = 20.00;
                $tier = '< 40% Achievement (20% Marks)';
            }
        }

        return $this->sendSuccess('Branch target summary calculated successfully', [
            'branch_id' => $branch->id,
            'branch_name' => $branch->branch_name,
            'target_amount' => $targetAmt,
            'disbursed_amount' => $disbAmt,
            'portfolio_disbursed_amount' => (float)$bta->portfolio_disbursed_amount,
            'incentive_disbursed_amount' => (float)$bta->incentive_disbursed_amount,
            'achievement_percentage' => $achPct,
            'total_loans' => $totalLoans,
            'tier' => $tier,
            'marks_obtained' => $obtained,
            'carry_marks' => $carryMarks,
            'percentage' => $scorePct,
        ]);
    }

    /**
     * List individual loan disbursal records for a branch (Book1 data).
     */
    public function loans(Request $request): JsonResponse
    {
        $query = LoanDisbursalRecord::query();

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int)$request->input('branch_id'));
        }

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($w) use ($q) {
                $w->where('loan_account_number', 'like', "%{$q}%")
                  ->orWhere('customer_name', 'like', "%{$q}%")
                  ->orWhere('product_name', 'like', "%{$q}%");
            });
        }

        $perPage = min(100, max(10, (int)$request->input('per_page', 25)));
        $records = $query->latest('disbursal_date')->paginate($perPage);

        return $this->sendSuccess('Loan disbursal records fetched successfully', [
            'loans' => $records->items(),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'total_count' => $records->total(),
                'per_page' => $records->perPage(),
            ],
        ]);
    }

    /**
     * Upload and import Target Sheet or Book1 Disbursal Sheet (CSV or Excel).
     */
    public function import(Request $request, TargetAchievementImportService $service): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'sheet_type' => 'nullable|string|in:branch_target,book1_disbursal',
        ]);

        $file = $request->file('file');
        $branchId = $request->filled('branch_id') ? (int)$request->input('branch_id') : null;
        $sheetType = $request->input('sheet_type');

        try {
            $result = $service->import($file->getRealPath(), $branchId, $sheetType);

            return $this->sendSuccess('Target / Disbursal sheet uploaded and scored successfully', [
                'type' => $result['type'],
                'created' => $result['created'],
                'updated' => $result['updated'],
                'failed' => $result['failed'],
                'total_processed' => $result['total_processed'],
                'branches_affected_count' => $result['branches_affected_count'],
                'errors' => $result['errors'],
            ]);
        } catch (\Throwable $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
