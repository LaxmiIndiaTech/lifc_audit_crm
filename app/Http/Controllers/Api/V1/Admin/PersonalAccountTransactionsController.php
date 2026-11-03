<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\PersonalAccountTransaction;
use App\Services\PersonalAccountTransactionsImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonalAccountTransactionsController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = PersonalAccountTransaction::with(['branch', 'employee']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }
        if ($request->filled('quarter')) {
            $query->where('quarter', $request->input('quarter'));
        }
        if ($request->filled('mobile_number')) {
            $query->where('mobile_number', 'like', '%' . $request->input('mobile_number') . '%');
        }

        $perPage = (int)$request->query('per_page', 25);
        $transactions = $query->latest('transaction_date')->paginate($perPage);

        return $this->sendSuccess('Personal account transactions fetched successfully', [
            'transactions' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'total_pages' => $transactions->lastPage(),
                'total_count' => $transactions->total(),
                'per_page' => $transactions->perPage(),
            ],
        ]);
    }

    public function import(Request $request, PersonalAccountTransactionsImportService $service): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
            'branch_id' => 'nullable|integer|exists:branches,id',
            'quarter' => 'nullable|string|max:10',
            'financial_year' => 'nullable|string|max:15',
        ]);

        $file = $request->file('file');
        $branchId = $request->filled('branch_id') ? (int)$request->input('branch_id') : null;
        $quarter = $request->input('quarter', 'Q4');
        $finYear = $request->input('financial_year', '2025-2026');

        try {
            $result = $service->import($file->getRealPath(), $branchId, $quarter, $finYear);

            return $this->sendSuccess('Personal account transactions imported successfully', [
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
