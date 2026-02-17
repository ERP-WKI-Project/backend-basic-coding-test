<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ApiHealthController extends Controller
{
    /**
     * API Health Check
     *
     * Check the health status of the API, database, and Redis connections.
     *
     * @tag Health Check
     * @unauthenticated
     */
    public function __invoke(Request $request)
    {
        $status = 200;

        $response = [
            'time' => (string) Carbon::now(),
            'db' => false,
            'redis' => false,
        ];

        try {
            DB::connection()->getPdo();
            $response['db'] = true;
        } catch (\Exception $e) {
            $status = 503;
        }

        try {
            Redis::connection()->client()->ping();
            $response['redis'] = true;
        } catch (\Exception $e) {
            $status = 503;
        }

        $response['status'] = $status === 200 ? 'ok' : 'degraded';

        return response()->json(['message' => $response], $status);
    }
}
