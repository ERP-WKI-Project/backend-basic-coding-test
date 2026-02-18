<?php

namespace App\Services;

use App\Services\Contracts\ServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

abstract class BaseService implements ServiceInterface
{
    protected string $modelClass;

    protected string $resourceName;

    public function all(array $filters = []): Collection|LengthAwarePaginator
    {
        $query = $this->modelClass::query();
        $query = $this->applyFilters($query, $filters);

        if (isset($filters['paginate']) && $filters['paginate'] === false) {
            return $query->get();
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function find(string $ulid): ?Model
    {
        return $this->modelClass::where('ulid', $ulid)->first();
    }

    public function create(array $data): Model
    {
        $model = $this->modelClass::create($data);
        $this->logActivity('Created new '.$this->resourceName, $model);

        return $model;
    }

    public function update(string $ulid, array $data): ?Model
    {
        $model = $this->find($ulid);

        if (! $model) {
            return null;
        }

        $model->update($data);
        $this->logActivity('Updated '.$this->resourceName, $model);

        return $model;
    }

    public function delete(string $ulid): bool
    {
        $model = $this->find($ulid);

        if (! $model) {
            return false;
        }

        $this->logActivity('Deleted '.$this->resourceName, $model);

        return $model->delete();
    }

    protected function applyFilters($query, array $filters)
    {
        return $query;
    }

    protected function logActivity(string $description, Model $model): void
    {
        $user = Auth::user();

        activity('database')
            ->performedOn($model)
            ->causedBy($user)
            ->log($description);
    }
}
