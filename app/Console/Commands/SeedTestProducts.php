<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sigmie\Document\Document;
use Sigmie\Mappings\NewProperties;
use Sigmie\Sigmie;

class SeedTestProducts extends Command
{
    protected $signature = 'products:seed-test {count=100 : Number of test products to create}';
    protected $description = 'Seed a small number of test products for quick testing';

    public function handle(Sigmie $sigmie): int
    {
        $count = (int) $this->argument('count');
        
        $this->info("Creating products index...");
        
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
            } else {
                $this->error('Failed to create index: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }
        
        $this->info("Generating {$count} test products...");
        
        $productNames = [
            'Wireless Headphones', 'Smartphone Case', 'Laptop Stand', 'USB-C Cable', 'Wireless Mouse',
            'Mechanical Keyboard', 'Monitor Stand', 'Webcam', 'Microphone', 'Speaker System',
            'Tablet Stand', 'Phone Charger', 'Power Bank', 'Bluetooth Adapter', 'HDMI Cable',
            'Gaming Chair', 'Desk Lamp', 'Desk Organizer', 'Cable Management', 'Laptop Sleeve',
        ];
        
        $colors = [
            'Black', 'White', 'Red', 'Blue', 'Green', 'Yellow', 'Orange', 'Purple', 'Pink', 'Gray',
            'Silver', 'Gold', 'Navy', 'Maroon', 'Teal', 'Cyan', 'Magenta', 'Lime', 'Olive', 'Coral',
        ];
        
        $variants = ['Pro', 'Plus', 'Max', 'Mini', 'Standard', 'Premium', 'Deluxe', 'Elite'];
        
        $batchSize = 5000;
        $totalBatches = ceil($count / $batchSize);
        
        $this->info("Processing in {$totalBatches} batches of {$batchSize} products each...");
        
        $bar = $this->output->createProgressBar($totalBatches);
        $bar->setFormat(' %current%/%max% batches [%bar%] %percent:3s%% | %elapsed:6s%/%estimated:-6s% | %memory:6s%');
        $bar->start();
        
        $collection = $sigmie->collect('products', refresh: false);
        $startTime = microtime(true);
        $lastStatusUpdate = 0;
        
        for ($batch = 0; $batch < $totalBatches; $batch++) {
            $documents = [];
            $batchStart = ($batch * $batchSize) + 1;
            $batchEnd = min(($batch + 1) * $batchSize, $count);
            
            for ($i = $batchStart; $i <= $batchEnd; $i++) {
                $baseName = $productNames[array_rand($productNames)];
                $variant = rand(1, 3) === 1 ? ' ' . $variants[array_rand($variants)] : '';
                $name = $baseName . $variant . ' #' . $i;
                $price = number_format(rand(99, 99999) / 100, 2, '.', '');
                $color = $colors[array_rand($colors)];
                
                $documents[] = new Document([
                    'name' => $name,
                    'price' => (float) $price,
                    'color' => $color,
                ]);
            }
            
            $collection->merge($documents);
            
            // Calculate progress info
            $currentTime = microtime(true);
            $elapsed = $currentTime - $startTime;
            $processed = min(($batch + 1) * $batchSize, $count);
            $speed = $elapsed > 0 ? round($processed / $elapsed) : 0;
            $remaining = $count - $processed;
            $eta = $speed > 0 ? round($remaining / $speed) : 0;
            
            // Update progress bar message with status every 10 batches or on last batch
            if (($batch + 1) % 10 === 0 || ($batch + 1) === $totalBatches) {
                $status = sprintf(
                    ' | Products: %s/%s | Speed: %s/s | ETA: %s',
                    number_format($processed),
                    number_format($count),
                    number_format($speed),
                    $eta > 0 ? $this->formatTime($eta) : '--'
                );
                $bar->setMessage($status);
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        $productsPerSecond = round($count / $duration);
        
        $this->info("✓ Successfully indexed " . number_format($count) . " test products in {$duration} seconds!");
        $this->info("✓ Average speed: " . number_format($productsPerSecond) . " products/second");
        $this->info("You can now test the search at: http://localhost:8000");
        
        return Command::SUCCESS;
    }
    
    private function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        if ($minutes < 60) {
            return "{$minutes}m {$remainingSeconds}s";
        }
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return "{$hours}h {$remainingMinutes}m";
    }
}
