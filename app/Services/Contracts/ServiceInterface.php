<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface ServiceInterface
{
    public function all(array $filters = []): Collection|LengthAwarePaginator;

    public function find(string $ulid): ?Model;

    public function create(array $data): Model;

    public function update(string $ulid, array $data): ?Model;

    public function delete(string $ulid): bool;
}
