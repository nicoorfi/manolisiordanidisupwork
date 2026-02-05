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
            $hosts = env('ELASTICSEARCH_HOSTS', '127.0.0.1:9200');
            
            $config = [];
            
            if (env('ELASTICSEARCH_USER') && env('ELASTICSEARCH_PASSWORD')) {
                $config['auth'] = [
                    env('ELASTICSEARCH_USER'),
                    env('ELASTICSEARCH_PASSWORD'),
                ];
            }
            
            if (env('ELASTICSEARCH_VERIFY_SSL') === 'false') {
                $config['verify'] = false;
            }
            
            $engine = env('ELASTICSEARCH_ENGINE', 'elasticsearch') === 'opensearch' 
                ? SearchEngineType::OpenSearch 
                : SearchEngineType::Elasticsearch;
            
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
