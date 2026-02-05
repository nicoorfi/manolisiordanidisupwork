<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Sigmie\Sigmie;
use Sigmie\Enums\SearchEngineType;

class SigmieServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Sigmie::class, function ($app) {
            // Hardcoded Elastic Cloud configuration
            $hosts = 'https://manolisiordanidisupwork-4eb020.es.europe-west3.gcp.cloud.es.io';
            $user = 'elastic';
            $password = '9uksfFl4T2ET8alEUQWPoJPu';
            
            $config = [
                'auth' => [
                    $user,
                    $password,
                ],
                'verify' => true, // SSL verification enabled for Elastic Cloud
            ];
            
            $engine = SearchEngineType::Elasticsearch;
            
            return Sigmie::create(
                hosts: $hosts,
                engine: $engine,
                config: $config
            );
        });
    }
    
    public function boot(): void
    {
        //
    }
}
