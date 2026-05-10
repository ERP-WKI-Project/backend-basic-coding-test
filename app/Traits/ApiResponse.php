<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        $response = [];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    protected function paginated(mixed $data, LengthAwarePaginator $paginator, ?string $message = null): JsonResponse
    {
        $response = [];

        if ($message) {
            $response['message'] = $message;
        }

        $response['data'] = $data;
        $response['links'] = $paginator->linkCollection()->toArray();
        $response['meta'] = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];

        return response()->json($response);
    }

    protected function error(string $message, int $code = 400, mixed $errors = null): JsonResponse
    {
        $response = ['message' => $message];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function created(mixed $data = null, string $message = 'Created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function noContent(string $message = 'No content'): JsonResponse
    {
        return $this->success(message: $message, code: 204);
    }

    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function validationError(mixed $errors): JsonResponse
    {
        return $this->error('Validation failed', 422, $errors);
    }
}
