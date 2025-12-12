<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Elasticsearch\ClientBuilder;
use App\Services\ElasticsearchEngine;

class ElasticsearchScoutServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Elasticsearch engine
        app(EngineManager::class)->extend('elasticsearch', function ($app) {
            $config = config('scout.elasticsearch');

            $client = ClientBuilder::create()
                ->setHosts($config['hosts'])
                ->setSSLVerification($config['ssl_verification'])
                ->build();

            return new ElasticsearchEngine($client, $config['index']);
        });
    }
}
