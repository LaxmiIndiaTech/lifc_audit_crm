<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Employee;
use App\Models\FeedbackReply;
use App\Models\FeedbackThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackRepliesController extends ApiController
{
    public function index(int $threadId): JsonResponse
    {
        $replies = FeedbackReply::with('employee')
            ->where('feedback_thread_id', $threadId)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback replies fetched successfully',
            'data' => [
                'feedback_replies' => $replies,
            ],
        ]);
    }

    public function create(Request $request, int $threadId): JsonResponse
    {
        $request->validate(['text' => 'required|string']);

        $thread = FeedbackThread::findOrFail($threadId);
        $emp = Employee::first();

        $reply = FeedbackReply::create([
            'feedback_thread_id' => $thread->id,
            'employee_id' => $emp?->id,
            'author_type' => 'admin',
            'text' => $request->input('text'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback reply created successfully',
            'data' => $reply->load('employee'),
        ], 201);
    }
}
