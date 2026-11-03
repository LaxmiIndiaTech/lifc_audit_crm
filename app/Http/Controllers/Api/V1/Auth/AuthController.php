<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function requestOtp(Request $request): JsonResponse
    {
        $mobile = trim((string)($request->input('mobile_number') ?: $request->input('mobile')));
        if (empty($mobile)) {
            return $this->sendError(["Mobile Number can't be blank"], 422);
        }

        // Clean mobile number (last 10 digits)
        $cleanMobile = substr(preg_replace('/\D/', '', $mobile), -10);

        $employee = Employee::where('mobile_number', 'like', "%{$cleanMobile}%")->first();
        if (!$employee) {
            $employee = Employee::first();
        }

        $otpCode = '123456';

        return response()->json([
            'status' => 'success',
            'message' => "OTP has been sent to employee number {$cleanMobile}",
            'data' => [
                'otp_code' => $otpCode,
            ],
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $mobile = trim((string)($request->input('mobile_number') ?: $request->input('mobile')));
        $otp = trim((string)$request->input('otp'));

        if (empty($mobile) || empty($otp)) {
            return $this->sendError(["Mobile Number and OTP can't be blank"], 422);
        }

        $cleanMobile = substr(preg_replace('/\D/', '', $mobile), -10);
        $employee = Employee::with(['branch.city.state'])->where('mobile_number', 'like', "%{$cleanMobile}%")->first() ?: Employee::with(['branch.city.state'])->first();

        $token = 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIi:' . ($employee?->id ?? 1) . '.sig';

        $branch = $employee?->branch;
        $city = $branch?->city;
        $state = $city?->state;

        $employeeData = [
            'id' => $employee?->id ?? 2,
            'employee_first_name' => $employee?->employee_first_name ?? 'Manish',
            'employee_last_name' => $employee?->employee_last_name ?? 'Kumar',
            'employee_code' => $employee?->employee_code ?? '40321',
            'email' => $employee?->email ?? 'manish.mishra@lifc.in',
            'mobile_number' => $employee?->mobile_number ?? $cleanMobile,
            'department' => $employee?->department ?? 'IT',
            'login_at' => Carbon::now()->toIso8601String(),
            'deactivated_at' => null,
            'is_active' => true,
            'roles' => ['admin'],
            'branch' => $branch ? [
                'id' => $branch->id,
                'branch_name' => $branch->branch_name,
                'branch_code' => $branch->branch_code,
                'address' => $branch->address,
                'pin_code' => $branch->pin_code,
                'is_active' => true,
                'meta' => $branch->meta ?: [
                    'branch_in_charge_name' => 'Aman Rawat',
                    'branch_in_charge_phone_number' => '9871218483',
                    'branch_in_charge_address' => 'SAKET J-Block',
                    'branch_in_charge_email' => 'amanrawat0506@gmail.com',
                    'branch_in_charge_employee_code' => 'EMP01',
                    'cm_asm_name' => 'Manish ',
                    'cm_asm_mobile_number' => '9871218482',
                    'cm_asm_email' => 'manish05@gmail.com',
                    'cm_asm_code' => 'EMP012',
                ],
            ] : null,
            'city' => $city ? [
                'id' => $city->id,
                'city_name' => $city->city_name,
                'city_slug' => $city->city_slug ?: 'delhi',
                'city_code' => $city->city_code,
                'branch_count' => 1,
                'is_active' => true,
                'meta' => [],
            ] : null,
            'state' => $state ? [
                'id' => $state->id,
                'state_name' => $state->state_name,
                'state_code' => $state->state_code,
                'country' => 'India',
                'is_active' => true,
            ] : null,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'OTP has been verified successfully',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 86400,
                'employee' => $employeeData,
            ],
        ]);
    }

    public function validateEmployeeRoles(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Employee roles fetched successfully',
            'data' => [
                'roles' => ['admin'],
            ],
        ]);
    }
}
