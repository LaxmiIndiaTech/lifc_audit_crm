<?php

namespace App\Services;

use App\Models\AuditInspection;
use App\Models\Branch;
use App\Models\LoanTatRecord;
use App\Models\State;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TatRecordsImportService
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
        'sujangarh' => 'Sujangarh (raj)',
        'sujangarh (raj)' => 'Sujangarh (raj)',
        'churu' => 'Churu',
        'sardarshahar (churu)' => 'Sardarshahar (churu)',
    ];

    public function import(string $filePath, ?int $defaultBranchId = null): array
    {
        $rows = $this->readFileRows($filePath);

        if (empty($rows)) {
            throw new \InvalidArgumentException('Spreadsheet or CSV file is empty.');
        }

        // Header mapping
        $headerMap = [
            'loan account number' => 'loan_account_number',
            'loan no' => 'loan_account_number',
            'loan account no' => 'loan_account_number',
            'customer name' => 'customer_name',
            'mobile no' => 'mobile_no',
            'mobile number' => 'mobile_no',
            'product name' => 'product_name',
            'product type' => 'product_name',
            'product' => 'product_name',
            'login date' => 'login_date',
            'disbursal date' => 'disbursal_date',
            'sourcing rm id' => 'sourcing_rm_id',
            'relationship officer userid' => 'sourcing_rm_id',
            'ro userid' => 'sourcing_rm_id',
            'sourcing rm name' => 'sourcing_rm_name',
            'relationship officer' => 'sourcing_rm_name',
            'ro name' => 'sourcing_rm_name',
            'branch name' => 'branch_name',
            'branch' => 'branch_name',
            'state' => 'state',
            'branch state' => 'state',
            'loan status' => 'loan_status',
            'status' => 'loan_status',
            'days' => 'days',
            'average days' => 'branch_average_days',
            'average days amj' => 'branch_average_days',
        ];

        // Find header row
        $headerRowIdx = null;
        $columnMapping = [];

        foreach ($rows as $idx => $row) {
            $normalizedRow = [];
            foreach ($row as $colKey => $cellVal) {
                $cleaned = strtolower(trim(preg_replace('/[^a-zA-Z0-9 ]+/', ' ', (string)$cellVal)));
                $cleaned = preg_replace('/\s+/', ' ', $cleaned);
                $normalizedRow[$colKey] = $cleaned;
            }

            if (in_array('loan account number', $normalizedRow) || in_array('loan no', $normalizedRow) || in_array('disbursal date', $normalizedRow)) {
                $headerRowIdx = $idx;
                foreach ($normalizedRow as $colKey => $cleanedHeader) {
                    if (isset($headerMap[$cleanedHeader])) {
                        $columnMapping[$colKey] = $headerMap[$cleanedHeader];
                    }
                }
                break;
            }
        }

        if ($headerRowIdx === null || empty($columnMapping)) {
            throw new \InvalidArgumentException('Could not identify valid TAT table headers (e.g. Loan Account Number, Login Date, Disbursal Date, Branch Name).');
        }

        $branches = Branch::all();
        $branchCache = [];
        foreach ($branches as $b) {
            $branchCache[strtolower(trim($b->branch_name))] = $b;
        }

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $affectedBranchIds = [];

        $dataRows = array_slice($rows, $headerRowIdx);

        foreach ($dataRows as $rowIdx => $row) {
            $record = [];
            foreach ($columnMapping as $colKey => $field) {
                $record[$field] = isset($row[$colKey]) ? trim((string)$row[$colKey]) : null;
            }

            $loanAccount = $record['loan_account_number'] ?? null;
            if (empty($loanAccount) || strtolower($loanAccount) === 'loan account number') {
                continue; // skip empty or secondary header rows
            }

            try {
                $loginDate = $this->parseDate($record['login_date'] ?? null);
                $disbursalDate = $this->parseDate($record['disbursal_date'] ?? null);

                if (!$loginDate || !$disbursalDate) {
                    $failed++;
                    $errors[] = "Row " . ($rowIdx + 1) . ": Invalid login or disbursal date for {$loanAccount}";
                    continue;
                }

                // Days calculation
                $days = null;
                if (!empty($record['days']) && is_numeric($record['days'])) {
                    $days = (float)$record['days'];
                } else {
                    $days = max(0, $loginDate->diffInDays($disbursalDate, false));
                }

                // Branch resolution
                $rawBranch = $record['branch_name'] ?? null;
                $branch = null;

                if ($defaultBranchId) {
                    $branch = Branch::find($defaultBranchId);
                }

                if (!$branch && !empty($rawBranch)) {
                    $branch = $this->resolveBranch($rawBranch, $branchCache, $record['state'] ?? null);
                }

                $branchId = $branch ? $branch->id : null;
                $branchName = $branch ? $branch->branch_name : ($rawBranch ?: 'Unknown Branch');

                // Financial Quarter & Year deduction
                $month = $disbursalDate->month;
                $year = $disbursalDate->year;
                $quarter = match (true) {
                    $month >= 4 && $month <= 6 => 'Q1',
                    $month >= 7 && $month <= 9 => 'Q2',
                    $month >= 10 && $month <= 12 => 'Q3',
                    default => 'Q4',
                };
                $finYear = ($month >= 4) ? "{$year}-" . ($year + 1) : ($year - 1) . "-{$year}";

                $tatRecord = LoanTatRecord::updateOrCreate(
                    ['loan_account_number' => $loanAccount],
                    [
                        'customer_name' => $record['customer_name'] ?: 'Customer',
                        'mobile_no' => $record['mobile_no'] ?: null,
                        'product_name' => $record['product_name'] ?: 'Loan',
                        'login_date' => $loginDate->format('Y-m-d'),
                        'disbursal_date' => $disbursalDate->format('Y-m-d'),
                        'sourcing_rm_id' => $record['sourcing_rm_id'] ?: null,
                        'sourcing_rm_name' => $record['sourcing_rm_name'] ?: null,
                        'branch_name' => $branchName,
                        'branch_id' => $branchId,
                        'state' => $record['state'] ?: ($branch->meta['state'] ?? null),
                        'loan_status' => $record['loan_status'] ?: 'Active',
                        'days' => $days,
                        'financial_quarter' => $quarter,
                        'financial_year' => $finYear,
                    ]
                );

                if ($tatRecord->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }

                if ($branchId) {
                    $affectedBranchIds[$branchId] = $branchId;
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Row " . ($rowIdx + 1) . " ({$loanAccount}): " . $e->getMessage();
            }
        }

        // Recalculate average days and auto-score H.1 for all affected branches
        $inspectionService = app(AuditInspectionService::class);
        $evaluatedBranches = [];

        foreach ($affectedBranchIds as $bId) {
            $branchAverage = LoanTatRecord::where('branch_id', $bId)->avg('days');
            $branchAverage = round((float)$branchAverage, 2);

            LoanTatRecord::where('branch_id', $bId)->update(['branch_average_days' => $branchAverage]);

            // Update H.1 in latest inspection for this branch
            $latestInspection = AuditInspection::where('branch_id', $bId)->latest('inspection_date')->first();
            if ($latestInspection) {
                $inspectionService->evaluateAutoCheckpoints($latestInspection);
                $latestInspection->recalculateTotals();
                $evaluatedBranches[] = [
                    'branch_id' => $bId,
                    'branch_name' => Branch::find($bId)->branch_name ?? 'Branch',
                    'average_days' => $branchAverage,
                    'total_loans' => LoanTatRecord::where('branch_id', $bId)->count(),
                ];
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'total_processed' => $created + $updated,
            'branches_affected_count' => count($affectedBranchIds),
            'evaluated_branches' => $evaluatedBranches,
            'errors' => array_slice($errors, 0, 10),
        ];
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

        // Excel numeric serial date (e.g. 46050)
        if (is_numeric($value) && (int)$value > 30000 && (int)$value < 60000) {
            try {
                $dateTime = ExcelDate::excelToDateTimeObject((int)$value);
                return Carbon::instance($dateTime);
            } catch (\Throwable $e) {}
        }

        $val = trim((string)$value);
        // Common formats: YYYY-MM-DD, DD-MM-YYYY, YYYY/MM/DD, DD/MM/YYYY
        try {
            return Carbon::parse($val);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Resolve branch using exact, alias, and fuzzy matching or auto-creates if unknown.
     */
    protected function resolveBranch(string $rawName, array &$cache, ?string $stateName = null): Branch
    {
        $clean = trim(preg_replace('/\s+/', ' ', strtolower($rawName)));

        // 1. Direct cache match
        if (isset($cache[$clean])) {
            return $cache[$clean];
        }

        // 2. Base name (remove parentheses contents e.g. "bagru (jaipur)" -> "bagru")
        $baseName = trim(preg_replace('/\s*\(.*?\)\s*/', '', $clean));
        if (isset($cache[$baseName])) {
            return $cache[$baseName];
        }

        // 3. Aliases dictionary
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

        // 4. Substring search in existing cache
        foreach ($cache as $cachedName => $branchObj) {
            $cachedBase = trim(preg_replace('/\s*\(.*?\)\s*/', '', $cachedName));
            if ($baseName === $cachedBase || str_starts_with($cachedName, $baseName) || str_starts_with($baseName, $cachedName)) {
                return $branchObj;
            }
        }

        // 5. If not found, auto-create the Branch so no records are lost
        $branchDisplayName = ucwords($baseName ?: $rawName);
        $codePrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $branchDisplayName), 0, 4));
        $code = ($codePrefix ?: 'BR') . rand(10, 99);

        $newBranch = Branch::create([
            'branch_name' => $branchDisplayName,
            'branch_code' => $code,
            'is_active' => true,
            'meta' => [
                'state' => $stateName,
                'auto_created_from_tat' => true,
            ],
        ]);

        $cache[$clean] = $newBranch;
        $cache[strtolower($branchDisplayName)] = $newBranch;

        return $newBranch;
    }
}
