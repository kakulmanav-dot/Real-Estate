<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $databaseOk = true;

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $databaseOk = false;
        }

        return response()->json([
            'success' => $databaseOk,
            'message' => $databaseOk ? 'Application is healthy.' : 'Application is degraded.',
            'data' => [
                'status' => $databaseOk ? 'ok' : 'degraded',
                'timestamp' => now()->toIso8601String(),
            ],
        ], $databaseOk ? 200 : 503);
    }
}
