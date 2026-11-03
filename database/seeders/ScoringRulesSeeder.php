<?php

namespace Database\Seeders;

use App\Models\ScoringRule;
use Illuminate\Database\Seeder;

class ScoringRulesSeeder extends Seeder
{
    public function run(): void
    {
        // Default rules for deposit delays (days gap between payment date & deposit date)
        $rules = [
            [
                'min_days' => 0,
                'max_days' => 1,
                'score' => 10,
                'description' => 'T+0 or T+1 (Deposited on time)',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'min_days' => 2,
                'max_days' => 2,
                'score' => 7,
                'description' => 'T+2 (1 day delay)',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'min_days' => 3,
                'max_days' => 3,
                'score' => 4,
                'description' => 'T+3 (2 days delay)',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'min_days' => 4,
                'max_days' => 4,
                'score' => 2,
                'description' => 'T+4 (3 days delay)',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'min_days' => 5,
                'max_days' => 9999,
                'score' => 0,
                'description' => 'T+5 or higher (Severe delay)',
                'is_active' => true,
                'is_deleted' => false,
            ],
        ];

        foreach ($rules as $r) {
            ScoringRule::updateOrCreate(
                ['min_days' => $r['min_days'], 'max_days' => $r['max_days']],
                $r
            );
        }
    }
}
