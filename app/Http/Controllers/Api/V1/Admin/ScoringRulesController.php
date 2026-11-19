<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\AuditInspection;
use App\Models\LoanTatRecord;
use App\Models\ScoringRule;
use App\Services\AuditInspectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScoringRulesController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int)$request->query('per_page', 50);
        $ruleType = $request->query('rule_type', 'cash_cheque');

        $rules = ScoringRule::active()
            ->where('rule_type', $ruleType)
            ->orderBy('min_days', 'asc')
            ->orderBy('max_days', 'asc')
            ->paginate($perPage);

        return $this->sendSuccess('Scoring rules fetched successfully', [
            'scoring_rules' => $rules->items(),
            'rule_type' => $ruleType,
            'pagination' => [
                'current_page' => $rules->currentPage(),
                'total_pages' => $rules->lastPage(),
                'total_count' => $rules->total(),
                'per_page' => $rules->perPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $rule = ScoringRule::find($id);
        if (!$rule) {
            return $this->sendError('Rule not found', 404);
        }

        return $this->sendSuccess('Scoring rule fetched successfully', $rule);
    }

    public function store(Request $request): JsonResponse
    {
        $ruleType = $request->input('rule_type', 'cash_cheque');
        $maxScoreAllowed = ($ruleType === 'tat_disbursement') ? 100 : 10;

        $validator = Validator::make($request->all(), [
            'rule_type' => 'nullable|string|in:cash_cheque,tat_disbursement',
            'min_days' => 'required|integer|min:0',
            'max_days' => 'required|integer|gte:min_days',
            'score' => "required|integer|between:0,{$maxScoreAllowed}",
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->all(), 422);
        }

        $min = (int)$request->input('min_days');
        $max = (int)$request->input('max_days');

        if (ScoringRule::checkOverlap($min, $max, null, $ruleType)) {
            return $this->sendError("Range overlaps with an existing active rule in {$ruleType}", 422);
        }

        $rule = ScoringRule::create([
            'rule_type' => $ruleType,
            'min_days' => $min,
            'max_days' => $max,
            'score' => $request->input('score'),
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active', true),
            'is_deleted' => false,
        ]);

        return $this->sendSuccess('Scoring rule created successfully', $rule, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rule = ScoringRule::find($id);
        if (!$rule) {
            return $this->sendError('Rule not found', 404);
        }

        $ruleType = $rule->rule_type ?: 'cash_cheque';
        $maxScoreAllowed = ($ruleType === 'tat_disbursement') ? 100 : 10;

        $validator = Validator::make($request->all(), [
            'min_days' => 'sometimes|integer|min:0',
            'max_days' => 'sometimes|integer',
            'score' => "sometimes|integer|between:0,{$maxScoreAllowed}",
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->all(), 422);
        }

        $min = (int)$request->input('min_days', $rule->min_days);
        $max = (int)$request->input('max_days', $rule->max_days);

        if ($min > $max) {
            return $this->sendError('min_days cannot be greater than max_days', 422);
        }

        if (ScoringRule::checkOverlap($min, $max, $rule->id, $ruleType)) {
            return $this->sendError("Range overlaps with an existing active rule in {$ruleType}", 422);
        }

        $rule->update($request->only(['min_days', 'max_days', 'score', 'description', 'is_active']));

        return $this->sendSuccess('Scoring rule updated successfully', $rule);
    }

    /**
     * Bulk Sync / Replace rules for a specific rule_type.
     */
    public function sync(Request $request): JsonResponse
    {
        $payload = $request->input('rules', []);
        $replace = $request->boolean('replace', false);
        $ruleType = $request->input('rule_type', $request->query('rule_type', 'cash_cheque'));
        $maxScoreAllowed = ($ruleType === 'tat_disbursement') ? 100 : 10;

        if (empty($payload) || !is_array($payload)) {
            return $this->sendError('Rules array is required', 422);
        }

        // Validate payload constraints and internal overlap
        $ranges = [];
        $errors = [];

        foreach ($payload as $idx => $item) {
            $rowNum = $idx + 1;
            $min = isset($item['min_days']) ? (int)$item['min_days'] : null;
            $max = isset($item['max_days']) ? (int)$item['max_days'] : null;
            $score = isset($item['score']) ? (int)$item['score'] : null;

            if ($min === null || $max === null || $min < 0 || $max < 0) {
                $errors[] = "Row {$rowNum}: min_days and max_days must be non-negative integers";
                continue;
            }
            if ($min > $max) {
                $errors[] = "Row {$rowNum}: min_days cannot be greater than max_days";
            }
            if ($score === null || $score < 0 || $score > $maxScoreAllowed) {
                $errors[] = "Row {$rowNum}: score must be between 0 and {$maxScoreAllowed}";
            }

            $ranges[] = ['min' => $min, 'max' => $max, 'row' => $rowNum, 'id' => $item['id'] ?? null];
        }

        // Check internal overlaps
        usort($ranges, fn($a, $b) => $a['min'] <=> $b['min'] ?: $a['max'] <=> $b['max']);
        for ($i = 0; $i < count($ranges) - 1; $i++) {
            if ($ranges[$i + 1]['min'] <= $ranges[$i]['max']) {
                $errors[] = "Rows {$ranges[$i]['row']} and {$ranges[$i + 1]['row']} overlap";
            }
        }

        if (!empty($errors)) {
            return $this->sendError($errors, 422);
        }

        $syncedIds = [];
        DB::beginTransaction();
        try {
            foreach ($payload as $item) {
                $rule = !empty($item['id']) ? ScoringRule::find($item['id']) : new ScoringRule();
                if (!$rule) {
                    throw new \Exception("Rule with id={$item['id']} not found");
                }

                $rule->rule_type = $ruleType;
                $rule->min_days = (int)$item['min_days'];
                $rule->max_days = (int)$item['max_days'];
                $rule->description = $item['description'] ?? null;
                $rule->score = (int)$item['score'];
                $rule->is_active = isset($item['is_active']) ? (bool)$item['is_active'] : true;
                $rule->is_deleted = false;
                $rule->save();

                $syncedIds[] = $rule->id;
            }

            if ($replace) {
                ScoringRule::where('rule_type', $ruleType)
                    ->whereNotIn('id', $syncedIds)
                    ->update([
                        'is_deleted' => true,
                        'is_active' => false,
                    ]);
            }

            DB::commit();

            // If TAT rules changed, re-evaluate H.1 for branches with TAT records
            if ($ruleType === 'tat_disbursement') {
                $branchIds = LoanTatRecord::distinct()->pluck('branch_id')->filter();
                $inspService = app(AuditInspectionService::class);
                foreach ($branchIds as $bId) {
                    $latestInsp = AuditInspection::where('branch_id', $bId)->latest('inspection_date')->first();
                    if ($latestInsp) {
                        $inspService->evaluateAutoCheckpoints($latestInsp);
                        $latestInsp->recalculateTotals();
                    }
                }
            }

            $allRules = ScoringRule::active()
                ->where('rule_type', $ruleType)
                ->orderBy('min_days', 'asc')
                ->orderBy('max_days', 'asc')
                ->get();

            return $this->sendSuccess('Scoring rules synced successfully', [
                'scoring_rules' => $allRules,
                'rule_type' => $ruleType,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), 422);
        }
    }
}
