<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitiesController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = City::with('state')->where('is_deleted', false);
        if ($request->filled('state_id')) {
            $query->where('state_id', $request->input('state_id'));
        }

        $cities = $query->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Cities fetched successfully',
            'data' => [
                'cities' => $cities,
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $city = City::with('state')->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'message' => 'City fetched successfully',
            'data' => $city,
        ]);
    }
}
