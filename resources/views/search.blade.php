<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Search - Laravel + Sigmie + Elasticsearch</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="bg-white rounded-xl shadow-xl p-6 md:p-8">
            <div class="mb-8">
                <h1 class="text-4xl font-bold mb-2 text-gray-800">Product Search</h1>
                <p class="text-gray-600">Search through millions of products powered by Elasticsearch</p>
            </div>
            
            <div class="mb-6">
                <form id="searchForm" class="space-y-4">
                    <div class="relative">
                        <input 
                            type="text" 
                            id="searchInput" 
                            name="q" 
                            placeholder="Search products by name..." 
                            class="w-full px-6 py-4 text-lg border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                            autocomplete="off"
                        >
                        <div id="loadingIndicator" class="hidden absolute right-4 top-1/2 transform -translate-y-1/2">
                            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Min Price ($)</label>
                            <input 
                                type="number" 
                                id="minPrice" 
                                name="min_price" 
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Max Price ($)</label>
                            <input 
                                type="number" 
                                id="maxPrice" 
                                name="max_price" 
                                step="0.01"
                                min="0"
                                placeholder="999.99"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Color</label>
                            <input 
                                type="text" 
                                id="color" 
                                name="color" 
                                placeholder="e.g., Red, Blue, Black"
                                class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button 
                            type="submit" 
                            id="searchButton"
                            class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all transform hover:scale-105"
                        >
                            Search
                        </button>
                        <button 
                            type="button" 
                            id="clearButton"
                            class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all"
                        >
                            Clear
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Facets Sidebar -->
                <div id="facets" class="lg:col-span-1 hidden lg:block">
                    <div class="bg-white rounded-lg border border-gray-200 p-4 sticky top-4">
                        <h2 class="text-lg font-bold mb-4 text-gray-800">Filters</h2>
                        
                        <!-- Color Facets -->
                        <div id="colorFacets" class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Colors</h3>
                            <div id="colorFacetList" class="space-y-2">
                                <p class="text-gray-400 text-sm">Loading...</p>
                            </div>
                        </div>
                        
                        <!-- Price Facets -->
                        <div id="priceFacets" class="mb-6">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Price Ranges</h3>
                            <div id="priceFacetList" class="space-y-2">
                                <p class="text-gray-400 text-sm">Loading...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Results -->
                <div id="results" class="lg:col-span-3 space-y-4">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <p class="text-gray-500 text-lg">Enter a search query to get started</p>
                        <p class="text-gray-400 text-sm mt-2">Try searching for: "headphones", "laptop", or filter by price/color</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('searchForm');
        const input = document.getElementById('searchInput');
        const results = document.getElementById('results');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const searchButton = document.getElementById('searchButton');
        const clearButton = document.getElementById('clearButton');
        
        let currentSearchController = null;
        let isSearching = false;

        // Instant search function - updates on every keystroke
        function performSearch() {
            const query = input.value.trim();
            const minPrice = document.getElementById('minPrice').value;
            const maxPrice = document.getElementById('maxPrice').value;
            const color = document.getElementById('color').value.trim();
            
            // Cancel previous request if still pending
            if (currentSearchController) {
                currentSearchController.abort();
            }
            
            // Create new AbortController for this request
            currentSearchController = new AbortController();
            
            isSearching = true;
            loadingIndicator.classList.remove('hidden');
            searchButton.disabled = true;
            searchButton.classList.add('opacity-50', 'cursor-not-allowed');

            // Only show loading if we don't have results yet
            if (!results.querySelector('.grid')) {
                results.innerHTML = `
                    <div class="text-center py-12">
                        <svg class="animate-spin mx-auto h-12 w-12 text-blue-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-gray-600">Searching products...</p>
                    </div>
                `;
            }

            const formData = new FormData();
            if (query) formData.append('q', query);
            if (minPrice) formData.append('min_price', minPrice);
            if (maxPrice) formData.append('max_price', maxPrice);
            if (color) formData.append('color', color);
            
            fetch('/search', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
                signal: currentSearchController.signal,
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    loadingIndicator.classList.add('hidden');
                    searchButton.disabled = false;
                    searchButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    isSearching = false;

                    // Show errors if any
                    if (data.errors && data.errors.length > 0) {
                        console.warn('Search errors:', data.errors);
                    }

                    // Update facets
                    updateFacets(data.facets || {});

                    if (data.hits && data.hits.length > 0) {
                        let html = `
                            <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <p class="text-blue-800 font-semibold">
                                    Found <span class="text-2xl">${data.total.toLocaleString()}</span> result${data.total !== 1 ? 's' : ''}
                                </p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        `;
                        
                        data.hits.forEach(hit => {
                            const source = hit._source || hit.source || {};
                            const highlight = hit.highlight || {};
                            
                            // Safely extract highlighted name or fallback to source name
                            let name = source.name || 'No name';
                            if (highlight.name && highlight.name.length > 0) {
                                name = highlight.name[0];
                            }
                            
                            const price = source.price ? '$' + parseFloat(source.price).toFixed(2) : 'N/A';
                            const color = source.color || 'Unknown';
                            
                            // Color badge with dynamic background
                            const colorMap = {
                                'Red': 'bg-red-100 text-red-800',
                                'Blue': 'bg-blue-100 text-blue-800',
                                'Green': 'bg-green-100 text-green-800',
                                'Yellow': 'bg-yellow-100 text-yellow-800',
                                'Black': 'bg-gray-800 text-white',
                                'White': 'bg-gray-100 text-gray-800',
                                'Orange': 'bg-orange-100 text-orange-800',
                                'Purple': 'bg-purple-100 text-purple-800',
                                'Pink': 'bg-pink-100 text-pink-800',
                            };
                            const colorClass = colorMap[color] || 'bg-gray-100 text-gray-800';
                            
                            html += `
                                <div class="border-2 border-gray-200 rounded-lg p-5 hover:shadow-lg hover:border-blue-300 transition-all transform hover:-translate-y-1 bg-white">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="text-lg font-bold text-gray-800 flex-1 mr-2" style="word-wrap: break-word;">
                                            ${name}
                                        </h3>
                                        <span class="text-xl font-bold text-blue-600 whitespace-nowrap">${price}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-3 py-1 ${colorClass} rounded-full text-sm font-medium">${color}</span>
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                        results.innerHTML = html;
                    } else {
                        results.innerHTML = `
                            <div class="text-center py-12">
                                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-gray-600 text-lg font-semibold mb-2">No results found</p>
                                <p class="text-gray-500">Try adjusting your search criteria or filters</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    // Ignore abort errors (cancelled requests)
                    if (error.name === 'AbortError') {
                        return;
                    }
                    
                    console.error('Search error:', error);
                    loadingIndicator.classList.add('hidden');
                    searchButton.disabled = false;
                    searchButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    isSearching = false;
                    
                    results.innerHTML = `
                        <div class="text-center py-12">
                            <svg class="mx-auto h-16 w-16 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-red-600 text-lg font-semibold mb-2">Error performing search</p>
                            <p class="text-gray-500">Please check your connection and try again</p>
                        </div>
                    `;
                });
        }

        // Form submission
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            performSearch();
        });

        // Instant search on every keystroke
        input.addEventListener('input', () => {
            performSearch();
        });
        
        // Also trigger instant search on filter changes
        document.getElementById('minPrice').addEventListener('input', () => {
            performSearch();
        });
        
        document.getElementById('maxPrice').addEventListener('input', () => {
            performSearch();
        });
        
        document.getElementById('color').addEventListener('input', () => {
            performSearch();
        });

        // Clear button
        clearButton.addEventListener('click', () => {
            input.value = '';
            document.getElementById('minPrice').value = '';
            document.getElementById('maxPrice').value = '';
            document.getElementById('color').value = '';
            results.innerHTML = `
                <div class="text-center py-12">
                    <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <p class="text-gray-500 text-lg">Enter a search query to get started</p>
                    <p class="text-gray-400 text-sm mt-2">Try searching for: "headphones", "laptop", or filter by price/color</p>
                </div>
            `;
        });

        // Allow Enter key to submit
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                form.dispatchEvent(new Event('submit'));
            }
        });
        
        // Load initial facets on page load
        performSearch();
        
        // Function to update facets
        function updateFacets(facets) {
            const colorFacetList = document.getElementById('colorFacetList');
            const priceFacetList = document.getElementById('priceFacetList');
            const facetsContainer = document.getElementById('facets');
            
            // Show facets container if we have data
            if (facets.color || facets.price) {
                facetsContainer.classList.remove('hidden');
            }
            
            // Update color facets - handle terms/buckets format
            if (facets.color) {
                const colorData = facets.color;
                let html = '';
                
                // Check if it's a terms aggregation (has buckets array)
                if (colorData.buckets && Array.isArray(colorData.buckets)) {
                    colorData.buckets.forEach(bucket => {
                        const isActive = document.getElementById('color').value === bucket.key;
                        html += `
                            <div class="flex items-center justify-between p-2 rounded ${isActive ? 'bg-blue-50 border border-blue-300' : 'hover:bg-gray-50'} cursor-pointer transition-colors" 
                                 onclick="selectColorFacet('${bucket.key}')">
                                <span class="text-sm text-gray-700">${bucket.key}</span>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">${bucket.doc_count.toLocaleString()}</span>
                            </div>
                        `;
                    });
                } else if (Array.isArray(colorData)) {
                    // Handle array format
                    colorData.forEach(facet => {
                        const isActive = document.getElementById('color').value === (facet.key || facet.value);
                        html += `
                            <div class="flex items-center justify-between p-2 rounded ${isActive ? 'bg-blue-50 border border-blue-300' : 'hover:bg-gray-50'} cursor-pointer transition-colors" 
                                 onclick="selectColorFacet('${facet.key || facet.value}')">
                                <span class="text-sm text-gray-700">${facet.key || facet.value}</span>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">${(facet.doc_count || facet.count || 0).toLocaleString()}</span>
                            </div>
                        `;
                    });
                }
                
                if (html) {
                    colorFacetList.innerHTML = html;
                } else {
                    colorFacetList.innerHTML = '<p class="text-gray-400 text-sm">No colors found</p>';
                }
            } else {
                colorFacetList.innerHTML = '<p class="text-gray-400 text-sm">No colors found</p>';
            }
            
            // Update price facets - show stats and create ranges
            if (facets.price) {
                const priceData = facets.price;
                let html = '';
                
                // Check if it has buckets (range aggregation)
                if (priceData.buckets && Array.isArray(priceData.buckets)) {
                    priceData.buckets.forEach(bucket => {
                        const currentMin = document.getElementById('minPrice').value;
                        const currentMax = document.getElementById('maxPrice').value;
                        const isActive = currentMin == bucket.from && currentMax == bucket.to;
                        
                        let rangeLabel = bucket.key;
                        if (bucket.from !== null && bucket.to !== null) {
                            rangeLabel = `$${bucket.from} - $${bucket.to}`;
                        } else if (bucket.from !== null) {
                            rangeLabel = `$${bucket.from}+`;
                        }
                        
                        html += `
                            <div class="flex items-center justify-between p-2 rounded ${isActive ? 'bg-blue-50 border border-blue-300' : 'hover:bg-gray-50'} cursor-pointer transition-colors" 
                                 onclick="selectPriceFacet(${bucket.from || 'null'}, ${bucket.to || 'null'})">
                                <span class="text-sm text-gray-700">${rangeLabel}</span>
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">${bucket.doc_count.toLocaleString()}</span>
                            </div>
                        `;
                    });
                } else if (priceData.min !== undefined && priceData.max !== undefined) {
                    // Show price stats and create ranges
                    const min = Math.floor(priceData.min);
                    const max = Math.ceil(priceData.max);
                    const avg = Math.round(priceData.avg || (min + max) / 2);
                    
                    // Create price ranges based on stats
                    const ranges = [
                        { from: min, to: Math.min(min + 50, avg), label: `$${min} - $${Math.min(min + 50, avg)}` },
                        { from: Math.min(min + 50, avg), to: avg, label: `$${Math.min(min + 50, avg)} - $${avg}` },
                        { from: avg, to: max, label: `$${avg} - $${max}` },
                    ];
                    
                    ranges.forEach(range => {
                        html += `
                            <div class="flex items-center justify-between p-2 rounded hover:bg-gray-50 cursor-pointer transition-colors" 
                                 onclick="selectPriceFacet(${range.from}, ${range.to})">
                                <span class="text-sm text-gray-700">${range.label}</span>
                            </div>
                        `;
                    });
                    
                    // Show stats
                    html += `
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-xs text-gray-500 mb-1">Price Range</p>
                            <p class="text-sm font-semibold text-gray-700">$${min.toFixed(2)} - $${max.toFixed(2)}</p>
                            <p class="text-xs text-gray-500 mt-1">Avg: $${avg.toFixed(2)}</p>
                        </div>
                    `;
                }
                
                if (html) {
                    priceFacetList.innerHTML = html;
                } else {
                    priceFacetList.innerHTML = '<p class="text-gray-400 text-sm">No price data found</p>';
                }
            } else {
                priceFacetList.innerHTML = '<p class="text-gray-400 text-sm">No price data found</p>';
            }
        }
        
        // Function to select color facet
        function selectColorFacet(color) {
            document.getElementById('color').value = color;
            performSearch();
        }
        
        // Function to select price facet
        function selectPriceFacet(min, max) {
            document.getElementById('minPrice').value = min || '';
            document.getElementById('maxPrice').value = max || '';
            performSearch();
        }
        
        // Make functions globally available
        window.selectColorFacet = selectColorFacet;
        window.selectPriceFacet = selectPriceFacet;
    </script>
</body>
</html>
