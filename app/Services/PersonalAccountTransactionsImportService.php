<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\PersonalAccountTransaction;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PersonalAccountTransactionsImportService
{
    protected array $headerMap = [
        'merchant name' => 'merchant_name',
        'merchant_name' => 'merchant_name',
        'decentro transaction id' => 'decentro_transaction_id',
        'decentro_transaction_id' => 'decentro_transaction_id',
        'transaction id' => 'decentro_transaction_id',
        'transaction date' => 'transaction_date',
        'transaction_date' => 'transaction_date',
        'collection type' => 'collection_type',
        'collection_type' => 'collection_type',
        'transaction amount' => 'transaction_amount',
        'transaction_amount' => 'transaction_amount',
        'amount' => 'transaction_amount',
        'payer vpa' => 'payer_vpa',
        'payer_vpa' => 'payer_vpa',
        'vpa' => 'payer_vpa',
        'mobile number' => 'mobile_number',
        'mobile_number' => 'mobile_number',
        'mobile no' => 'mobile_number',
        'employee id' => 'employee_code',
        'employee_id' => 'employee_code',
        'employee code' => 'employee_code',
        'employee name' => 'employee_name',
        'employee_name' => 'employee_name',
        'branch' => 'branch_name',
        'branch name' => 'branch_name',
        'department' => 'department',
    ];

    public function import(string $filePath, ?int $defaultBranchId = null, string $quarter = 'Q4', string $finYear = '2025-2026'): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, true);

        if (empty($rows)) {
            throw new \InvalidArgumentException('Spreadsheet is empty.');
        }

        // Find header row (looks for "decentro" or "payer vpa")
        $headerRowIdx = null;
        $headers = [];

        foreach ($rows as $idx => $row) {
            $cleaned = array_map(fn($v) => strtolower(trim((string)$v)), $row);
            if (in_array('decentro transaction id', $cleaned) || in_array('payer vpa', $cleaned) || in_array('merchant name', $cleaned)) {
                $headerRowIdx = $idx;
                $headers = $cleaned;
                break;
            }
        }

        if ($headerRowIdx === null) {
            $headerRowIdx = 1;
            $headers = array_map(fn($v) => strtolower(trim((string)$v)), $rows[1]);
        }

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        $branches = Branch::all()->keyBy(fn($b) => strtolower(trim($b->branch_name)));
        $employeesByCode = Employee::all()->keyBy('employee_code');
        $employeesByMobile = Employee::whereNotNull('mobile_number')->get()->keyBy('mobile_number');

        foreach ($rows as $idx => $row) {
            if ($idx <= $headerRowIdx) {
                continue;
            }

            $data = [];
            foreach ($row as $colKey => $val) {
                $headerName = $headers[$colKey] ?? null;
                if ($headerName && isset($this->headerMap[$headerName])) {
                    $attr = $this->headerMap[$headerName];
                    $data[$attr] = is_string($val) ? trim($val) : $val;
                }
            }

            if (empty($data['decentro_transaction_id']) && empty($data['payer_vpa'])) {
                continue;
            }

            try {
                // Link branch
                $branchId = $defaultBranchId;
                if (!empty($data['branch_name'])) {
                    $bKey = strtolower(trim($data['branch_name']));
                    if (isset($branches[$bKey])) {
                        $branchId = $branches[$bKey]->id;
                    }
                }
                $data['branch_id'] = $branchId;

                // Match employee by code or mobile
                $emp = null;
                if (!empty($data['employee_code']) && isset($employeesByCode[$data['employee_code']])) {
                    $emp = $employeesByCode[$data['employee_code']];
                } elseif (!empty($data['mobile_number']) && isset($employeesByMobile[$data['mobile_number']])) {
                    $emp = $employeesByMobile[$data['mobile_number']];
                }

                if ($emp) {
                    $data['employee_id'] = $emp->id;
                    $data['employee_code'] = $emp->employee_code;
                    $data['employee_name'] = $emp->full_name;
                    if (!$branchId && $emp->branch_id) {
                        $data['branch_id'] = $emp->branch_id;
                    }
                }

                // Parse transaction date
                if (!empty($data['transaction_date'])) {
                    $data['transaction_date'] = Carbon::parse($data['transaction_date'])->toDateTimeString();
                }

                $data['quarter'] = $quarter;
                $data['financial_year'] = $finYear;
                $data['is_violation'] = true;

                $txn = PersonalAccountTransaction::where('decentro_transaction_id', $data['decentro_transaction_id'])->first();
                if ($txn) {
                    $txn->update($data);
                    $updated++;
                } else {
                    PersonalAccountTransaction::create($data);
                    $created++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = "Row {$idx}: " . $e->getMessage();
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => array_slice($errors, 0, 50),
        ];
    }
}
