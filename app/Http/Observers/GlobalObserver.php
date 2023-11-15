<?php

namespace App\Http\Observers;

use App\Models\SiteLog;
use Illuminate\Database\Eloquent\Model;


class GlobalObserver
{
    public function saved(Model $model)
    {
        $dirty = $model->getDirty();
        $original = $model->getOriginal();
        $message = $original == [] ? 'Created ' : 'Updated ';
        if($original != []){
            $originalFiltered = [];
            foreach(array_keys($dirty) as $key){
                $originalFiltered[$key] =  $original[$key];
            }   
            $original =  $originalFiltered;
        }
        (new SiteLog())->reportDatabase([
            'get_class'     => get_class($model), 
            'id'            => $model->id,         
            'old_value'     => $original,
            'new_value'     => $dirty,], $message . get_class($model));

    }

    public function deleted(Model $model)
    {
        (new SiteLog())->reportDatabase([         
            'get_class'     => get_class($model),
            'id'            => $model->id,         
            'old_value'     => $model,
            'new_value'     => null,], 'Deleted '. get_class($model));
    }

}
