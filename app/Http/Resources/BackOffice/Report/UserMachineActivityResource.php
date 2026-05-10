<?php

namespace App\Http\Resources\BackOffice\Report;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserMachineActivityResource extends ResourceCollection
{
    public $collects = 'array';

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
