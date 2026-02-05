<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sigmie\Mappings\NewProperties;
use Sigmie\Sigmie;

class SearchController extends Controller
{
    public function __construct(
        private Sigmie $sigmie
    ) {}

    public function index()
    {
        return view('search');
    }

    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $color = $request->input('color');
        
        $properties = new NewProperties;
        $properties->name('name');
        $properties->number('price')->float();
        $properties->category('color');
        
        $search = $this->sigmie->newSearch('products')
            ->properties($properties)
            ->queryString($query)
            ->highlighting(['name'], prefix: '<mark class="bg-yellow-200">', suffix: '</mark>')
            ->size(20)
            ->sort('_score:desc');
        
        // Add filters
        $filters = [];
        if ($minPrice !== null) {
            $filters[] = "price>={$minPrice}";
        }
        if ($maxPrice !== null) {
            $filters[] = "price<={$maxPrice}";
        }
        if ($color) {
            $filters[] = "color:{$color}";
        }
        
        if (!empty($filters)) {
            $search->filters(implode(' AND ', $filters));
        }
        
        $response = $search->get();
        
        return response()->json([
            'query' => $query,
            'total' => $response->total(),
            'hits' => $response->hits(),
        ]);
    }
}
