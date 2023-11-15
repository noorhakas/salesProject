<?php

namespace App\Http\Traits;

use App\Http\Observers\GlobalObserver;

trait ObservantTrait
{
    public static function bootObservantTrait()
    {
        static::observe(new GlobalObserver);
    }
}

?>