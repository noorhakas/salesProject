<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;



class InterfaceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(\App\Repository\Interfaces\ClassInterFace::class,\App\Repository\Eloquent\ClassRepository::class);
		$this->app->bind(\App\Repository\Interfaces\BrickInterface::class,\App\Repository\Eloquent\BrickRepository::class);
		$this->app->bind(\App\Repository\Interfaces\AccTypeInterface::class,\App\Repository\Eloquent\AccTypeRepository::class);
		$this->app->bind(\App\Repository\Interfaces\SpecialtyInterface::class,\App\Repository\Eloquent\SpecialtyRepository::class);
		$this->app->bind(\App\Repository\Interfaces\ProductInterface::class,\App\Repository\Eloquent\ProductRepository::class);
		$this->app->bind(\App\Repository\Interfaces\CustomerInterface::class,\App\Repository\Eloquent\CustomerRepository::class);
		$this->app->bind(\App\Repository\Interfaces\PlanInterface::class,\App\Repository\Eloquent\PlanRepository::class);
		$this->app->bind(\App\Repository\Interfaces\VisitInterface::class,\App\Repository\Eloquent\VisitRepository::class);


    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
