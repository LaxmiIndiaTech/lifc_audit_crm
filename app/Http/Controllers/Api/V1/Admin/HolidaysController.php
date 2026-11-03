<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditInspection;
use App\Models\CashChequeReceipt;
use App\Models\Holiday;
use App\Models\ScoringRule;
use App\Services\AuditInspectionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HolidaysController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month');
        $search = $request->input('search');
        $perPage = (int)$request->input('per_page', 50);

        $query = Holiday::query()->with('state');

        if ($year) {
            $query->whereYear('holiday_date', $year);
        }

        if ($month) {
            $query->whereMonth('holiday_date', $month);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('holiday_name', 'like', "%{$search}%")
                  ->orWhere('holiday_date', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $holidays = $query->orderBy('holiday_date', 'asc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => [
                'holidays' => $holidays->items(),
                'pagination' => [
                    'current_page' => $holidays->currentPage(),
                    'total_pages' => $holidays->lastPage(),
                    'total_count' => $holidays->total(),
                    'per_page' => $holidays->perPage(),
                ],
                'summary' => [
                    'total_year' => Holiday::whereYear('holiday_date', $year)->count(),
                    'upcoming' => Holiday::where('holiday_date', '>=', Carbon::today()->toDateString())->count(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'holiday_date' => 'required|date',
            'holiday_name' => 'required|string|max:255',
            'state_id' => 'nullable|integer|exists:states,id',
            'type' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        $date = Carbon::parse($validated['holiday_date'])->format('Y-m-d');

        $holiday = Holiday::updateOrCreate(
            [
                'holiday_date' => $date,
                'state_id' => $validated['state_id'] ?? null,
            ],
            [
                'holiday_name' => $validated['holiday_name'],
                'type' => $validated['type'] ?? 'bank_holiday',
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Holiday saved successfully',
            'data' => $holiday,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Holiday deleted successfully',
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls,txt|max:10240',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return response()->json([
                'status' => 'error',
                'message' => 'File is empty.',
            ], 422);
        }

        // Header detection
        $headerRowIdx = 1;
        $headers = [];
        foreach ($rows as $idx => $r) {
            $cleaned = array_map(fn($v) => strtolower(trim(str_replace(['_', '-'], ' ', (string)$v))), $r);
            if (in_array('date', $cleaned) || in_array('holiday date', $cleaned) || in_array('holiday name', $cleaned)) {
                $headerRowIdx = $idx;
                $headers = $cleaned;
                break;
            }
        }

        if (empty($headers)) {
            $headers = array_map(fn($v) => strtolower(trim(str_replace(['_', '-'], ' ', (string)$rows[1]))), $rows[1]);
        }

        $dateCol = null;
        $nameCol = null;
        $typeCol = null;

        foreach ($headers as $colKey => $h) {
            if ($h === 'date' || $h === 'holiday date' || $h === 'holiday_date') {
                $dateCol = $colKey;
            } elseif ($h === 'name' || $h === 'holiday name' || $h === 'holiday' || $h === 'description') {
                $nameCol = $colKey;
            } elseif ($h === 'type' || $h === 'holiday type') {
                $typeCol = $colKey;
            }
        }

        // Default to column A for date and column B for name if headers weren't named
        if (!$dateCol) $dateCol = 'A';
        if (!$nameCol) $nameCol = 'B';

        $imported = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            if ($idx <= $headerRowIdx) continue;

            $rawDate = trim((string)($row[$dateCol] ?? ''));
            $name = trim((string)($row[$nameCol] ?? ''));
            $type = $typeCol ? trim((string)($row[$typeCol] ?? 'bank_holiday')) : 'bank_holiday';

            if (empty($rawDate) || empty($name)) continue;

            try {
                $parsedDate = $this->parseDate($rawDate);
                if (!$parsedDate) {
                    $errors[] = "Row {$idx}: Invalid date format '{$rawDate}'";
                    continue;
                }

                Holiday::updateOrCreate(
                    ['holiday_date' => $parsedDate, 'state_id' => null],
                    [
                        'holiday_name' => $name,
                        'type' => $type ?: 'bank_holiday',
                        'is_active' => true,
                    ]
                );
                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$idx}: " . $e->getMessage();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Successfully imported {$imported} holidays.",
            'data' => [
                'imported_count' => $imported,
                'errors' => array_slice($errors, 0, 20),
            ],
        ]);
    }

    /**
     * Re-calculate receipts with working banking days.
     */
    public function recalculateReceipts(): JsonResponse
    {
        $receipts = CashChequeReceipt::whereNotNull('deposit_date')->whereNotNull('created_date')->get();
        $scoringRules = ScoringRule::activeRules()->orderBy('min_days')->get();
        if ($scoringRules->isEmpty()) {
            $scoringRules = ScoringRule::active()->orderBy('min_days')->get();
        }

        $updatedCount = 0;
        $affectedBranchIds = [];

        foreach ($receipts as $receipt) {
            $gapDays = Holiday::calculateWorkingDaysGap($receipt->created_date, $receipt->deposit_date, $receipt->state_id);

            $matchedRule = $scoringRules->first(function ($r) use ($gapDays) {
                return $gapDays >= $r->min_days && $gapDays <= $r->max_days;
            });

            $receipt->update([
                'gap_in_days' => $gapDays,
                'tat_status' => 'T+' . $gapDays,
                'is_over_tat' => ($gapDays > 1),
                'scoring_rules_id' => $matchedRule ? $matchedRule->id : null,
            ]);

            $updatedCount++;
            if ($receipt->branch_id && !in_array($receipt->branch_id, $affectedBranchIds)) {
                $affectedBranchIds[] = $receipt->branch_id;
            }
        }

        // Re-evaluate inspections for affected branches
        $inspService = app(AuditInspectionService::class);
        foreach ($affectedBranchIds as $bId) {
            $inspections = AuditInspection::where('branch_id', $bId)->get();
            foreach ($inspections as $insp) {
                $inspService->evaluateAutoCheckpoints($insp);
                $insp->recalculateTotals();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Recalculated {$updatedCount} receipts using working banking days.",
            'data' => [
                'updated_receipts' => $updatedCount,
                'affected_branches' => count($affectedBranchIds),
            ],
        ]);
    }

    protected function parseDate($value): ?string
    {
        if (empty($value) || $value === '-') return null;

        if (is_numeric($value) && $value > 30000 && $value < 60000) {
            return Carbon::createFromTimestampUTC(($value - 25569) * 86400)->format('Y-m-d');
        }

        $trimmed = trim((string)$value);
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $trimmed, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
