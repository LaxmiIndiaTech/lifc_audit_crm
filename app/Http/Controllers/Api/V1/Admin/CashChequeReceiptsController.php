<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\CashChequeReceipt;
use App\Services\CashChequeReceiptsImportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashChequeReceiptsController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = CashChequeReceipt::with(['branch', 'employee', 'scoringRule']);

        // Search (q)
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($w) use ($q) {
                $w->where('loan_no', 'like', "%{$q}%")
                  ->orWhere('customer_name', 'like', "%{$q}%")
                  ->orWhere('customer_mobile_no', 'like', "%{$q}%")
                  ->orWhere('branch_name', 'like', "%{$q}%")
                  ->orWhere('collected_by_name', 'like', "%{$q}%")
                  ->orWhere('lms_receipt_id', 'like', "%{$q}%");
            });
        }

        // Hierarchy
        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int)$request->input('branch_id'));
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', (int)$request->input('employee_id'));
        }
        if ($request->filled('scoring_rules_id')) {
            $query->where('scoring_rules_id', (int)$request->input('scoring_rules_id'));
        }
        if ($request->filled('state_id')) {
            $query->where('state_id', (int)$request->input('state_id'));
        }
        if ($request->filled('loan_no')) {
            $query->where('loan_no', 'like', '%' . $request->input('loan_no') . '%');
        }

        // Status filters
        if ($request->filled('payment_mode')) {
            $modes = is_array($request->input('payment_mode')) ? $request->input('payment_mode') : explode(',', $request->input('payment_mode'));
            $query->whereIn('payment_mode', array_map('trim', $modes));
        }
        if ($request->filled('payment_status')) {
            $statuses = is_array($request->input('payment_status')) ? $request->input('payment_status') : explode(',', $request->input('payment_status'));
            $query->whereIn('payment_status', array_map('trim', $statuses));
        }
        if ($request->filled('payment_channel')) {
            $channels = is_array($request->input('payment_channel')) ? $request->input('payment_channel') : explode(',', $request->input('payment_channel'));
            $query->whereIn('payment_channel', array_map('trim', $channels));
        }
        if ($request->filled('deposit_status')) {
            $dstatuses = is_array($request->input('deposit_status')) ? $request->input('deposit_status') : explode(',', $request->input('deposit_status'));
            $query->whereIn('deposit_status', array_map('trim', $dstatuses));
        }
        if ($request->boolean('is_over_tat')) {
            $query->where('is_over_tat', true);
        }

        // Month & Year Filter
        $month = $request->filled('month') ? (int)$request->input('month') : null;
        $year = $request->filled('year') ? (int)$request->input('year') : null;
        $allowedFields = ['payment_date', 'deposit_date', 'created_date', 'created_at'];
        $dateField = in_array($request->input('month_filter_field'), $allowedFields) ? $request->input('month_filter_field') : 'payment_date';

        if ($month && $year && $month >= 1 && $month <= 12) {
            $startDate = Carbon::create($year, $month, 1)->startOfDay();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
            $query->whereBetween($dateField, [$startDate, $endDate]);
        } elseif ($year) {
            $startDate = Carbon::create($year, 1, 1)->startOfDay();
            $endDate = Carbon::create($year, 12, 31)->endOfDay();
            $query->whereBetween($dateField, [$startDate, $endDate]);
        }

        // Date ranges
        if ($request->filled('payment_date_from')) {
            $query->where('payment_date', '>=', Carbon::parse($request->input('payment_date_from'))->startOfDay());
        }
        if ($request->filled('payment_date_to')) {
            $query->where('payment_date', '<=', Carbon::parse($request->input('payment_date_to'))->endOfDay());
        }
        if ($request->filled('deposit_date_from')) {
            $query->where('deposit_date', '>=', Carbon::parse($request->input('deposit_date_from'))->startOfDay());
        }
        if ($request->filled('deposit_date_to')) {
            $query->where('deposit_date', '<=', Carbon::parse($request->input('deposit_date_to'))->endOfDay());
        }

        // Amounts
        if ($request->filled('amount_min')) {
            $query->where('total_amount_paid', '>=', (float)$request->input('amount_min'));
        }
        if ($request->filled('amount_max')) {
            $query->where('total_amount_paid', '<=', (float)$request->input('amount_max'));
        }

        // Sorting
        $allowedSort = ['created_at', 'payment_date', 'deposit_date', 'created_date', 'total_amount_paid', 'gap_in_days', 'loan_no'];
        $sortBy = in_array($request->input('sort_by'), $allowedSort) ? $request->input('sort_by') : 'payment_date';
        $sortDir = strtolower($request->input('sort_dir')) === 'asc' ? 'asc' : 'desc';

        $perPage = (int)$request->query('per_page', 25);
        $receipts = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        return $this->sendSuccess('Receipts fetched successfully', [
            'receipts' => $receipts->items(),
            'pagination' => [
                'current_page' => $receipts->currentPage(),
                'total_pages' => $receipts->lastPage(),
                'total_count' => $receipts->total(),
                'per_page' => $receipts->perPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $receipt = CashChequeReceipt::with(['branch', 'employee', 'scoringRule'])->find($id);
        if (!$receipt) {
            return $this->sendError('Receipt not found', 404);
        }

        return $this->sendSuccess('Receipt fetched successfully', $receipt);
    }

    public function import(Request $request, CashChequeReceiptsImportService $service): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $file = $request->file('file');
        $branchId = $request->filled('branch_id') ? (int)$request->input('branch_id') : null;

        try {
            $result = $service->import($file->getRealPath(), $branchId);

            return $this->sendSuccess('Receipts imported and auto-scored successfully', [
                'created' => $result['created'],
                'updated' => $result['updated'],
                'failed' => $result['failed'],
                'errors' => $result['errors'],
            ]);
        } catch (\Throwable $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
