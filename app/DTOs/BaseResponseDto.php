<?php

namespace App\DTOs;

readonly class BaseResponseDto
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public bool $status,
        public string $message,
        public mixed $data = null,
        public array $errors = [],
    ) {
        //
    }

    /**
     * Create success response
     */
    public static function success(string $message, mixed $data = null): self
    {
        return new self(
            status: true,
            message: $message,
            data: $data,
            errors: []
        );
    }

    /**
     * Create failure response
     */
    public static function failure(string $message, array $errors = [], mixed $data = null): self
    {
        return new self(
            status: false,
            message: $message,
            data: $data,
            errors: $errors
        );
    }

    /**
     * Check if response is success
     */
    public function isSuccess(): bool
    {
        return $this->status === true;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        $response = [
            'status' => $this->status,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        return $response;
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
