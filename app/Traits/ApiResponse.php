<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

trait ApiResponse
{
    protected function resourceResponse(JsonResource $resource, string $message = '', int $code = 200): JsonResponse
    {
        return $resource
            ->additional([
                'success' => true,
                'message' => $message,
                'errors' => null,
            ])
            ->response()
            ->setStatusCode($code);
    }

    protected function collectionResponse(AnonymousResourceCollection $collection, string $message = ''): JsonResponse
    {
        return $collection
            ->additional([
                'success' => true,
                'message' => $message,
                'errors' => null,
            ])
            ->response();
    }

    protected function successResponse(mixed $data = null, string $message = '', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $code);
    }

    protected function errorResponse(string $message = '', int $code = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $code);
    }
}
