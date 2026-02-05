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
        
        $documents = [];
        for ($i = 1; $i <= $count; $i++) {
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
        
        $collection = $sigmie->collect('products', refresh: true);
        $collection->merge($documents);
        
        $this->info("✓ Successfully indexed {$count} test products!");
        $this->info("You can now test the search at: http://localhost:8000");
        
        return Command::SUCCESS;
    }
}
