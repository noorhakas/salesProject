<?php

namespace App\Http\Resources\API\Concerns;

trait FormatsIdName
{
    
    protected function idName($model): ?array
    {
        if (! $model) {
            return null;
        }

        return [
            'id'   => $model->id,
            'name' => $model->name,
        ];
    }
}