<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    public function sendSuccess(string $message, mixed $data = null, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public function sendError(string|array $errors, int $statusCode = 400): JsonResponse
    {
        $errorList = is_array($errors) ? $errors : [$errors];

        return response()->json([
            'status' => 'error',
            'errors' => $errorList,
        ], $statusCode);
    }
}
