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
            // Support Cloud ID (Elastic Cloud) or direct hosts
            $cloudId = env('ELASTICSEARCH_CLOUD_ID');
            $hosts = env('ELASTICSEARCH_HOSTS', 'https://manolisiordanidisupwork-4eb020.es.europe-west3.gcp.cloud.es.io');
            
            // Decode Cloud ID if provided
            if ($cloudId) {
                $hosts = $this->decodeCloudId($cloudId);
            }
            
            $config = [];
            
            // Elastic Cloud requires authentication
            // Note: env() doesn't work when config is cached - ensure config:clear is run after .env changes
            $user = env('ELASTICSEARCH_USER');
            $password = env('ELASTICSEARCH_PASSWORD');
            
            // Default user to 'elastic' if not set, but password is required
            if (empty($user)) {
                $user = 'elastic';
            }
            
            if (empty($password)) {
                throw new \RuntimeException(
                    "ELASTICSEARCH_PASSWORD must be set for Elastic Cloud. " .
                    "Current USER: " . ($user ?: 'not set') . ". " .
                    "If you just updated .env, run: php artisan config:clear"
                );
            }
            
            $config['auth'] = [
                $user,
                $password,
            ];
            
            // SSL verification - default to true for Elastic Cloud (secure by default)
            // Only disable for local development if needed
            $verifySsl = env('ELASTICSEARCH_VERIFY_SSL', 'true');
            if ($verifySsl === 'false' || $verifySsl === false) {
                $config['verify'] = false;
            } else {
                // For Elastic Cloud, we want to verify SSL certificates
                $config['verify'] = true;
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
    
    /**
     * Decode Elastic Cloud ID to extract endpoint
     * Format: <deployment-name>:<base64-encoded-info>
     * Encoded info: <host>:<port>$<es-id>$<kibana-id>
     * Result: https://<es-id>.<host>:<port>
     */
    private function decodeCloudId(string $cloudId): string
    {
        if (!str_contains($cloudId, ':')) {
            return $cloudId; // Not a valid Cloud ID format
        }
        
        [$deploymentName, $encoded] = explode(':', $cloudId, 2);
        
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            return $cloudId; // Invalid base64, return as-is
        }
        
        // Format: <host>:<port>$<es-id>$<kibana-id>
        $parts = explode('$', $decoded);
        if (count($parts) < 2) {
            return $cloudId; // Invalid format
        }
        
        // Extract host and port from first part
        $hostPort = $parts[0]; // e.g., "europe-west3.gcp.cloud.es.io:443"
        $esId = $parts[1]; // Elasticsearch ID
        
        // Build endpoint: https://<es-id>.<host>:<port>
        $endpoint = "https://{$esId}.{$hostPort}";
        
        return $endpoint;
    }

    public function boot(): void
    {
        //
    }
}
