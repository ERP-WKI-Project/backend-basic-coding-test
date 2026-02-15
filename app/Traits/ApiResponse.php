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

    protected function deletedResponse(string $message = ''): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => null,
            'errors' => null,
        ]);
    }
}
