# Product Search Setup Guide

## Overview
This Laravel application includes a full-featured product search powered by Elasticsearch and Sigmie, capable of searching through millions of products.

## Features
- ✅ Full-text search on product names
- ✅ Price range filtering (min/max)
- ✅ Color filtering
- ✅ Search result highlighting
- ✅ Responsive, modern UI
- ✅ Real-time search feedback
- ✅ Error handling

## Prerequisites

1. **Elasticsearch** must be running. For local development:
   ```bash
   docker run -d \
     -p 9200:9200 \
     -e "discovery.type=single-node" \
     -e "xpack.security.enabled=false" \
     docker.elastic.co/elasticsearch/elasticsearch:9.0.0
   ```

2. **Laravel** application should be running:
   ```bash
   php artisan serve
   # or with Octane:
   php artisan octane:start --server=swoole
   ```

## Quick Start - Testing with Sample Data

1. **Seed test products** (50 products for quick testing):
   ```bash
   php artisan products:seed-test 50
   ```

2. **Open the search interface**:
   - Visit: `http://localhost:8000`
   - You should see the search interface

3. **Test the search**:
   - Try searching for: "headphones", "laptop", "keyboard"
   - Try filtering by price: Min: 10, Max: 100
   - Try filtering by color: "Red", "Blue", "Black"

## Full Setup - 5 Million Products

### Step 1: Generate CSV File
```bash
php artisan products:generate-csv 5000000
```
This creates `storage/app/products_5.0M.csv` (takes 5-10 minutes)

### Step 2: Index Products
```bash
php artisan products:index-csv products_5.0M.csv
```
For faster indexing with larger batches:
```bash
php artisan products:index-csv products_5.0M.csv --chunk=5000
```
This takes 10-30 minutes depending on your hardware.

## Available Commands

- `php artisan products:seed-test {count}` - Seed test products (default: 100)
- `php artisan products:generate-csv {count}` - Generate CSV file (default: 5M)
- `php artisan products:index-csv {file} {--chunk=1000}` - Index products from CSV

## Search API Endpoint

**GET** `/search`

**Query Parameters:**
- `q` - Search query (optional)
- `min_price` - Minimum price filter (optional)
- `max_price` - Maximum price filter (optional)
- `color` - Color filter (optional)

**Example:**
```
GET /search?q=headphones&min_price=50&max_price=200&color=Black
```

**Response:**
```json
{
  "query": "headphones",
  "total": 1234,
  "hits": [
    {
      "_source": {
        "name": "Wireless Headphones Pro #123",
        "price": 149.99,
        "color": "Black"
      },
      "highlight": {
        "name": ["Wireless <mark>Headphones</mark> Pro #123"]
      }
    }
  ]
}
```

## Frontend Features

The search interface includes:
- Large, prominent search input
- Price range filters (min/max)
- Color filter
- Clear button to reset filters
- Loading indicators
- Error handling
- Responsive design (mobile-friendly)
- Product cards with highlighted search terms
- Color-coded badges

## Troubleshooting

### Elasticsearch Connection Error
- Ensure Elasticsearch is running: `curl http://localhost:9200`
- Check `.env` file has correct `ELASTICSEARCH_HOSTS`

### No Results Found
- Make sure products are indexed: `php artisan products:seed-test 50`
- Check Elasticsearch index exists: `curl http://localhost:9200/products/_count`

### Search Not Working
- Check browser console for JavaScript errors
- Verify routes are registered: `php artisan route:list`
- Check Laravel logs: `storage/logs/laravel.log`

## Performance Notes

- **5M products**: CSV file ~200-300 MB
- **Indexing speed**: ~1000-5000 products/second (depends on hardware)
- **Search performance**: Sub-second response times even with millions of products
- **Memory usage**: Batch processing prevents memory issues

## Next Steps

1. Test with sample data first (`products:seed-test`)
2. Verify search works correctly
3. Generate full dataset if needed (`products:generate-csv`)
4. Index full dataset (`products:index-csv`)
5. Enjoy fast, powerful search! 🚀
