<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasUlidColumn
{
    /**
     * Boot the ULID column trait for a model.
     *
     * @return void
     */
    public static function bootHasUlidColumn()
    {
        static::creating(function ($model) {
            if (is_null($model->{$model->getUlidColumn()})) {
                $model->{$model->getUlidColumn()} = Str::ulid();
            }
        });
    }

    public function initializeHasUlidColumn()
    {
        if (! in_array('id', $this->hidden)) {
            $this->hidden[] = 'id';
        }
    }

    public function getUlidColumn(): string
    {
        return defined(static::class.'::ULID_COLUMN') ? static::ULID_COLUMN : 'ulid';
    }
}
