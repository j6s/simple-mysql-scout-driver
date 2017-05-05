<?php
namespace J6s\SimpleMysqlScoutDriver\Providers;
use Illuminate\Support\ServiceProvider;
use J6s\SimpleMysqlScoutDriver\Engine\SimpleMysqlEngine;
use Laravel\Scout\EngineManager;

class SimpleMysqlServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../migrations');

        resolve(EngineManager::class)->extend('simple_mysql', function() {
            return new SimpleMysqlEngine();
        });
    }

}