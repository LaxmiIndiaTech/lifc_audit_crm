<?php

namespace App\Services;

use App\Models\AuditInspection;
use App\Models\Branch;
use App\Models\CashChequeReceipt;
use App\Models\Employee;
use App\Models\ScoringRule;
use App\Models\State;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CashChequeReceiptsImportService
{
    /**
     * Normalized key mapping: all keys in lowercase with spaces instead of underscores/hyphens.
     */
    protected array $headerMap = [
        'loan no' => 'loan_no',
        'loan' => 'loan_no',
        'customer name' => 'customer_name',
        'customer mobile no' => 'customer_mobile_no',
        'mobile no' => 'customer_mobile_no',
        'created date' => 'created_date',
        'payment date' => 'payment_date',
        'payment month' => 'payment_month',
        'emi amount paid' => 'emi_amount_paid',
        'late payment charges paid' => 'late_payment_charges_paid',
        'bounce charges paid' => 'bounce_charges_paid',
        'penalty charges paid' => 'penalty_charges_paid',
        'other charges paid' => 'other_charges_paid',
        'total amount paid' => 'total_amount_paid',
        'charges' => 'charges',
        'payment mode' => 'payment_mode',
        'payment status' => 'payment_status',
        'payment channel' => 'payment_channel',
        'receipt purpose' => 'receipt_purpose',
        'lms receipt id' => 'lms_receipt_id',
        'deposit amount' => 'deposit_amount',
        'deposit status' => 'deposit_status',
        'deposit date' => 'deposit_date',
        'deposited date' => 'deposit_date',
        'branch' => 'branch_name',
        'branch name' => 'branch_name',
        'collected by name' => 'collected_by_name',
        'collected by id' => 'collected_by_id',
        'department' => 'department',
        'state' => 'state_name',
        'state name' => 'state_name',
        'days' => 'gap_in_days',
        'gap in days' => 'gap_in_days',
        'tat' => 'tat_status',
        'tat status' => 'tat_status',
    ];

    public function import(string $filePath, ?int $defaultBranchId = null): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, true);

        if (empty($rows)) {
            throw new \InvalidArgumentException('Spreadsheet is empty.');
        }

        // Find header row (search first 10 rows for "loan" or "receipt")
        $headerRowIdx = null;
        $headers = [];

        foreach ($rows as $idx => $row) {
            $cleaned = [];
            foreach ($row as $colKey => $v) {
                $cleaned[$colKey] = $this->normalizeHeader((string)$v);
            }
            if (in_array('loan no', $cleaned) || in_array('lms receipt id', $cleaned) || in_array('loan', $cleaned)) {
                $headerRowIdx = $idx;
                $headers = $cleaned;
                break;
            }
        }

        if ($headerRowIdx === null) {
            $headerRowIdx = 1;
            foreach ($rows[1] as $colKey => $v) {
                $headers[$colKey] = $this->normalizeHeader((string)$v);
            }
        }

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        // Preload branches, states, employees, and active scoring rules
        $branches = Branch::all()->keyBy(fn($b) => strtolower(trim($b->branch_name)));
        $states = State::all()->keyBy(fn($s) => strtolower(trim($s->state_name)));
        $employees = Employee::all()->keyBy('employee_code');
        $scoringRules = ScoringRule::activeRules()->orderBy('min_days')->get();
        if ($scoringRules->isEmpty()) {
            $scoringRules = ScoringRule::active()->orderBy('min_days')->get();
        }
        $affectedBranchIds = [];

        foreach ($rows as $idx => $row) {
            if ($idx <= $headerRowIdx) {
                continue;
            }

            // Map columns
            $data = [];
            foreach ($row as $colKey => $val) {
                $headerName = $headers[$colKey] ?? null;
                if ($headerName && isset($this->headerMap[$headerName])) {
                    $attr = $this->headerMap[$headerName];
                    $data[$attr] = is_string($val) ? trim($val) : $val;
                }
            }

            // Skip empty rows
            if (empty($data['loan_no']) && empty($data['lms_receipt_id'])) {
                continue;
            }

            try {
                // Link branch (auto-create if missing)
                $branchId = $defaultBranchId;
                if (!empty($data['branch_name'])) {
                    $bKey = strtolower(trim($data['branch_name']));
                    if (isset($branches[$bKey])) {
                        $branchId = $branches[$bKey]->id;
                    } else {
                        $bNameClean = ucwords(strtolower(trim($data['branch_name'])));
                        $bCodeClean = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $data['branch_name']), 0, 4)) . '01';
                        $newBranch = Branch::firstOrCreate(
                            ['branch_name' => $bNameClean],
                            ['branch_code' => $bCodeClean, 'is_active' => true]
                        );
                        $branches[$bKey] = $newBranch;
                        $branchId = $newBranch->id;
                    }
                }
                $data['branch_id'] = $branchId;
                if ($branchId && !in_array($branchId, $affectedBranchIds)) {
                    $affectedBranchIds[] = $branchId;
                }

                // Link state
                if (!empty($data['state_name'])) {
                    $sKey = strtolower(trim($data['state_name']));
                    if (isset($states[$sKey])) {
                        $data['state_id'] = $states[$sKey]->id;
                    }
                }

                // Link employee
                if (!empty($data['collected_by_id'])) {
                    $empCode = (string)$data['collected_by_id'];
                    if (isset($employees[$empCode])) {
                        $data['employee_id'] = $employees[$empCode]->id;
                    }
                }

                // Parse dates accurately
                if (!empty($data['created_date'])) {
                    $data['created_date'] = $this->parseDate($data['created_date']);
                }
                if (!empty($data['payment_date'])) {
                    $data['payment_date'] = $this->parseDateTime($data['payment_date']);
                }
                if (!empty($data['deposit_date'])) {
                    $data['deposit_date'] = $this->parseDate($data['deposit_date']);
                }

                // If payment_date is missing, use created_date
                if (empty($data['payment_date']) && !empty($data['created_date'])) {
                    $data['payment_date'] = Carbon::parse($data['created_date'])->toDateTimeString();
                }

                // Calculate working banking days gap (excluding Sundays, 2nd/4th Saturdays & holidays)
                $baseDate = !empty($data['created_date']) ? $data['created_date'] : $data['payment_date'];
                if (!empty($data['deposit_date']) && !empty($baseDate)) {
                    $gapDays = \App\Models\Holiday::calculateWorkingDaysGap($baseDate, $data['deposit_date'], $data['state_id'] ?? null);

                    $data['gap_in_days'] = $gapDays;
                    $data['tat_status'] = 'T+' . $gapDays;
                    $data['is_over_tat'] = ($gapDays > 1);

                    // Match with Scoring Master rule based on gap days
                    $matchedRule = $scoringRules->first(function ($r) use ($gapDays) {
                        return $gapDays >= $r->min_days && $gapDays <= $r->max_days;
                    });
                    if ($matchedRule) {
                        $data['scoring_rules_id'] = $matchedRule->id;
                    }
                }

                // Find or initialize record
                $receipt = null;
                if (!empty($data['lms_receipt_id']) && $data['lms_receipt_id'] !== '-') {
                    $receipt = CashChequeReceipt::where('lms_receipt_id', $data['lms_receipt_id'])->first();
                }

                if (!$receipt && !empty($data['loan_no'])) {
                    $q = CashChequeReceipt::where('loan_no', $data['loan_no']);
                    if (!empty($data['created_date'])) {
                        $q->where('created_date', $data['created_date']);
                    }
                    if (!empty($data['total_amount_paid'])) {
                        $q->where('total_amount_paid', $data['total_amount_paid']);
                    }
                    $receipt = $q->first();
                }

                if ($receipt) {
                    $receipt->update($data);
                    $updated++;
                } else {
                    CashChequeReceipt::create($data);
                    $created++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Row {$idx}: " . $e->getMessage();
            }
        }

        // Auto-recalculate checkpoints for affected branches
        try {
            $inspService = app(AuditInspectionService::class);
            foreach ($affectedBranchIds as $bId) {
                $inspection = AuditInspection::where('branch_id', $bId)->latest('inspection_date')->first();
                if (!$inspection) {
                    $inspection = $inspService->getOrCreateInspection($bId, Carbon::today()->toDateString(), 'Q1', '2026-2027');
                }
                if ($inspection) {
                    $inspService->evaluateAutoCheckpoints($inspection);
                    $inspection->recalculateTotals();
                }
            }
        } catch (\Throwable $ex) {
            // Log but don't break import response
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => array_slice($errors, 0, 50),
        ];
    }

    protected function normalizeHeader(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = str_replace(['_', '-'], ' ', $normalized);
        return trim(preg_replace('/\s+/', ' ', $normalized));
    }

    protected function parseDate($value): ?string
    {
        if (empty($value) || $value === '-') return null;

        // If Excel numeric serial date (e.g. 46115)
        if (is_numeric($value) && $value > 30000 && $value < 60000) {
            return Carbon::createFromTimestampUTC(($value - 25569) * 86400)->format('Y-m-d');
        }

        $trimmed = trim((string)$value);
        // Explicitly handle DD-MM-YYYY or DD/MM/YYYY
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $trimmed, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function parseDateTime($value): ?string
    {
        if (empty($value) || $value === '-') return null;

        if (is_numeric($value) && $value > 30000 && $value < 60000) {
            return Carbon::createFromTimestampUTC(($value - 25569) * 86400)->toDateTimeString();
        }

        $trimmed = trim((string)$value);
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})(.*)$/', $trimmed, $m)) {
            $timePart = trim($m[4]);
            $datePart = sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
            return $timePart ? Carbon::parse("{$datePart} {$timePart}")->toDateTimeString() : "{$datePart} 00:00:00";
        }

        try {
            return Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
