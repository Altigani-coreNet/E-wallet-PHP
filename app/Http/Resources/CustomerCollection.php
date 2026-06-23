<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => CustomerResource::collection($this->collection),
        ];
    }

    public function with(Request $request): array
    {
        // Preserve paginator meta/links if present
        if (method_exists($this->resource, 'toArray')) {
            return [];
        }
        return [];
    }
}


