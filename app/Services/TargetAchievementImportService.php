<?php

namespace App\Services;

use App\Models\AuditInspection;
use App\Models\Branch;
use App\Models\BranchTargetAchievement;
use App\Models\LoanDisbursalRecord;
use App\Models\ScoringRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TargetAchievementImportService
{
    /**
     * Common branch alias mapping for variations between MIS sheets and DB.
     */
    protected array $branchAliases = [
        'khedbrahma' => 'Khedbhrhma (gujarat)',
        'khedbrahma(gujarat)' => 'Khedbhrhma (gujarat)',
        'junagadh' => 'Junagarh',
        'junagadh(gj)' => 'Junagarh',
        'sardarshahar' => 'Sardarshahar (churu)',
        'sardarsahar' => 'Sardarshahar (churu)',
        'nasrullaganj' => 'Nasrullaganj-mp',
        'nasrullaganj(mp)' => 'Nasrullaganj-mp',
        'khandwa' => 'Khandwa-mp',
        'khandwa(mp)' => 'Khandwa-mp',
        'dhamtari' => 'Dhamtari - Cg',
        'dhamtari(cg)' => 'Dhamtari - Cg',
        'dhamtari (cg)' => 'Dhamtari - Cg',
        'narmadapuram' => 'Narmadapuram Mp',
        'narmadapuram (mp)' => 'Narmadapuram Mp',
        'biaora' => 'Biaora Mp',
        'biaora (mp)' => 'Biaora Mp',
        'renwal' => 'Renwal',
        'kishangarh renwal' => 'Renwal',
        'gopinath marg' => 'Gopinath Marg (jaipur)',
        'gopinath marg (jaipur)' => 'Gopinath Marg (jaipur)',
        'fatehpur' => 'Fatehpur',
        'fathehpur(up)' => 'Fatehpur',
        'fathehpur (up)' => 'Fatehpur',
        'sujangarh' => 'Sujangarh (raj)',
        'sujangarh (raj)' => 'Sujangarh (raj)',
        'churu' => 'Churu',
        'sardarshahar (churu)' => 'Sardarshahar (churu)',
        'ashoknagar(mp)' => 'Ashoknagar',
        'ashoknagar (mp)' => 'Ashoknagar',
        'agar malwa(mp)' => 'Agar Malwa (mp)',
        'alot (mp)' => 'Alot (mp)',
        'amreli (gujarat)' => 'Amreli',
        'anand (gujarat)' => 'Anand (gujarat)',
        'ankleshwar (gujarat)' => 'Ankleshwar (gujarat)',
        'ashta (mp)' => 'Ashta (mp)',
        'auraiya(up)' => 'Auraiya',
        'badnawar (mp)' => 'Badnawar (mp)',
        'bagru (jaipur)' => 'Bagru (jaipur)',
        'baroda (gujarat)' => 'Baroda (gujarat)',
        'bayad(gj)' => 'Bayad',
        'bodeli (gujarat)' => 'Bodeli (gujarat)',
        'chalisgaon(mh)' => 'Chalisgaon',
        'chopda(mh)' => 'Chopda',
        'damoh (mp)' => 'Damoh (mp)',
        'gadarwara (mp)' => 'Gadarwara (mp)',
        'kaushambi(up)' => 'Kaushambi',
        'kheda(gj)' => 'Kheda',
        'khirkiya(mp)' => 'Khirkiya',
    ];

    /**
     * Import a sheet (auto-detects whether it is a Target sheet or a Book1 Disbursal sheet).
     */
    public function import(string $filePath, ?int $defaultBranchId = null, ?string $sheetType = null): array
    {
        $rows = $this->readFileRows($filePath);

        if (empty($rows)) {
            throw new \InvalidArgumentException('Spreadsheet or CSV file is empty.');
        }

        // Determine header row and normalized headers
        $detection = $this->detectSheetTypeAndHeaders($rows);
        $type = $sheetType ?: $detection['type'];
        $headerRowIdx = $detection['header_row_idx'];
        $columnMapping = $detection['column_mapping'];

        if ($type === 'book1_disbursal') {
            return $this->importBook1Disbursals($rows, $headerRowIdx, $columnMapping, $defaultBranchId);
        } else {
            return $this->importBranchTargets($rows, $headerRowIdx, $columnMapping, $defaultBranchId);
        }
    }

    /**
     * Detect whether sheet is loan-level disbursal or branch-level targets.
     */
    protected function detectSheetTypeAndHeaders(array $rows): array
    {
        foreach ($rows as $idx => $row) {
            $normalizedRow = [];
            foreach ($row as $colKey => $cellVal) {
                $cleaned = strtolower(trim(preg_replace('/[^a-zA-Z0-9 ]+/', ' ', (string)$cellVal)));
                $cleaned = preg_replace('/\s+/', ' ', $cleaned);
                $normalizedRow[$colKey] = $cleaned;
            }

            // Check if it's Book1 Loan Disbursal sheet
            if (in_array('loan account number', $normalizedRow) || in_array('disbursal amount', $normalizedRow) || (in_array('disbursal date', $normalizedRow) && in_array('disbursal status', $normalizedRow))) {
                $mapping = [];
                $headerAliases = [
                    'loan account number' => 'loan_account_number',
                    'loan no' => 'loan_account_number',
                    'customer name' => 'customer_name',
                    'applicant name' => 'customer_name',
                    'branch name' => 'branch_name',
                    'branch' => 'branch_name',
                    'branch state' => 'state',
                    'state' => 'state',
                    'disbursal amount' => 'disbursal_amount',
                    'disbursed amount' => 'disbursal_amount',
                    'net disbursed amount' => 'disbursal_amount',
                    'disbursal date' => 'disbursal_date',
                    'disbursal month' => 'disbursal_month',
                    'product name' => 'product_name',
                    'product' => 'product_name',
                ];

                foreach ($normalizedRow as $colKey => $h) {
                    if (isset($headerAliases[$h]) && !isset($mapping[$headerAliases[$h]])) {
                        $mapping[$headerAliases[$h]] = $colKey;
                    }
                }

                return [
                    'type' => 'book1_disbursal',
                    'header_row_idx' => $idx,
                    'column_mapping' => $mapping,
                ];
            }

            // Check if it's Target Sheet
            if (in_array('branch target', $normalizedRow) || in_array('actual dis', $normalizedRow) || in_array('portfolio dis amount', $normalizedRow) || (in_array('branch name', $normalizedRow) && in_array('target', $normalizedRow))) {
                $mapping = [];
                $headerAliases = [
                    'branch name' => 'branch_name',
                    'branch' => 'branch_name',
                    'state' => 'state',
                    'branch target' => 'target_amount',
                    'target' => 'target_amount',
                    'target amount' => 'target_amount',
                    'disbursed amount' => 'disbursed_amount',
                    'actual disbursed amount' => 'disbursed_amount',
                    'actual disbursed' => 'disbursed_amount',
                    'disbursed' => 'disbursed_amount',
                    'disbursement' => 'disbursed_amount',
                    'incentive dis amount' => 'incentive_disbursed_amount',
                    'portfolio dis amount' => 'portfolio_disbursed_amount',
                    'actual dis' => 'achievement_percentage',
                    'actual dis percent' => 'achievement_percentage',
                    'portfolio dis' => 'portfolio_percentage',
                ];

                foreach ($normalizedRow as $colKey => $h) {
                    if (isset($headerAliases[$h]) && !isset($mapping[$headerAliases[$h]])) {
                        $mapping[$headerAliases[$h]] = $colKey;
                    }
                }

                return [
                    'type' => 'branch_target',
                    'header_row_idx' => $idx,
                    'column_mapping' => $mapping,
                ];
            }
        }

        // Fallback: look for branch name
        foreach ($rows as $idx => $row) {
            foreach ($row as $colKey => $cellVal) {
                $cleaned = strtolower(trim((string)$cellVal));
                if ($cleaned === 'branch' || $cleaned === 'branch name') {
                    return [
                        'type' => 'branch_target',
                        'header_row_idx' => $idx,
                        'column_mapping' => ['branch_name' => $colKey],
                    ];
                }
            }
        }

        throw new \InvalidArgumentException('Could not identify valid headers in the uploaded sheet. Required: Loan Disbursal headers or Branch Target headers.');
    }

    /**
     * Import Book1 Loan Disbursal records (granular loan rows) and roll up to branch target achievements.
     */
    public function importBook1Disbursals(array $rows, int $headerRowIdx, array $colMap, ?int $defaultBranchId = null): array
    {
        $branchCache = $this->loadBranchCache();
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $affectedBranchIds = [];

        $totalRows = count($rows);

        for ($rowIdx = $headerRowIdx + 1; $rowIdx < $totalRows; $rowIdx++) {
            $row = $rows[$rowIdx];
            $loanAccount = isset($colMap['loan_account_number']) ? trim((string)($row[$colMap['loan_account_number']] ?? '')) : null;

            if (empty($loanAccount)) {
                continue;
            }

            try {
                $disbAmount = isset($colMap['disbursal_amount']) ? (float)str_replace(',', '', (string)($row[$colMap['disbursal_amount']] ?? 0)) : 0.0;
                $customerName = isset($colMap['customer_name']) ? trim((string)($row[$colMap['customer_name']] ?? '')) : null;
                $productName = isset($colMap['product_name']) ? trim((string)($row[$colMap['product_name']] ?? '')) : null;
                $disbMonth = isset($colMap['disbursal_month']) ? trim((string)($row[$colMap['disbursal_month']] ?? '')) : null;
                $rawBranch = isset($colMap['branch_name']) ? trim((string)($row[$colMap['branch_name']] ?? '')) : null;
                $rawState = isset($colMap['state']) ? trim((string)($row[$colMap['state']] ?? '')) : null;

                $disbDate = null;
                if (isset($colMap['disbursal_date'])) {
                    $disbDate = $this->parseDate($row[$colMap['disbursal_date']] ?? null);
                }

                // Resolve branch
                $branch = null;
                if ($defaultBranchId) {
                    $branch = Branch::find($defaultBranchId);
                }
                if (!$branch && !empty($rawBranch)) {
                    $branch = $this->resolveBranch($rawBranch, $branchCache, $rawState);
                }

                $branchId = $branch ? $branch->id : null;
                $branchName = $branch ? $branch->branch_name : ($rawBranch ?: 'Unknown Branch');

                // Quarter & Year deduction
                $quarter = 'Q1';
                $finYear = '2026-2027';
                if ($disbDate) {
                    $m = $disbDate->month;
                    $y = $disbDate->year;
                    $quarter = match (true) {
                        $m >= 4 && $m <= 6 => 'Q1',
                        $m >= 7 && $m <= 9 => 'Q2',
                        $m >= 10 && $m <= 12 => 'Q3',
                        default => 'Q4',
                    };
                    $finYear = ($m >= 4) ? "{$y}-" . ($y + 1) : ($y - 1) . "-{$y}";
                }

                $record = LoanDisbursalRecord::updateOrCreate(
                    ['loan_account_number' => $loanAccount],
                    [
                        'customer_name' => $customerName,
                        'branch_name' => $branchName,
                        'branch_id' => $branchId,
                        'state' => $rawState ?: ($branch->meta['state'] ?? null),
                        'disbursal_amount' => $disbAmount,
                        'disbursal_date' => $disbDate ? $disbDate->format('Y-m-d') : null,
                        'disbursal_month' => $disbMonth,
                        'product_name' => $productName,
                        'financial_quarter' => $quarter,
                        'financial_year' => $finYear,
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                if ($branchId) {
                    $affectedBranchIds[$branchId] = $branchId;
                }
            } catch (\Throwable $e) {
                $failed++;
                if (count($errors) < 10) {
                    $errors[] = "Row " . ($rowIdx + 1) . " ({$loanAccount}): " . $e->getMessage();
                }
            }
        }

        // Aggregate branch disbursed amount and re-calculate Section I.1
        $this->aggregateAndScoreBranches($affectedBranchIds);

        return [
            'type' => 'book1_disbursal',
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'total_processed' => $created + $updated,
            'branches_affected_count' => count($affectedBranchIds),
            'errors' => $errors,
        ];
    }

    /**
     * Import Branch Target records from sheet (File 2 format).
     */
    public function importBranchTargets(array $rows, int $headerRowIdx, array $colMap, ?int $defaultBranchId = null): array
    {
        $branchCache = $this->loadBranchCache();
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $affectedBranchIds = [];

        $totalRows = count($rows);

        for ($rowIdx = $headerRowIdx + 1; $rowIdx < $totalRows; $rowIdx++) {
            $row = $rows[$rowIdx];
            $rawBranch = isset($colMap['branch_name']) ? trim((string)($row[$colMap['branch_name']] ?? '')) : null;

            if (empty($rawBranch)) {
                continue;
            }

            try {
                $targetAmt = isset($colMap['target_amount']) ? (float)str_replace(',', '', (string)($row[$colMap['target_amount']] ?? 0)) : 0.0;
                $directDisb = isset($colMap['disbursed_amount']) ? (float)str_replace(',', '', (string)($row[$colMap['disbursed_amount']] ?? 0)) : null;
                $incDis = isset($colMap['incentive_disbursed_amount']) ? (float)str_replace(',', '', (string)($row[$colMap['incentive_disbursed_amount']] ?? 0)) : null;
                $portDis = isset($colMap['portfolio_disbursed_amount']) ? (float)str_replace(',', '', (string)($row[$colMap['portfolio_disbursed_amount']] ?? 0)) : null;
                $rawState = isset($colMap['state']) ? trim((string)($row[$colMap['state']] ?? '')) : null;

                // Resolve branch
                $branch = null;
                if ($defaultBranchId) {
                    $branch = Branch::find($defaultBranchId);
                }
                if (!$branch && !empty($rawBranch)) {
                    $branch = $this->resolveBranch($rawBranch, $branchCache, $rawState);
                }

                $branchId = $branch ? $branch->id : null;
                $branchName = $branch ? $branch->branch_name : $rawBranch;

                // Default quarter Q1 / AMJ
                $quarter = 'Q1';
                $finYear = '2026-2027';

                $bta = BranchTargetAchievement::firstOrNew([
                    'branch_id' => $branchId,
                    'financial_quarter' => $quarter,
                    'financial_year' => $finYear,
                ]);

                $isNew = !$bta->exists;
                $bta->branch_name = $branchName;
                $bta->state = $rawState ?: ($branch->meta['state'] ?? null);
                $bta->target_amount = $targetAmt;
                if ($incDis !== null) $bta->incentive_disbursed_amount = $incDis;
                if ($portDis !== null) $bta->portfolio_disbursed_amount = $portDis;

                if ($directDisb !== null && $directDisb > 0) {
                    $bta->disbursed_amount = $directDisb;
                } elseif ((float)$bta->disbursed_amount <= 0) {
                    if ($portDis > 0) {
                        $bta->disbursed_amount = $portDis;
                    } elseif ($incDis > 0) {
                        $bta->disbursed_amount = $incDis;
                    }
                }

                $bta->save();

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }

                if ($branchId) {
                    $affectedBranchIds[$branchId] = $branchId;
                }
            } catch (\Throwable $e) {
                $failed++;
                if (count($errors) < 10) {
                    $errors[] = "Row " . ($rowIdx + 1) . " ({$rawBranch}): " . $e->getMessage();
                }
            }
        }

        // Aggregate and score
        $this->aggregateAndScoreBranches($affectedBranchIds);

        return [
            'type' => 'branch_target',
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'total_processed' => $created + $updated,
            'branches_affected_count' => count($affectedBranchIds),
            'errors' => $errors,
        ];
    }

    /**
     * Recalculates achievement percentage and auto-scores Section I.1 for affected branches.
     */
    public function aggregateAndScoreBranches(array $branchIds): void
    {
        $inspectionService = app(AuditInspectionService::class);

        // Fetch active Target vs Achievement scoring rules
        $rules = ScoringRule::activeRules()
            ->where('rule_type', 'target_achievement')
            ->orderBy('min_days', 'asc')
            ->get();

        foreach ($branchIds as $bId) {
            $bta = BranchTargetAchievement::where('branch_id', $bId)->first();
            if (!$bta) continue;

            // Check if Book1 loan records exist for this branch
            $loansQuery = LoanDisbursalRecord::where('branch_id', $bId);
            $book1TotalDisb = (float)$loansQuery->sum('disbursal_amount');
            $book1Count = $loansQuery->count();

            if ($book1Count > 0) {
                $bta->disbursed_amount = $book1TotalDisb;
                $bta->total_loans = $book1Count;
            }

            $target = (float)$bta->target_amount;
            $disbursed = (float)$bta->disbursed_amount;

            $achPct = 0.0;
            if ($target > 0) {
                $achPct = round(($disbursed / $target) * 100.0, 2);
            }
            $bta->achievement_percentage = $achPct;

            // Calculate marks obtained out of 15
            $carryMarks = 15.0000;
            $matchedRule = null;

            if ($rules->isNotEmpty()) {
                foreach ($rules as $rule) {
                    if ($achPct >= $rule->min_days && $achPct <= $rule->max_days) {
                        $matchedRule = $rule;
                        break;
                    }
                }
            }

            if ($matchedRule) {
                $scorePct = (float)$matchedRule->score;
                $obtained = round(($scorePct / 100.0) * $carryMarks, 2);
                $tier = $matchedRule->description ?: "{$matchedRule->min_days}%-{$matchedRule->max_days}% ({$scorePct}% Marks)";
            } else {
                // Fallback policy standards
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

            $bta->marks_obtained = $obtained;
            $bta->carry_marks = $carryMarks;
            $bta->tier = $tier;
            $bta->save();

            // Auto-score Section I.1 in latest inspection for this branch
            $latestInspection = AuditInspection::where('branch_id', $bId)->latest('inspection_date')->first();
            if ($latestInspection) {
                $inspectionService->evaluateAutoCheckpoints($latestInspection);
                $latestInspection->recalculateTotals();
            }
        }
    }

    /**
     * Cache existing branches for fast matching.
     */
    protected function loadBranchCache(): array
    {
        $branches = Branch::all();
        $cache = [];
        foreach ($branches as $b) {
            $clean = trim(preg_replace('/\s+/', ' ', strtolower($b->branch_name)));
            $cache[$clean] = $b;
            $base = trim(preg_replace('/\s*\(.*?\)\s*/', '', $clean));
            if (!isset($cache[$base])) {
                $cache[$base] = $b;
            }
        }
        return $cache;
    }

    /**
     * Resolve branch with alias mapping and auto-create fallback.
     */
    protected function resolveBranch(string $rawName, array &$cache, ?string $stateName = null): Branch
    {
        $clean = trim(preg_replace('/\s+/', ' ', strtolower($rawName)));

        if (isset($cache[$clean])) {
            return $cache[$clean];
        }

        $baseName = trim(preg_replace('/\s*\(.*?\)\s*/', '', $clean));
        if (isset($cache[$baseName])) {
            return $cache[$baseName];
        }

        if (isset($this->branchAliases[$clean])) {
            $aliasTarget = strtolower($this->branchAliases[$clean]);
            if (isset($cache[$aliasTarget])) {
                return $cache[$aliasTarget];
            }
        }

        if (isset($this->branchAliases[$baseName])) {
            $aliasTarget = strtolower($this->branchAliases[$baseName]);
            if (isset($cache[$aliasTarget])) {
                return $cache[$aliasTarget];
            }
        }

        foreach ($cache as $cachedName => $branchObj) {
            $cachedBase = trim(preg_replace('/\s*\(.*?\)\s*/', '', $cachedName));
            if ($baseName === $cachedBase || str_starts_with($cachedName, $baseName) || str_starts_with($baseName, $cachedName)) {
                return $branchObj;
            }
        }

        // Auto-create branch if not found so no records are lost
        $branchDisplayName = ucwords($baseName ?: $rawName);
        $codePrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $branchDisplayName), 0, 4));
        $code = ($codePrefix ?: 'BR') . rand(10, 99);

        $newBranch = Branch::create([
            'branch_name' => $branchDisplayName,
            'branch_code' => $code,
            'is_active' => true,
            'meta' => [
                'state' => $stateName,
                'auto_created_from_target_sheet' => true,
            ],
        ]);

        $cache[$clean] = $newBranch;
        $cache[$baseName] = $newBranch;

        return $newBranch;
    }

    /**
     * Read spreadsheet or CSV rows into indexed array of arrays.
     */
    protected function readFileRows(string $filePath): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv' || $ext === 'txt') {
            $rows = [];
            if (($handle = fopen($filePath, 'r')) !== false) {
                while (($data = fgetcsv($handle, 10000, ',')) !== false) {
                    $rows[] = $data;
                }
                fclose($handle);
            }
            return $rows;
        }

        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        return $worksheet->toArray(null, true, true, false);
    }

    /**
     * Parse date from various formats including Excel serial numbers.
     */
    protected function parseDate($value): ?Carbon
    {
        if (empty($value)) return null;

        if (is_numeric($value) && (int)$value > 30000 && (int)$value < 60000) {
            try {
                $dateTime = ExcelDate::excelToDateTimeObject((int)$value);
                return Carbon::instance($dateTime);
            } catch (\Throwable $e) {}
        }

        $val = trim((string)$value);
        try {
            return Carbon::parse($val);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
