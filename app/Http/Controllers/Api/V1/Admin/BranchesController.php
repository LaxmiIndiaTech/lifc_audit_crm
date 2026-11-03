<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchesController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Branch::with(['city.state'])->where('is_deleted', false);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('branch_name', 'like', "%{$q}%")
                    ->orWhere('branch_code', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $page = max(1, (int)$request->input('page', 1));
        $perPage = max(1, (int)$request->input('per_page', 25));

        $totalCount = $query->count();
        $totalPages = (int)ceil($totalCount / $perPage);

        $branches = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $formatted = $branches->map(function ($b) {
            return [
                'id' => $b->id,
                'branch_name' => $b->branch_name,
                'branch_code' => $b->branch_code,
                'address' => $b->address,
                'pin_code' => $b->pin_code,
                'is_active' => (bool)$b->is_active,
                'city' => $b->city ? [
                    'id' => $b->city->id,
                    'city_name' => $b->city->city_name,
                    'city_code' => $b->city->city_code,
                ] : null,
                'state' => $b->city?->state ? [
                    'id' => $b->city->state->id,
                    'state_name' => $b->city->state->state_name,
                    'state_code' => $b->city->state->state_code,
                ] : null,
                'meta' => $b->meta ?? [],
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Branches fetched successfully',
            'data' => [
                'branches' => $formatted,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => $totalPages,
                    'total_count' => $totalCount,
                    'next_page' => $page < $totalPages ? $page + 1 : null,
                    'prev_page' => $page > 1 ? $page - 1 : null,
                ],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $b = Branch::with(['city.state'])->find($id);
        if (!$b) {
            return $this->sendError('Branch not found', 404);
        }

        $formatted = [
            'id' => $b->id,
            'branch_name' => $b->branch_name,
            'branch_code' => $b->branch_code,
            'address' => $b->address,
            'pin_code' => $b->pin_code,
            'is_active' => (bool)$b->is_active,
            'city' => $b->city ? [
                'id' => $b->city->id,
                'city_name' => $b->city->city_name,
                'city_code' => $b->city->city_code,
            ] : null,
            'state' => $b->city?->state ? [
                'id' => $b->city->state->id,
                'state_name' => $b->city->state->state_name,
                'state_code' => $b->city->state->state_code,
            ] : null,
            'meta' => $b->meta ?? [],
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Branch fetched successfully',
            'data' => $formatted,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $branch = Branch::create($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Branch created successfully',
            'data' => $branch,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $b = Branch::findOrFail($id);
        $b->update($request->all());
        return response()->json([
            'status' => 'success',
            'message' => 'Branch updated successfully',
            'data' => $b,
        ]);
    }
}
