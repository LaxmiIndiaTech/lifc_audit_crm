<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\FeedbackReply;
use App\Models\FeedbackThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackThreadsController extends ApiController
{
    public function index(Request $request, int $branchId): JsonResponse
    {
        $query = FeedbackThread::with(['feedbackReplies.employee'])
            ->where('branch_id', $branchId);

        if ($request->filled('month') && $request->input('month') !== 'all') {
            $query->where('inspection_month', $request->input('month'));
        }

        $total = (clone $query)->count();
        $withReplies = (clone $query)->whereHas('feedbackReplies')->count();
        $pending = $total - $withReplies;

        $page = (int)$request->input('page', 1);
        $perPage = (int)$request->input('per_page', 25);

        $threads = $query->latest()
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback threads fetched successfully',
            'data' => [
                'feedback_threads' => $threads,
                'stats' => [
                    'total' => $total,
                    'with_replies' => $withReplies,
                    'pending' => $pending,
                ],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => max(1, (int)ceil($total / $perPage)),
                    'total_count' => $total,
                    'per_page' => $perPage,
                ],
            ],
        ]);
    }

    public function create(Request $request, int $branchId): JsonResponse
    {
        $request->validate([
            'score_point_code' => 'required|string',
            'section' => 'required|string',
            'inspection_month' => 'required|string',
            'visitor_comment' => 'nullable|string',
            'immediate_action' => 'nullable|string',
        ]);

        $sectionText = mb_substr($request->input('section', 'General'), 0, 490);

        $thread = FeedbackThread::updateOrCreate(
            [
                'branch_id' => $branchId,
                'score_point_code' => $request->input('score_point_code'),
                'inspection_month' => $request->input('inspection_month'),
            ],
            [
                'section' => $sectionText,
                'visitor_comment' => $request->input('visitor_comment'),
                'immediate_action' => $request->input('immediate_action'),
            ]
        );

        // Update corresponding inspection checkpoint score and recalculate totals
        $checkpointScores = \App\Models\InspectionCheckpointScore::whereHas('inspection', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->where('checkpoint_code', $request->input('score_point_code'))->get();

        foreach ($checkpointScores as $s) {
            $updateData = [
                'visitor_comment' => $request->input('visitor_comment'),
                'immediate_action' => $request->input('immediate_action'),
            ];

            if ($request->has('marks_obtained')) {
                $marks = (float)$request->input('marks_obtained');
                $carry = (float)$s->carry_marks;
                $clamped = max(0.0, min($marks, $carry));
                $updateData['marks_obtained'] = $clamped;
                $updateData['percentage'] = $carry > 0 ? round(($clamped / $carry) * 100, 2) : 0;
            }

            $s->update($updateData);
            $s->inspection->recalculateTotals();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback thread created successfully',
            'data' => $thread,
        ], 201);
    }
}
