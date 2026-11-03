<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\City;
use App\Models\State;
use Database\Seeders\AuditCheckpointsSeeder;
use Database\Seeders\SampleDataSeeder;
use Database\Seeders\ScoringRulesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditScoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            AuditCheckpointsSeeder::class,
            ScoringRulesSeeder::class,
            SampleDataSeeder::class,
        ]);
    }

    public function test_dashboard_stats_endpoint(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => [
                    'branches',
                    'states',
                    'cities',
                    'employees',
                    'receipts',
                    'personal_violations',
                ],
            ]);
    }

    public function test_scoring_rules_listing_and_sync(): void
    {
        $res = $this->getJson('/api/v1/admin/scoring_rules');
        $res->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // Test sync endpoint
        $payload = [
            'replace' => false,
            'rules' => [
                ['min_days' => 0, 'max_days' => 1, 'score' => 10, 'description' => 'T+1 On time'],
                ['min_days' => 2, 'max_days' => 3, 'score' => 5, 'description' => 'T+2 to T+3'],
            ],
        ];

        $syncRes = $this->postJson('/api/v1/admin/scoring_rules/sync', $payload);
        $syncRes->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    public function test_branch_inspection_and_manual_checkpoint_update(): void
    {
        $branch = Branch::where('branch_name', 'Kotputali')->first();
        $this->assertNotNull($branch);

        // Fetch inspection
        $res = $this->getJson("/api/v1/admin/branch_inspection/{$branch->id}");
        $res->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.branch_info.branch_name', 'Kotputali');

        $inspectionId = $res->json('data.inspection_id');
        $this->assertNotEmpty($inspectionId);

        // Update manual scores B through G
        $updatePayload = [
            'scores' => [
                ['checkpoint_code' => 'B.1', 'marks_obtained' => 2.0, 'visitor_comment' => 'All ok', 'department' => 'Ops'],
                ['checkpoint_code' => 'B.2', 'marks_obtained' => 0.0, 'visitor_comment' => 'No policy copy', 'department' => 'Ops'],
                ['checkpoint_code' => 'C.1', 'marks_obtained' => 2.0, 'visitor_comment' => 'Upfront charges note missing', 'department' => 'Ops'],
                ['checkpoint_code' => 'G.1', 'marks_obtained' => 1.0, 'visitor_comment' => 'USB blocked', 'department' => 'IT'],
            ],
        ];

        $updateRes = $this->postJson("/api/v1/admin/inspections/{$inspectionId}/update_scores", $updatePayload);
        $updateRes->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }
}
