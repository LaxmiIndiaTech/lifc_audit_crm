<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatesController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $states = State::where('is_deleted', false)->get();

        return response()->json([
            'status' => 'success',
            'message' => 'States fetched successfully',
            'data' => [
                'states' => $states,
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $state = State::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'message' => 'State fetched successfully',
            'data' => $state,
        ]);
    }
}
