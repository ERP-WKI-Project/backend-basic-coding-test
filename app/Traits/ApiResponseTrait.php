<?php

namespace App\Traits;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponseTrait
{
    public function successResponse($data, $message = 'Success', $code = 200)
    {
        if ($data instanceof ResourceCollection) {
            return $data->additional([
                'status' => 'success',
                'message' => $message,
            ]);
        }

        if ($data instanceof JsonResource) {
            return $data->additional([
                'status' => 'success',
                'message' => $message,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public function errorResponse($message, $code = 400)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $code);
    }
}
