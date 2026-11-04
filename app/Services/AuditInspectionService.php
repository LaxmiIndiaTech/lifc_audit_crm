<?php

namespace App\Services;

use App\Models\AuditCheckpoint;
use App\Models\AuditInspection;
use App\Models\Branch;
use App\Models\CashChequeReceipt;
use App\Models\InspectionCheckpointScore;
use App\Models\PersonalAccountTransaction;
use Carbon\Carbon;

class AuditInspectionService
{
    /**
     * Get an existing inspection or create a new one with initialized checkpoints.
     */
    public function getOrCreateInspection(
        int $branchId,
        string $date,
        string $quarter = 'Q4',
        string $finYear = '2025-2026'
    ): AuditInspection {
        $branch = Branch::findOrFail($branchId);
        $parsedDate = Carbon::parse($date);
        $inspectionDate = $parsedDate->format('Y-m-d');
        $monthStr = $parsedDate->format('Y-m');

        $inspection = AuditInspection::where('branch_id', $branch->id)
            ->where(function ($q) use ($inspectionDate, $monthStr) {
                $q->where('inspection_date', $inspectionDate)
                  ->orWhere('inspection_month', $monthStr);
            })
            ->first();

        if (!$inspection) {
            $inspection = AuditInspection::where('branch_id', $branch->id)->latest('inspection_date')->first();
        }

        if (!$inspection) {
            $inspection = AuditInspection::create([
                'branch_id' => $branch->id,
                'inspection_date' => $inspectionDate,
                'inspection_month' => $monthStr,
                'financial_quarter' => $quarter,
                'financial_year' => $finYear,
                'company_name' => 'Laxmi India Finance Ltd.',
                'branch_in_charge_name' => $branch->meta['branch_in_charge_name'] ?? null,
                'branch_in_charge_code' => $branch->meta['branch_in_charge_employee_code'] ?? null,
                'cluster_name' => $branch->meta['cluster'] ?? ($branch->meta['cm_asm_name'] ?? null),
                'cluster_code' => $branch->meta['asm'] ?? ($branch->meta['cm_asm_code'] ?? null),
                'inspection_officials' => 'Internal Audit Team',
                'process_marks_carry' => 40.00,
                'process_marks_obtained' => 0.00,
                'total_marks_carry' => 100.00,
                'status' => 'draft',
            ]);
        } else {
            // Update metadata if missing
            $updates = [];
            if (empty($inspection->branch_in_charge_name) && !empty($branch->meta['branch_in_charge_name'])) {
                $updates['branch_in_charge_name'] = $branch->meta['branch_in_charge_name'];
                $updates['branch_in_charge_code'] = $branch->meta['branch_in_charge_employee_code'] ?? null;
            }
            if (empty($inspection->cluster_name) && !empty($branch->meta['cluster'])) {
                $updates['cluster_name'] = $branch->meta['cluster'];
                $updates['cluster_code'] = $branch->meta['asm'] ?? null;
            }
            if (!empty($updates)) {
                $inspection->update($updates);
            }
        }

        $this->ensureCheckpointsInitialized($inspection);
        $this->evaluateAutoCheckpoints($inspection);
        $inspection->recalculateTotals();

        return $inspection->fresh(['scores', 'branch']);
    }

    /**
     * Initialize all master checkpoints (A.1 to G.4) for this inspection if missing,
     * and sync carry marks with master checkpoints.
     */
    public function ensureCheckpointsInitialized(AuditInspection $inspection): void
    {
        $checkpoints = AuditCheckpoint::active()->get();

        foreach ($checkpoints as $cp) {
            $score = InspectionCheckpointScore::firstOrNew([
                'audit_inspection_id' => $inspection->id,
                'checkpoint_code' => $cp->checkpoint_code,
            ]);

            $score->audit_checkpoint_id = $cp->id;
            $score->section = $cp->section;
            $score->scope_point = $cp->scope_point;
            $score->carry_marks = $cp->carry_marks;
            $score->weight = $cp->weight;
            if (!$score->exists) {
                $score->marks_obtained = 0.00;
                $score->percentage = 0.00;
                $score->department = $cp->default_department;
            } else {
                $score->department = $score->department ?: $cp->default_department;
            }
            $score->save();
        }
    }

    /**
     * Automatically evaluate A.1 and A.2 based on uploaded sheets.
     */
    public function evaluateAutoCheckpoints(AuditInspection $inspection): void
    {
        $branchId = $inspection->branch_id;

        // 1. Evaluate A.1: Collection deposited in T+1 (Scoring Master rules based)
        $receipts = CashChequeReceipt::where('branch_id', $branchId)->with('scoringRule')->get();
        $totalReceipts = $receipts->count();
        $delayedReceipts = $receipts->where('is_over_tat', true)->count();
        $onTimeReceipts = $totalReceipts - $delayedReceipts;

        $carryA1 = 2.0000;
        if ($totalReceipts > 0) {
            $totalPoints = 0.0;
            $ruleBreakdown = [];
            foreach ($receipts as $r) {
                // Rule score out of 10
                $score = $r->scoringRule ? (float)$r->scoringRule->score : ($r->gap_in_days <= 1 ? 10.0 : 0.0);
                $totalPoints += $score;
                $tat = $r->tat_status ?? ('T+' . ($r->gap_in_days ?? 0));
                $ruleBreakdown[$tat] = ($ruleBreakdown[$tat] ?? 0) + 1;
            }

            $avgScore = $totalPoints / $totalReceipts; // Average out of 10
            $obtainedA1 = round(($avgScore / 10.0) * $carryA1, 2);
            $pctA1 = round(($obtainedA1 / $carryA1) * 100, 2);

            $breakdownStr = [];
            ksort($ruleBreakdown);
            foreach ($ruleBreakdown as $tat => $cnt) {
                $breakdownStr[] = "{$tat}: {$cnt}";
            }
            $breakdownSummary = implode(', ', $breakdownStr);

            $commentA1 = "Total {$totalReceipts} receipts evaluated ({$breakdownSummary}). Adherence: {$onTimeReceipts} on-time, {$delayedReceipts} delayed. Scoring Master weighted score: " . round($avgScore, 2) . "/10.";
            $actionA1 = $delayedReceipts > 0 ? "Strictly adhere to T+1 deposit timeline and report exceptions to HO." : "All collections deposited within T+1 norms.";
            $calcData = [
                'total_receipts' => $totalReceipts,
                'delayed_receipts' => $delayedReceipts,
                'on_time_receipts' => $onTimeReceipts,
                'average_rule_score' => round($avgScore, 2),
                'breakdown' => $ruleBreakdown,
                'formula' => '(average_rule_score / 10.0) * carry_marks',
            ];
        } else {
            $obtainedA1 = 0.0000;
            $pctA1 = 0.00;
            $commentA1 = "";
            $actionA1 = "";
            $calcData = null;
        }

        InspectionCheckpointScore::where('audit_inspection_id', $inspection->id)
            ->where('checkpoint_code', 'A.1')
            ->update([
                'carry_marks' => $carryA1,
                'marks_obtained' => $obtainedA1,
                'percentage' => $pctA1,
                'visitor_comment' => $commentA1,
                'immediate_action' => $actionA1,
                'department' => 'CMS',
                'calculation_data' => $calcData,
            ]);

        // 2. Evaluate A.2: Collection Amount Deposited in Personal Account
        $violationsQuery = PersonalAccountTransaction::where('branch_id', $branchId);
        $violationTxns = (clone $violationsQuery)->where('is_violation', true)->get();
        $violationCount = $violationTxns->count();

        $carryA2 = 2.0000;
        if ($violationCount == 0) {
            $obtainedA2 = 2.0000;
            $pctA2 = 100.00;
            $commentA2 = "No collections carried out through personal bank accounts.";
            $actionA2 = "Satisfactory.";
            $calcDataA2 = [
                'violation_count' => 0,
                'violating_employees_count' => 0,
                'violating_employees' => [],
                'total_deduction' => 0.0,
                'marks_obtained' => $carryA2,
                'carry_marks' => $carryA2,
                'rule' => 'Zero violations = 2.00 marks (100% Pass)',
            ];
        } else {
            // Find unique violating employees
            $violatingEmps = [];
            foreach ($violationTxns as $vt) {
                $c = $vt->employee_code ?: ($vt->mobile_number ?: 'N/A');
                $n = $vt->employee_name ?: 'Employee';
                $violatingEmps[$c] = "{$n} ({$c})";
            }
            $violatingEmpCount = count($violatingEmps);
            $empList = array_values($violatingEmps);

            // Rule: Agar 1 bhi employee match karta hai to 0 marks, 0 match par 2 marks
            $obtainedA2 = 0.0000;
            $pctA2 = 0.00;

            $empSummary = implode(', ', array_slice($empList, 0, 4));
            if (count($empList) > 4) {
                $empSummary .= " and " . (count($empList) - 4) . " more";
            }

            $commentA2 = "{$violationCount} transaction(s) in {$inspection->financial_quarter} were carried out using {$violatingEmpCount} employee personal bank account/mobile UPI ({$empSummary}). Zero tolerance policy: 0 marks awarded.";
            $actionA2 = "Strictly prohibit personal UPI/VPA accounts. Mandate official company QR code only. Disciplinary action required.";
            $calcDataA2 = [
                'violation_count' => $violationCount,
                'violating_employees_count' => $violatingEmpCount,
                'violating_employees' => $empList,
                'total_deduction' => $carryA2,
                'marks_obtained' => 0.0,
                'carry_marks' => $carryA2,
                'rule' => '1 or more violating employees = 0 marks (Fail), 0 violations = 2 marks (Pass)',
            ];
        }

        InspectionCheckpointScore::where('audit_inspection_id', $inspection->id)
            ->where('checkpoint_code', 'A.2')
            ->update([
                'carry_marks' => $carryA2,
                'marks_obtained' => $obtainedA2,
                'percentage' => $pctA2,
                'visitor_comment' => $commentA2,
                'immediate_action' => $actionA2,
                'department' => 'CMS',
                'calculation_data' => $calcDataA2,
            ]);

        $inspection->recalculateTotals();
    }

    /**
     * Manually update a checkpoint score (for Sections B through G).
     */
    public function updateManualScore(
        AuditInspection $inspection,
        string $checkpointCode,
        float $marksObtained,
        ?string $visitorComment = null,
        ?string $immediateAction = null,
        ?string $department = null
    ): InspectionCheckpointScore {
        $score = InspectionCheckpointScore::where('audit_inspection_id', $inspection->id)
            ->where('checkpoint_code', $checkpointCode)
            ->firstOrFail();

        $carryMarks = (float)$score->carry_marks;
        $clampedObtained = max(0.0, min($marksObtained, $carryMarks));
        $percentage = $carryMarks > 0 ? round(($clampedObtained / $carryMarks) * 100, 2) : 0;

        $score->update([
            'marks_obtained' => $clampedObtained,
            'percentage' => $percentage,
            'visitor_comment' => $visitorComment ?? $score->visitor_comment,
            'immediate_action' => $immediateAction ?? $score->immediate_action,
            'department' => $department ?? $score->department,
        ]);

        $inspection->recalculateTotals();

        return $score->fresh();
    }

    /**
     * Seed predefined scores for a full inspection (e.g. from the Kotputali sample sheet).
     */
    public function applyPredefinedSampleScores(AuditInspection $inspection): void
    {
        $scoresData = [
            'B.1' => ['obtained' => 2.0, 'comment' => 'All ok', 'action' => 'Satisfactory.'],
            'B.2' => ['obtained' => 0.0, 'comment' => 'No, Branch does not have updated product/credit policy in Physical for Readable for every Employee.', 'action' => 'HO Ops to dispatch updated physical booklet.'],
            'B.3' => ['obtained' => 1.0, 'comment' => 'Yes, the information regarding non-refundable charges has been pasted on branch.', 'action' => 'Satisfactory.'],
            'B.4' => ['obtained' => 1.0, 'comment' => 'Yes, all employees were wearing ID cards.', 'action' => 'Satisfactory.'],
            'C.1' => ['obtained' => 2.0, 'comment' => 'Customer Prem Chand (LAPKTP202601439) not given information about upfront charges.', 'action' => 'Branch to ensure charges disclosed upfront to all customers.'],
            'C.2' => ['obtained' => 1.0, 'comment' => 'Customer complaints not pending at branch.', 'action' => 'Satisfactory.'],
            'C.3' => ['obtained' => 2.0, 'comment' => 'No, we did not find Property Papers and NOC acknowledgement copies at branch.', 'action' => 'Branch to maintain NOC files properly.'],
            'D.1' => ['obtained' => 5.0, 'comment' => 'Mandatory documents displayed on notice board except Grievance details with email/customer care not pasted.', 'action' => 'Admin to paste updated contact grievance posters.'],
            'D.2' => ['obtained' => 2.0, 'comment' => 'Display board and LIFC board properly displayed.', 'action' => 'Satisfactory.'],
            'D.3' => ['obtained' => 1.0, 'comment' => 'Branch is situated at 2nd floor and clearly visible from main road.', 'action' => 'Satisfactory.'],
            'E.1' => ['obtained' => 1.0, 'comment' => 'Yes, branch maintains and updates registers at branch.', 'action' => 'Satisfactory.'],
            'E.2' => ['obtained' => 1.0, 'comment' => 'Yes, OPP and other critical documents properly placed.', 'action' => 'Satisfactory.'],
            'E.3' => ['obtained' => 1.0, 'comment' => 'No blank/unnamed PDC cheques found.', 'action' => 'Satisfactory.'],
            'E.4' => ['obtained' => 1.0, 'comment' => 'No old/rejected files or unauthorized stamps lying.', 'action' => 'Satisfactory.'],
            'F.1' => ['obtained' => 1.0, 'comment' => 'Latest rent agreement available with contact details of Landlord.', 'action' => 'Satisfactory.'],
            'F.2' => ['obtained' => 1.0, 'comment' => 'No tobacco or cigarette found.', 'action' => 'Satisfactory.'],
            'F.3' => ['obtained' => 1.0, 'comment' => 'Branch maintained neat and clean. Paying Rs. 2500 for housekeeping.', 'action' => 'Satisfactory.'],
            'F.4' => ['obtained' => 1.0, 'comment' => '1 standee assigned to branch not found during physical verification.', 'action' => 'Locate standee or write off in AIMS.'],
            'F.5' => ['obtained' => 1.0, 'comment' => 'No assets scrapped yet.', 'action' => 'Satisfactory.'],
            'G.1' => ['obtained' => 1.0, 'comment' => 'All USB ports blocked.', 'action' => 'Satisfactory.'],
            'G.2' => ['obtained' => 1.0, 'comment' => 'No unwanted/unethical data found in systems.', 'action' => 'Satisfactory.'],
            'G.3' => ['obtained' => 1.0, 'comment' => 'Anti-virus installed and updated.', 'action' => 'Satisfactory.'],
            'G.4' => ['obtained' => 2.0, 'comment' => 'Major IT assets matched with HO Records/AIMS.', 'action' => 'Satisfactory.'],
            'H.1' => ['obtained' => 1.0, 'comment' => 'Average 32 days from login to disbursement in last quarter.', 'action' => 'Reduce TAT to under 10 days.'],
            'I.1' => ['obtained' => 15.0, 'comment' => 'Achievement average 121.45% in last quarter.', 'action' => 'Satisfactory.'],
            'J.1' => ['obtained' => 15.0, 'comment' => 'Branch NPA is 0% (Low Risk).', 'action' => 'Satisfactory.'],
            'K.1' => ['obtained' => 10.0, 'comment' => 'Manpower attrition is 0% in last quarter.', 'action' => 'Satisfactory.'],
            'L.1' => ['obtained' => 5.0, 'comment' => 'Application utilization is 85% in AIMS & Visitor App.', 'action' => 'Satisfactory.'],
            'M.1' => ['obtained' => 10.0, 'comment' => 'Loan file query resolution is 86%.', 'action' => 'Satisfactory.'],
        ];

        foreach ($scoresData as $code => $data) {
            $score = InspectionCheckpointScore::where('audit_inspection_id', $inspection->id)
                ->where('checkpoint_code', $code)
                ->first();

            if ($score) {
                $carry = (float)$score->carry_marks;
                $pct = $carry > 0 ? round(($data['obtained'] / $carry) * 100, 2) : 0;
                $score->update([
                    'marks_obtained' => $data['obtained'],
                    'percentage' => $pct,
                    'visitor_comment' => $data['comment'],
                    'immediate_action' => $data['action'],
                ]);
            }
        }

        $inspection->recalculateTotals();
    }

    public function bulkUpdateScores(AuditInspection $inspection, array $scores): void
    {
        foreach ($scores as $item) {
            $code = $item['checkpoint_code'] ?? null;
            if (!$code) continue;

            $this->updateManualScore(
                $inspection,
                $code,
                (float)($item['marks_obtained'] ?? 0),
                $item['visitor_comment'] ?? null,
                $item['immediate_action'] ?? null,
                $item['department'] ?? null
            );
        }
    }

    public function getSectionWiseSummary(AuditInspection $inspection): array
    {
        $scores = $inspection->scores()->get();
        $sectionsDef = [
            'A' => ['name' => 'Cash and Cheque Management Norms', 'group' => 'Process'],
            'B' => ['name' => 'Operational Aspects and Application Awareness', 'group' => 'Process'],
            'C' => ['name' => 'Customer Management', 'group' => 'Process'],
            'D' => ['name' => 'Display and Regulatory requirements', 'group' => 'Process'],
            'E' => ['name' => 'Document Management', 'group' => 'Process'],
            'F' => ['name' => 'Admin & House Keeping', 'group' => 'Process'],
            'G' => ['name' => 'Information Security', 'group' => 'Process'],
            'H' => ['name' => 'TAT Login to Disbursement (Average Of Last Quarter)', 'group' => 'Performance'],
            'I' => ['name' => 'Target Vs Achievement (Average Of Last Quarter)', 'group' => 'Performance'],
            'J' => ['name' => 'Branch NPA % (Average Of Last Quarter)', 'group' => 'Performance'],
            'K' => ['name' => 'Manpower Attrition (Average Of Last Quarter)', 'group' => 'Performance'],
            'L' => ['name' => 'Application Utilization (AIMS & Visitor App)', 'group' => 'Performance'],
            'M' => ['name' => 'Loan File Query Resolution (Average Of Last Quarter)', 'group' => 'Performance'],
        ];

        $sections = [];
        foreach ($sectionsDef as $secCode => $secMeta) {
            $secScores = $scores->where('section', $secCode);
            $secCarry = (float)$secScores->sum('carry_marks');
            $secObtained = (float)$secScores->sum('marks_obtained');
            $secPct = $secCarry > 0 ? round(($secObtained / $secCarry) * 100, 2) : 0;

            $sections[] = [
                'section' => $secCode,
                'section_code' => $secCode,
                'section_name' => $secMeta['name'],
                'category' => $secMeta['group'],
                'marks_carry' => $secCarry,
                'marks_obtained' => $secObtained,
                'percentage' => $secPct,
            ];
        }

        return [
            'sections' => $sections,
            'sub_total_i_process' => [
                'marks_carry' => (float)$inspection->process_marks_carry,
                'marks_obtained' => (float)$inspection->process_marks_obtained,
                'percentage' => (float)$inspection->process_score_pct,
            ],
            'sub_total_ii_performance' => [
                'marks_carry' => (float)$inspection->performance_marks_carry,
                'marks_obtained' => (float)$inspection->performance_marks_obtained,
                'percentage' => (float)$inspection->performance_score_pct,
            ],
            'grand_total' => [
                'marks_carry' => (float)$inspection->total_marks_carry,
                'marks_obtained' => (float)$inspection->total_marks_obtained,
                'percentage' => (float)$inspection->total_score_pct,
            ],
            'final_grade' => $inspection->final_grade,
        ];
    }
}
