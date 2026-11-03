<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeesController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Employee::with(['branch.city.state'])->where('is_deleted', false);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('employee_first_name', 'like', "%{$q}%")
                    ->orWhere('employee_last_name', 'like', "%{$q}%")
                    ->orWhere('employee_code', 'like', "%{$q}%")
                    ->orWhere('mobile_number', 'like', "%{$q}%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        $page = max(1, (int)$request->input('page', 1));
        $perPage = max(1, (int)$request->input('per_page', 25));

        $totalCount = $query->count();
        $totalPages = (int)ceil($totalCount / $perPage);

        $employees = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        $formatted = $employees->map(function ($e) {
            return [
                'id' => $e->id,
                'employee_first_name' => $e->employee_first_name,
                'employee_last_name' => $e->employee_last_name,
                'employee_code' => $e->employee_code,
                'email' => $e->email,
                'mobile_number' => $e->mobile_number,
                'department' => $e->department,
                'designation' => $e->designation,
                'is_active' => (bool)$e->is_active,
                'roles' => ['employee'],
                'branch' => $e->branch ? [
                    'id' => $e->branch->id,
                    'branch_name' => $e->branch->branch_name,
                    'branch_code' => $e->branch->branch_code,
                ] : null,
                'city' => $e->branch?->city ? [
                    'id' => $e->branch->city->id,
                    'city_name' => $e->branch->city->city_name,
                ] : null,
                'state' => $e->branch?->city?->state ? [
                    'id' => $e->branch->city->state->id,
                    'state_name' => $e->branch->city->state->state_name,
                ] : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Employees fetched successfully',
            'data' => [
                'employees' => $formatted,
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
        $e = Employee::with(['branch.city.state'])->findOrFail($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Employee fetched successfully',
            'data' => $e,
        ]);
    }

    public function addEmployeeRole(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Role updated successfully',
            'data' => [],
        ]);
    }
}
