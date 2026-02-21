<?php

namespace SmartGeomatics\DeferredBatch;

use Illuminate\Bus\Dispatcher;
use Illuminate\Support\ServiceProvider;

class DeferredBatchServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Dispatcher::macro('deferredBatch', function (callable $builder) {
            return new DeferredBatch($builder);
        });
    }
}
