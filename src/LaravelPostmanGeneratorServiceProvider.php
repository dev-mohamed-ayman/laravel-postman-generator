<?php

namespace MohamedAyman\LaravelPostmanGenerator;

use Illuminate\Support\ServiceProvider;
use MohamedAyman\LaravelPostmanGenerator\Commands\GeneratePostmanCollectionCommand;

class LaravelPostmanGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/postman-generator.php',
            'postman-generator'
        );

        $this->app->singleton(PostmanGenerator::class, function ($app) {
            return new PostmanGenerator(
                $app['router'],
                $app['request']
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/postman-generator.php' => config_path('postman-generator.php'),
            ], 'postman-generator-config');

            $this->commands([
                GeneratePostmanCollectionCommand::class,
            ]);
        }
    }
}
