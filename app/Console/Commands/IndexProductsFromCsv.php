<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sigmie\Document\Document;
use Sigmie\Mappings\NewProperties;
use Sigmie\Sigmie;

class IndexProductsFromCsv extends Command
{
    protected $signature = 'products:index-csv {file=products_5.0M.csv : CSV file name} {--chunk=1000 : Number of products to index per batch}';
    protected $description = 'Index products from CSV file into Elasticsearch';

    public function handle(Sigmie $sigmie): int
    {
        $filename = $this->argument('file');
        $filepath = storage_path('app/' . $filename);
        $chunkSize = (int) $this->option('chunk');

        if (!file_exists($filepath)) {
            $this->error("File not found: {$filepath}");
            $this->info("Generate the CSV file first using: php artisan products:generate-csv");
            return Command::FAILURE;
        }

        $this->info("Creating/updating products index...");
        
        $properties = new NewProperties;
        $properties->name('name');
        $properties->number('price')->float();
        $properties->category('color');
        
        try {
            $index = $sigmie->newIndex('products')
                ->properties($properties)
                ->tokenizeOnWhitespaces()
                ->lowercase()
                ->trim()
                ->create();
            
            $this->info('Index created successfully!');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                $this->warn('Index already exists, skipping creation.');
                $index = $sigmie->index('products');
            } else {
                $this->error('Failed to create index: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $this->info("Reading CSV file: {$filename}");
        $this->info("Batch size: {$chunkSize} products");

        $file = fopen($filepath, 'r');
        
        // Skip header row
        fgetcsv($file);

        $collection = $sigmie->collect('products', refresh: false);
        $documents = [];
        $totalIndexed = 0;
        $lineNumber = 1;

        $startTime = microtime(true);
        $bar = $this->output->createProgressBar();
        $bar->setFormat(' %current% products indexed [%elapsed%]');

        while (($row = fgetcsv($file)) !== false) {
            $lineNumber++;
            
            if (count($row) < 3) {
                continue;
            }

            [$name, $price, $color] = $row;
            
            // Validate data
            if (empty($name) || !is_numeric($price) || empty($color)) {
                continue;
            }

            $documents[] = new Document([
                'name' => trim($name),
                'price' => (float) $price,
                'color' => trim($color),
            ]);

            if (count($documents) >= $chunkSize) {
                $collection->merge($documents);
                $totalIndexed += count($documents);
                $bar->advance(count($documents));
                $documents = [];
            }
        }

        // Index remaining documents
        if (!empty($documents)) {
            $collection->merge($documents);
            $totalIndexed += count($documents);
            $bar->advance(count($documents));
        }

        fclose($file);
        $bar->finish();
        $this->newLine();

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->info("✓ Indexed {$totalIndexed} products in {$duration} seconds");
        $this->info("✓ Average speed: " . round($totalIndexed / $duration) . " products/second");

        return Command::SUCCESS;
    }
}
