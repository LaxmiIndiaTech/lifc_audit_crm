<?php

namespace App\Console\Commands;

use App\Models\AuditInspection;
use App\Models\Branch;
use App\Models\CashChequeReceipt;
use App\Models\PersonalAccountTransaction;
use App\Services\AuditInspectionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestUserDataAuditCommand extends Command
{
    protected $signature = 'audit:test-user-data';
    protected $description = 'Load user sample data and verify audit scoring module for sections A through G';

    public function handle(AuditInspectionService $inspectionService): int
    {
        $this->info('Starting Audit Scoring verification with User Data...');

        $branch = Branch::where('branch_name', 'Kotputali')->first();
        if (!$branch) {
            $this->error('Branch Kotputali not found. Please run php artisan db:seed first.');
            return 1;
        }

        // 1. Seed Receipt Transfer Details (A.1 Sample: 119 total, 42 delayed above T+1)
        CashChequeReceipt::where('branch_id', $branch->id)->delete();

        $this->info('Loading 119 receipts (77 on-time, 42 delayed)...');
        // 77 on-time (T+1)
        for ($i = 1; $i <= 77; $i++) {
            CashChequeReceipt::create([
                'loan_no' => 'PL_ONTIME_' . $i,
                'customer_name' => 'Customer OnTime ' . $i,
                'branch_id' => $branch->id,
                'branch_name' => 'Kotputali',
                'created_date' => '2026-03-01',
                'payment_date' => '2026-03-01 10:00:00',
                'deposit_date' => '2026-03-02', // 1 day gap (T+1)
                'gap_in_days' => 1,
                'tat_status' => 'T+1',
                'is_over_tat' => false,
            ]);
        }

        // 42 delayed (T+2, T+3, T+5)
        for ($i = 1; $i <= 42; $i++) {
            $gap = ($i % 3 === 0) ? 5 : (($i % 2 === 0) ? 3 : 2);
            CashChequeReceipt::create([
                'loan_no' => 'PL_DELAYED_' . $i,
                'customer_name' => 'Customer Delayed ' . $i,
                'branch_id' => $branch->id,
                'branch_name' => 'Kotputali',
                'created_date' => '2026-03-01',
                'payment_date' => '2026-03-01 10:00:00',
                'deposit_date' => Carbon::parse('2026-03-01')->addDays($gap)->toDateString(),
                'gap_in_days' => $gap,
                'tat_status' => 'T+' . $gap,
                'is_over_tat' => true,
            ]);
        }

        // 2. Seed Personal Account Transactions (A.2: 15 transactions from user data)
        PersonalAccountTransaction::where('branch_id', $branch->id)->delete();
        $this->info('Loading 15 personal account transactions...');

        $personalTxns = [
            ['id' => '8D6CA88515E047C9825D49E955FB4CC5', 'date' => '2026-01-05', 'amt' => 1180, 'vpa' => '9352858389@ybl', 'mob' => '9352858389', 'code' => '6242', 'name' => 'Sandeep Kumar Yadav'],
            ['id' => '0EEC8CC649674ECEA443A1D8F8A68E6D', 'date' => '2026-01-05', 'amt' => 1180, 'vpa' => '7665373024@ptyes', 'mob' => '7665373024', 'code' => '6452', 'name' => 'Raghuveer Singh Gurjar'],
            ['id' => 'B52938EDC08C4BA8A471B275067F847F', 'date' => '2026-01-06', 'amt' => 1180, 'vpa' => '9352858389@ybl', 'mob' => '9352858389', 'code' => '6242', 'name' => 'Sandeep Kumar Yadav'],
            ['id' => '6854A8434E994D15BC9505413CDCF01B', 'date' => '2026-01-07', 'amt' => 1180, 'vpa' => '9602446928@ybl', 'mob' => '9602446928', 'code' => '6580', 'name' => 'Rambeer Yadav'],
            ['id' => '2D08D749BF5B49828D111229B961359F', 'date' => '2026-01-09', 'amt' => 1180, 'vpa' => '9352858389@ybl', 'mob' => '9352858389', 'code' => '6242', 'name' => 'Sandeep Kumar Yadav'],
            ['id' => 'CC3120E351F54C759FBA6605F2279F49', 'date' => '2026-01-15', 'amt' => 1180, 'vpa' => '9352858389@ybl', 'mob' => '9352858389', 'code' => '6242', 'name' => 'Sandeep Kumar Yadav'],
            ['id' => 'C7146AA649AD4ABDB51EC025EDCF8128', 'date' => '2026-02-02', 'amt' => 1180, 'vpa' => '9352858389@ybl', 'mob' => '9352858389', 'code' => '6242', 'name' => 'Sandeep Kumar Yadav'],
            ['id' => '7AAD0D0A0C2C4F34BFCF64D2A12BC293', 'date' => '2026-02-04', 'amt' => 1180, 'vpa' => '9352858389@ybl', 'mob' => '9352858389', 'code' => '6242', 'name' => 'Sandeep Kumar Yadav'],
            ['id' => 'D258EA0F4D38455393551F5B94AEFC22', 'date' => '2026-02-06', 'amt' => 1180, 'vpa' => '9602446928@ybl', 'mob' => '9602446928', 'code' => '6580', 'name' => 'Rambeer Yadav'],
            ['id' => '37BEC9924ED64612A264EEF4F1286518', 'date' => '2026-02-09', 'amt' => 1180, 'vpa' => '7665373024@ptyes', 'mob' => '7665373024', 'code' => '6452', 'name' => 'Raghuveer Singh Gurjar'],
            ['id' => 'E03297B3CCFF486D9F37AD4762E1BEF6', 'date' => '2026-02-09', 'amt' => 1180, 'vpa' => '9352858389@ybl', 'mob' => '9352858389', 'code' => '6242', 'name' => 'Sandeep Kumar Yadav'],
            ['id' => 'F1354434ED004B978C54DC5F496DC3D1', 'date' => '2026-02-11', 'amt' => 1180, 'vpa' => '9602446928@ybl', 'mob' => '9602446928', 'code' => '6580', 'name' => 'Rambeer Yadav'],
            ['id' => 'B294A7AD5D814FCF883F095939FFE40B', 'date' => '2026-02-12', 'amt' => 1180, 'vpa' => '9352858389@ybl', 'mob' => '9352858389', 'code' => '6242', 'name' => 'Sandeep Kumar Yadav'],
            ['id' => 'FA06D4835742430C8C39C1A9CF059B48', 'date' => '2026-02-19', 'amt' => 1180, 'vpa' => '7665373024@ptyes', 'mob' => '7665373024', 'code' => '6452', 'name' => 'Raghuveer Singh Gurjar'],
            ['id' => '85817645538C43F3B05BE624717F9CC5', 'date' => '2026-02-28', 'amt' => 885,  'vpa' => '8741989697@ybl', 'mob' => '8741989697', 'code' => '4988', 'name' => 'Ajay Kumar Jat'],
        ];

        foreach ($personalTxns as $t) {
            PersonalAccountTransaction::create([
                'merchant_name' => 'laxmiindia',
                'decentro_transaction_id' => $t['id'],
                'transaction_date' => $t['date'],
                'collection_type' => 'Dynamic QR',
                'transaction_amount' => $t['amt'],
                'payer_vpa' => $t['vpa'],
                'mobile_number' => $t['mob'],
                'employee_code' => $t['code'],
                'employee_name' => $t['name'],
                'branch_id' => $branch->id,
                'branch_name' => 'Kotputali',
                'department' => 'SALES',
                'quarter' => 'Q4',
                'financial_year' => '2025-2026',
                'is_violation' => true,
            ]);
        }

        // 3. Create or Fetch Audit Inspection
        $inspection = $inspectionService->getOrCreateInspection($branch->id, '2026-05-28', 'Q4', '2025-2026');

        // 4. Update Manual Checkpoints B through G from User's sheet
        $manualScores = [
            // Section B (Carry: 5, Obtain: 4)
            ['checkpoint_code' => 'B.1', 'marks_obtained' => 2.0, 'visitor_comment' => 'All ok', 'immediate_action' => null, 'department' => 'Ops'],
            ['checkpoint_code' => 'B.2', 'marks_obtained' => 0.0, 'visitor_comment' => 'No, Branch does not have updated product/credit policy in Physical for Readable for every Employee.', 'immediate_action' => null, 'department' => 'Ops'],
            ['checkpoint_code' => 'B.3', 'marks_obtained' => 1.0, 'visitor_comment' => 'Yes, the information regarding non-refundable charges has been pasted on branch.', 'immediate_action' => null, 'department' => 'Ops'],
            ['checkpoint_code' => 'B.4', 'marks_obtained' => 1.0, 'visitor_comment' => 'Yes, all employees were wearing ID cards.', 'immediate_action' => null, 'department' => 'Ops'],

            // Section C (Carry: 10, Obtain: 8)
            ['checkpoint_code' => 'C.1', 'marks_obtained' => 2.0, 'visitor_comment' => 'Customer Prem Chand (LAPKTP202601439) not given the information about upfront charges which is deducted from Disbursement.', 'immediate_action' => null, 'department' => 'Ops'],
            ['checkpoint_code' => 'C.2', 'marks_obtained' => 4.0, 'visitor_comment' => 'Customer compaints not pending at branch.', 'immediate_action' => null, 'department' => 'Ops'],
            ['checkpoint_code' => 'C.3', 'marks_obtained' => 2.0, 'visitor_comment' => 'No, we did not found Property Papers and NOC acknowledgement copies at branch.', 'immediate_action' => null, 'department' => 'Ops'],

            // Section D (Carry: 7, Obtain: 5)
            ['checkpoint_code' => 'D.1', 'marks_obtained' => 3.0, 'visitor_comment' => "Mandatory documents has been displayed on notice board in the branch.\n2. Grievance Details with E-mail ID / Customer care ID and Number - not pasted.\n5. Shop Act Certificate/GUMASTA - old address showing.", 'immediate_action' => null, 'department' => 'Admin'],
            ['checkpoint_code' => 'D.2', 'marks_obtained' => 1.0, 'visitor_comment' => "A. Yes, Display board is displayed at the branch entrance.\nB. Yes, the board of LIFC is displayed outside the office premises.", 'immediate_action' => null, 'department' => 'Admin'],
            ['checkpoint_code' => 'D.3', 'marks_obtained' => 1.0, 'visitor_comment' => 'Branch is situated at 2nd floor and direction signboard is not required as it clearly visible from main road.', 'immediate_action' => null, 'department' => 'Admin'],

            // Section E (Carry: 3, Obtain: 3)
            ['checkpoint_code' => 'E.1', 'marks_obtained' => 1.0, 'visitor_comment' => 'Yes, branch have maintain and update registers at branch.', 'immediate_action' => null, 'department' => 'Admin'],
            ['checkpoint_code' => 'E.2', 'marks_obtained' => 1.0, 'visitor_comment' => 'Yes, OPP and other critical documents are properly placed in the branch and verify that no old files or unauthorised stamps are lying.', 'immediate_action' => null, 'department' => 'Ops'],
            ['checkpoint_code' => 'E.3', 'marks_obtained' => 1.0, 'visitor_comment' => 'No, we did not found any Post Dated Cheques (PDC) taken without the name of Company.', 'immediate_action' => null, 'department' => 'Ops'],

            // Section F (Carry: 6, Obtain: 5)
            ['checkpoint_code' => 'F.1', 'marks_obtained' => 1.0, 'visitor_comment' => 'Yes, the latest copy of rent/lease agreement readily available with Landlord contact.', 'immediate_action' => null, 'department' => 'Admin'],
            ['checkpoint_code' => 'F.2', 'marks_obtained' => 1.0, 'visitor_comment' => 'No, we did not found branch employee with Tobacco, Cigarette, Chewing etc. at branch.', 'immediate_action' => null, 'department' => 'Admin'],
            ['checkpoint_code' => 'F.3', 'marks_obtained' => 1.0, 'visitor_comment' => 'Yes, branch is maintained neat and clean. We are paying Rs. 2500/- for housekeeping.', 'immediate_action' => null, 'department' => 'Admin'],
            ['checkpoint_code' => 'F.4', 'marks_obtained' => 1.0, 'visitor_comment' => 'Major Admin assets are not matched with HO Records/AIMS. 1 standee assigned but not available at branch.', 'immediate_action' => null, 'department' => 'Admin'],
            ['checkpoint_code' => 'F.5', 'marks_obtained' => 1.0, 'visitor_comment' => 'No assets scrapped yet.', 'immediate_action' => null, 'department' => 'Admin'],

            // Section G (Carry: 5, Obtain: 5)
            ['checkpoint_code' => 'G.1', 'marks_obtained' => 1.0, 'visitor_comment' => 'Yes, all USB Port (PAN Drive & USB cable etc.) is blocked.', 'immediate_action' => null, 'department' => 'IT'],
            ['checkpoint_code' => 'G.2', 'marks_obtained' => 1.0, 'visitor_comment' => 'No, we did not found anything unwanted/unethical data in Computer/Laptop.', 'immediate_action' => null, 'department' => 'IT'],
            ['checkpoint_code' => 'G.3', 'marks_obtained' => 1.0, 'visitor_comment' => 'Yes, Anti-virus is installed & Updated on all computer systems at branch.', 'immediate_action' => null, 'department' => 'IT'],
            ['checkpoint_code' => 'G.4', 'marks_obtained' => 2.0, 'visitor_comment' => 'Major IT Assets are matched with HO Records/AIMS.', 'immediate_action' => null, 'department' => 'IT'],
        ];

        $inspectionService->bulkUpdateScores($inspection, $manualScores);

        // 5. Fetch Summary and print out
        $summary = $inspectionService->getSectionWiseSummary($inspection->fresh());

        $this->newLine();
        $this->info('=== BRANCH INSPECTION REPORT CARD: MARKS OBTAINED SECTION WISE ===');
        $this->table(
            ['Section', 'Section Name', 'Marks Carry', 'Marks Obtained', '% Score'],
            array_map(function ($s) {
                return [
                    $s['section_code'],
                    $s['section_name'],
                    $s['marks_carry'],
                    $s['marks_obtained'],
                    $s['percentage'] . '%',
                ];
            }, $summary['sections'])
        );

        $sub = $summary['sub_total_i'];
        $this->info("Sub Total I (Process): Carry = {$sub['marks_carry']}, Obtained = {$sub['marks_obtained']}, Score = {$sub['percentage']}%");
        $this->info("Branch Final Grade: {$summary['final_grade']}");

        return 0;
    }
}
