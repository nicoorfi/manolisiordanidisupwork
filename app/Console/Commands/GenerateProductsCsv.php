<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateProductsCsv extends Command
{
    protected $signature = 'products:generate-csv {count=5000000 : Number of products to generate}';
    protected $description = 'Generate a CSV file with demo products';

    private array $productNames = [
        'Wireless Headphones', 'Smartphone Case', 'Laptop Stand', 'USB-C Cable', 'Wireless Mouse',
        'Mechanical Keyboard', 'Monitor Stand', 'Webcam', 'Microphone', 'Speaker System',
        'Tablet Stand', 'Phone Charger', 'Power Bank', 'Bluetooth Adapter', 'HDMI Cable',
        'Ethernet Cable', 'USB Hub', 'SD Card', 'External Hard Drive', 'SSD Drive',
        'Gaming Chair', 'Desk Lamp', 'Desk Organizer', 'Cable Management', 'Laptop Sleeve',
        'Backpack', 'Briefcase', 'Wallet', 'Watch', 'Fitness Tracker',
        'Smart Watch', 'Earbuds', 'Headphones', 'Earphones', 'Bluetooth Speaker',
        'Portable Speaker', 'Soundbar', 'TV Stand', 'Wall Mount', 'Surge Protector',
        'Extension Cord', 'Smart Plug', 'LED Strip', 'Smart Bulb', 'Security Camera',
        'Doorbell Camera', 'Smart Lock', 'Thermostat', 'Air Purifier', 'Humidifier',
        'Coffee Maker', 'Kettle', 'Toaster', 'Blender', 'Food Processor',
        'Stand Mixer', 'Microwave', 'Air Fryer', 'Slow Cooker', 'Rice Cooker',
        'Dishwasher', 'Washing Machine', 'Dryer', 'Refrigerator', 'Freezer',
        'Oven', 'Stove', 'Range Hood', 'Sink', 'Faucet',
        'Shower Head', 'Towel Rack', 'Bath Mat', 'Toilet Seat', 'Mirror',
        'Vanity', 'Medicine Cabinet', 'Soap Dispenser', 'Toothbrush Holder', 'Shower Curtain',
        'Bed Frame', 'Mattress', 'Pillow', 'Comforter', 'Sheet Set',
        'Duvet Cover', 'Throw Blanket', 'Curtains', 'Blinds', 'Rug',
        'Carpet', 'Floor Lamp', 'Ceiling Fan', 'Wall Art', 'Picture Frame',
        'Vase', 'Plant Pot', 'Plant Stand', 'Bookshelf', 'Bookcase',
        'Desk', 'Office Chair', 'Filing Cabinet', 'Printer', 'Scanner',
        'Projector', 'Screen', 'Whiteboard', 'Cork Board', 'Calendar',
        'Notebook', 'Pen Set', 'Pencil Case', 'Stapler', 'Paper Clip',
        'Binder', 'Folder', 'Envelope', 'Label', 'Tape',
        'Scissors', 'Ruler', 'Calculator', 'Clock', 'Timer',
        'Thermometer', 'Scale', 'Measuring Cup', 'Measuring Spoon', 'Kitchen Scale',
        'Cutting Board', 'Knife Set', 'Can Opener', 'Bottle Opener', 'Corkscrew',
        'Peeler', 'Grater', 'Colander', 'Strainer', 'Sieve',
        'Mixing Bowl', 'Whisk', 'Spatula', 'Ladle', 'Tongs',
        'Oven Mitt', 'Pot Holder', 'Apron', 'Dish Towel', 'Sponge',
        'Dish Soap', 'Hand Soap', 'Paper Towel', 'Trash Bag', 'Storage Container',
        'Food Storage', 'Lunch Box', 'Water Bottle', 'Travel Mug', 'Coffee Cup',
        'Wine Glass', 'Beer Glass', 'Cocktail Shaker', 'Ice Tray', 'Coaster',
        'Placemat', 'Tablecloth', 'Napkin', 'Napkin Ring', 'Candle',
        'Candle Holder', 'Lighter', 'Matches', 'Incense', 'Diffuser',
        'Essential Oil', 'Soap', 'Shampoo', 'Conditioner', 'Body Wash',
        'Lotion', 'Sunscreen', 'Deodorant', 'Toothpaste', 'Mouthwash',
        'Floss', 'Razor', 'Shaving Cream', 'Aftershave', 'Hairbrush',
        'Comb', 'Hair Dryer', 'Straightener', 'Curling Iron', 'Hair Clipper',
        'Nail Clipper', 'Tweezers', 'Mirror', 'Makeup Brush', 'Makeup Bag',
        'Lipstick', 'Mascara', 'Foundation', 'Concealer', 'Blush',
        'Eyeshadow', 'Eyeliner', 'Highlighter', 'Bronzer', 'Setting Spray',
        'Perfume', 'Cologne', 'Body Spray', 'Lotion', 'Moisturizer',
        'Cleanser', 'Toner', 'Serum', 'Face Mask', 'Eye Cream',
        'Sunscreen', 'Sunscreen Stick', 'Sunscreen Spray', 'After Sun', 'Aloe Vera',
        'First Aid Kit', 'Bandage', 'Antiseptic', 'Pain Relief', 'Cold Medicine',
        'Vitamins', 'Supplements', 'Protein Powder', 'Energy Bar', 'Snack',
        'Candy', 'Chocolate', 'Gum', 'Mints', 'Breath Spray',
        'Hand Sanitizer', 'Wet Wipes', 'Tissues', 'Cotton Swabs', 'Cotton Balls',
        'Bandage', 'Gauze', 'Medical Tape', 'Thermometer', 'Blood Pressure Monitor',
        'Pulse Oximeter', 'Stethoscope', 'Hot Water Bottle', 'Ice Pack', 'Heating Pad',
        'Massage Oil', 'Foam Roller', 'Yoga Mat', 'Exercise Ball', 'Resistance Band',
        'Dumbbell', 'Kettlebell', 'Barbell', 'Weight Plate', 'Bench',
        'Treadmill', 'Exercise Bike', 'Rowing Machine', 'Elliptical', 'Stepper',
        'Jump Rope', 'Pull Up Bar', 'Push Up Bar', 'Ab Roller', 'Balance Board',
        'Grip Strengthener', 'Wrist Weights', 'Ankle Weights', 'Weighted Vest', 'Vest',
        'Running Shoes', 'Walking Shoes', 'Sneakers', 'Athletic Shoes', 'Boots',
        'Sandals', 'Flip Flops', 'Slippers', 'Dress Shoes', 'Casual Shoes',
        'High Heels', 'Flats', 'Loafers', 'Oxfords', 'Derby',
        'Sneakers', 'Trainers', 'Running Shoes', 'Basketball Shoes', 'Tennis Shoes',
        'Soccer Cleats', 'Football Cleats', 'Baseball Cleats', 'Golf Shoes', 'Hiking Boots',
        'Work Boots', 'Safety Boots', 'Rain Boots', 'Winter Boots', 'Snow Boots',
        'Ski Boots', 'Snowboard Boots', 'Ice Skates', 'Roller Skates', 'Skateboard',
        'Bicycle', 'Mountain Bike', 'Road Bike', 'Hybrid Bike', 'Electric Bike',
        'Bike Helmet', 'Bike Lock', 'Bike Light', 'Bike Bell', 'Bike Basket',
        'Bike Rack', 'Bike Pump', 'Bike Tool', 'Bike Chain', 'Bike Tire',
        'Bike Tube', 'Bike Pedal', 'Bike Saddle', 'Bike Handlebar', 'Bike Grips',
        'Bike Brake', 'Bike Gear', 'Bike Chain', 'Bike Derailleur', 'Bike Cassette',
        'Bike Chainring', 'Bike Crank', 'Bike Bottom Bracket', 'Bike Headset', 'Bike Stem',
        'Bike Fork', 'Bike Frame', 'Bike Wheel', 'Bike Rim', 'Bike Spoke',
        'Bike Hub', 'Bike Axle', 'Bike Bearing', 'Bike Seal', 'Bike Grease',
        'Bike Lube', 'Bike Cleaner', 'Bike Degreaser', 'Bike Polish', 'Bike Wax',
        'Bike Cover', 'Bike Storage', 'Bike Stand', 'Bike Repair Stand', 'Bike Workstand',
        'Bike Tool Kit', 'Bike Multi Tool', 'Bike Patch Kit', 'Bike Tire Lever', 'Bike Chain Tool',
        'Bike Cable', 'Bike Housing', 'Bike Brake Pad', 'Bike Brake Cable', 'Bike Shift Cable',
        'Bike Grip Tape', 'Bike Bar Tape', 'Bike Saddle Cover', 'Bike Seat Post', 'Bike Seat Clamp',
        'Bike Stem Cap', 'Bike Top Cap', 'Bike Spacer', 'Bike Spacer Set', 'Bike Spacer Kit',
        'Bike Spacer Tool', 'Bike Spacer Wrench', 'Bike Spacer Pliers', 'Bike Spacer Remover', 'Bike Spacer Installer',
    ];

    private array $colors = [
        'Black', 'White', 'Red', 'Blue', 'Green',
        'Yellow', 'Orange', 'Purple', 'Pink', 'Brown',
        'Gray', 'Grey', 'Silver', 'Gold', 'Bronze',
        'Navy', 'Maroon', 'Teal', 'Turquoise', 'Cyan',
        'Magenta', 'Lime', 'Olive', 'Coral', 'Salmon',
        'Beige', 'Ivory', 'Cream', 'Tan', 'Khaki',
        'Burgundy', 'Indigo', 'Violet', 'Lavender', 'Mauve',
        'Peach', 'Apricot', 'Amber', 'Copper', 'Platinum',
        'Charcoal', 'Slate', 'Steel', 'Titanium', 'Gunmetal',
    ];

    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $filename = 'products_' . number_format($count / 1000000, 1) . 'M.csv';
        $filepath = storage_path('app/' . $filename);

        $this->info("Generating {$count} products...");
        $this->info("This may take a while. Please be patient.");

        $startTime = microtime(true);
        $chunkSize = 10000;
        $totalChunks = ceil($count / $chunkSize);

        $file = fopen($filepath, 'w');
        
        // Write CSV header
        fputcsv($file, ['name', 'price', 'color']);

        $bar = $this->output->createProgressBar($totalChunks);
        $bar->start();

        for ($chunk = 0; $chunk < $totalChunks; $chunk++) {
            $chunkData = [];
            $currentChunkSize = min($chunkSize, $count - ($chunk * $chunkSize));
            
            for ($i = 0; $i < $currentChunkSize; $i++) {
                $productIndex = ($chunk * $chunkSize) + $i;
                $baseName = $this->productNames[array_rand($this->productNames)];
                
                // Add variety: sometimes add model number, sometimes add size/variant
                $variants = ['Pro', 'Plus', 'Max', 'Mini', 'Standard', 'Premium', 'Deluxe', 'Elite'];
                $variant = '';
                if (rand(1, 3) === 1) {
                    $variant = ' ' . $variants[array_rand($variants)];
                }
                
                $name = $baseName . $variant . ' #' . ($productIndex + 1);
                $price = number_format(rand(99, 99999) / 100, 2, '.', '');
                $color = $this->colors[array_rand($this->colors)];
                
                fputcsv($file, [$name, $price, $color]);
            }
            
            $bar->advance();
        }

        fclose($file);
        $bar->finish();
        $this->newLine();

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        $fileSize = filesize($filepath);
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);

        $this->info("✓ Generated {$count} products in {$duration} seconds");
        $this->info("✓ File saved to: {$filepath}");
        $this->info("✓ File size: {$fileSizeMB} MB");

        return Command::SUCCESS;
    }
}
