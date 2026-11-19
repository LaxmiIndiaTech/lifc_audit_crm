<?php

namespace Database\Seeders;

use App\Models\ScoringRule;
use Illuminate\Database\Seeder;

class ScoringRulesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Default rules for cash & cheque deposit delays (Section A.1)
        $cashRules = [
            [
                'rule_type' => 'cash_cheque',
                'min_days' => 0,
                'max_days' => 1,
                'score' => 10,
                'description' => 'T+0 or T+1 (Deposited on time)',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'rule_type' => 'cash_cheque',
                'min_days' => 2,
                'max_days' => 2,
                'score' => 7,
                'description' => 'T+2 (1 day delay)',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'rule_type' => 'cash_cheque',
                'min_days' => 3,
                'max_days' => 3,
                'score' => 4,
                'description' => 'T+3 (2 days delay)',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'rule_type' => 'cash_cheque',
                'min_days' => 4,
                'max_days' => 4,
                'score' => 2,
                'description' => 'T+4 (3 days delay)',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'rule_type' => 'cash_cheque',
                'min_days' => 5,
                'max_days' => 9999,
                'score' => 0,
                'description' => 'T+5 or higher (Severe delay)',
                'is_active' => true,
                'is_deleted' => false,
            ],
        ];

        foreach ($cashRules as $r) {
            ScoringRule::updateOrCreate(
                ['rule_type' => 'cash_cheque', 'min_days' => $r['min_days'], 'max_days' => $r['max_days']],
                $r
            );
        }

        // 2. Default rules for TAT Login to Disbursement (Section H.1 - 5 Marks Carry)
        $tatRules = [
            [
                'rule_type' => 'tat_disbursement',
                'min_days' => 0,
                'max_days' => 10,
                'score' => 100,
                'description' => '0-10 Days (100% Marks - 5M Carry)',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'rule_type' => 'tat_disbursement',
                'min_days' => 11,
                'max_days' => 20,
                'score' => 75,
                'description' => '11-20 Days (75% Marks - 3.75M Carry)',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'rule_type' => 'tat_disbursement',
                'min_days' => 21,
                'max_days' => 30,
                'score' => 50,
                'description' => '21-30 Days (50% Marks - 2.50M Carry)',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'rule_type' => 'tat_disbursement',
                'min_days' => 31,
                'max_days' => 9999,
                'score' => 20,
                'description' => '31 and above Days (20% Marks - 1.00M Carry)',
                'is_active' => true,
                'is_deleted' => false,
            ],
        ];

        foreach ($tatRules as $r) {
            ScoringRule::updateOrCreate(
                ['rule_type' => 'tat_disbursement', 'min_days' => $r['min_days'], 'max_days' => $r['max_days']],
                $r
            );
        }
    }
}
