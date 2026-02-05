<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sigmie\Document\Document;
use Sigmie\Mappings\NewProperties;
use Sigmie\Sigmie;

class SeedDemoData extends Command
{
    protected $signature = 'demo:seed';
    protected $description = 'Seed demo data into Elasticsearch';

    public function handle(Sigmie $sigmie): int
    {
        $this->info('Creating index...');
        
        $properties = new NewProperties;
        $properties->title('title');
        $properties->text('description');
        $properties->name('author');
        $properties->category('category');
        $properties->date('created_at');
        
        try {
            $index = $sigmie->newIndex('demo_articles')
                ->properties($properties)
                ->tokenizeOnWhitespaces()
                ->lowercase()
                ->trim()
                ->create();
            
            $this->info('Index created successfully!');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                $this->warn('Index already exists, skipping creation.');
                $index = $sigmie->index('demo_articles');
            } else {
                $this->error('Failed to create index: ' . $e->getMessage());
                return Command::FAILURE;
            }
        }
        
        $this->info('Indexing demo articles...');
        
        $articles = [
            [
                'title' => 'Introduction to Laravel',
                'description' => 'Laravel is a powerful PHP framework for building modern web applications. It provides elegant syntax and tools for rapid development.',
                'author' => 'Taylor Otwell',
                'category' => 'Web Development',
                'created_at' => now()->subDays(10)->toIso8601String(),
            ],
            [
                'title' => 'Getting Started with Elasticsearch',
                'description' => 'Elasticsearch is a distributed search and analytics engine. Learn how to index and search your data efficiently.',
                'author' => 'Shay Banon',
                'category' => 'Search',
                'created_at' => now()->subDays(8)->toIso8601String(),
            ],
            [
                'title' => 'Building Search with Sigmie',
                'description' => 'Sigmie is a PHP library that makes working with Elasticsearch easy. It provides a fluent API for building powerful search features.',
                'author' => 'Sigmie Team',
                'category' => 'Search',
                'created_at' => now()->subDays(5)->toIso8601String(),
            ],
            [
                'title' => 'Laravel Octane Performance',
                'description' => 'Laravel Octane supercharges your application performance by serving it with high-powered application servers.',
                'author' => 'Taylor Otwell',
                'category' => 'Performance',
                'created_at' => now()->subDays(3)->toIso8601String(),
            ],
            [
                'title' => 'Full-Text Search Best Practices',
                'description' => 'Learn the best practices for implementing full-text search in your applications. Covering indexing, querying, and optimization.',
                'author' => 'Search Expert',
                'category' => 'Search',
                'created_at' => now()->subDays(1)->toIso8601String(),
            ],
            [
                'title' => 'PHP 8 Features',
                'description' => 'Explore the new features in PHP 8 including JIT compilation, named arguments, and union types.',
                'author' => 'PHP Core Team',
                'category' => 'Programming',
                'created_at' => now()->subHours(12)->toIso8601String(),
            ],
            [
                'title' => 'Modern Web Development',
                'description' => 'A comprehensive guide to modern web development practices, tools, and frameworks.',
                'author' => 'Web Developer',
                'category' => 'Web Development',
                'created_at' => now()->subHours(6)->toIso8601String(),
            ],
        ];
        
        $documents = [];
        foreach ($articles as $article) {
            $documents[] = new Document($article);
        }
        
        $collection = $sigmie->collect('demo_articles', refresh: true);
        $collection->merge($documents);
        
        $this->info('Demo data seeded successfully!');
        $this->info('Total articles: ' . count($articles));
        
        return Command::SUCCESS;
    }
}
