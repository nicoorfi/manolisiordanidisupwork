# Agent Notes

## Elastic Cloud Configuration

**Elasticsearch Endpoint:**
```
https://manolisiordanidisupwork-4eb020.es.europe-west3.gcp.cloud.es.io
```

**Credentials:**
- User: `elastic`
- Password: `9uksfFl4T2ET8alEUQWPoJPu`

**Environment Variables:**
```env
ELASTICSEARCH_HOSTS=https://manolisiordanidisupwork-4eb020.es.europe-west3.gcp.cloud.es.io
ELASTICSEARCH_USER=elastic
ELASTICSEARCH_PASSWORD=9uksfFl4T2ET8alEUQWPoJPu
ELASTICSEARCH_VERIFY_SSL=true
ELASTICSEARCH_ENGINE=elasticsearch
```

## Deployment

**Server:** `nico@35.242.239.121`  
**App Directory:** `/var/www/manolisiordanidisupwork`  
**Repository:** `git@github.com:nicoorfi/manolisiordanidisupwork.git`

**Deploy Command:**
```bash
envoy run deploy
```

**Deployment Flow:**
1. Push to repository
2. Enable maintenance mode
3. Clone repository
4. Run composer
5. Update symlinks
6. Run migrations
7. Optimize Laravel
8. Cleanup old releases
9. Reload PHP-FPM
10. Reload Octane
11. Disable maintenance mode

**Octane:** Uses graceful reload (`artisan octane:reload`) for zero-downtime deployments.

## Search Features

- Real-time instant search (updates on every keystroke)
- Color facets
- Price facets
- Form data POST requests
- CSRF disabled for `/search` route

## Commands

- `php artisan products:seed-test {count}` - Seed test products
- `php artisan products:generate-csv {count}` - Generate CSV file
- `php artisan products:index-csv {file}` - Index products from CSV
