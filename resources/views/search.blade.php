<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OEM / IAM Parts Cross-Matcher — powered by plan.parts</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .pp-gradient { background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans">

{{-- Header --}}
<div class="pp-gradient text-white py-6 px-4 shadow-lg">
    <div class="max-w-5xl mx-auto flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">OEM / IAM Parts Cross-Matcher</h1>
            <p class="text-blue-200 text-sm mt-1">Find analog spare parts in your supplier price lists — powered by
                <a href="https://plan.parts" target="_blank" class="underline text-white font-semibold">plan.parts API</a>
            </p>
        </div>
        <a href="https://plan.parts/register" target="_blank"
           class="hidden sm:inline-block bg-white text-blue-700 font-semibold px-4 py-2 rounded-lg text-sm hover:bg-blue-50 transition">
            Get API Token →
        </a>
    </div>
</div>

<div class="max-w-5xl mx-auto px-4 py-8 space-y-8">

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Search Form --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Search Analog Parts</h2>
        <form method="GET" action="{{ route('plan-parts.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 mb-1">Article Number</label>
                <input type="text"
                       name="article"
                       value="{{ request('article') }}"
                       placeholder="e.g. F026407123, W712/95, 03L115562"
                       class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                       autocomplete="off"
                       required>
            </div>
            <div class="sm:w-48">
                <label class="block text-xs font-medium text-gray-500 mb-1">Brand <span class="text-gray-400">(optional)</span></label>
                <input type="text"
                       name="brand"
                       value="{{ request('brand') }}"
                       placeholder="e.g. BOSCH, MANN"
                       class="w-full border border-gray-200 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
            </div>
            <div class="sm:self-end">
                <button type="submit"
                        class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-lg text-sm transition">
                    Find Analogs
                </button>
            </div>
        </form>
        <p class="text-xs text-gray-400 mt-3">
            Leave brand empty to search across all manufacturers.
            Each search uses 2 API tokens via
            <a href="https://plan.parts/docs/aftermarket-oem-oe-iam" target="_blank" class="text-blue-500 hover:underline">plan.parts API</a>.
        </p>
    </div>

    {{-- Search Results --}}
    @if($result !== null)
        @if($result['error'])
            <div class="bg-red-50 border border-red-200 rounded-2xl p-5">
                <p class="text-sm font-semibold text-red-700">API Error</p>
                <p class="text-sm text-red-600 mt-1">{{ $result['error'] }}</p>
                @if(str_contains($result['error'], 'token'))
                    <p class="text-sm text-gray-600 mt-2">
                        Don't have an API token?
                        <a href="https://plan.parts/register" target="_blank" class="text-blue-600 underline font-semibold">Register at plan.parts</a>
                        to get started.
                    </p>
                @endif
            </div>
        @else
            {{-- Analogs found --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">
                        Analogs for
                        <span class="text-blue-600 font-mono">{{ $result['query']['article'] }}</span>
                        @if($result['query']['brand'])
                            <span class="text-gray-500 font-normal text-base">/ {{ $result['query']['brand'] }}</span>
                        @endif
                    </h2>
                    <span class="text-xs bg-blue-50 text-blue-700 px-3 py-1 rounded-full font-medium">
                        {{ count($result['analogs']) }} analogs found
                    </span>
                </div>

                @if(count($result['analogs']) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left text-xs text-gray-400 uppercase tracking-wider">
                                    <th class="pb-2 pr-4">Article</th>
                                    <th class="pb-2 pr-4">Brand</th>
                                    <th class="pb-2 pr-4">Name</th>
                                    <th class="pb-2">Type</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($result['analogs'] as $analog)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 pr-4 font-mono text-gray-800 font-medium">
                                            {{ $analog['article'] ?? $analog['article_number'] ?? '—' }}
                                        </td>
                                        <td class="py-2 pr-4 text-gray-600">{{ $analog['brand'] ?? '—' }}</td>
                                        <td class="py-2 pr-4 text-gray-500">{{ $analog['name'] ?? $analog['part_name'] ?? '—' }}</td>
                                        <td class="py-2">
                                            <span class="text-xs px-2 py-0.5 rounded-full
                                                {{ ($analog['type'] ?? '') === 'OEM' ? 'bg-purple-50 text-purple-700' : 'bg-green-50 text-green-700' }}">
                                                {{ $analog['type'] ?? 'IAM' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-400 text-sm">No analogs found for this article. Try without specifying a brand.</p>
                @endif
            </div>

            {{-- Price list offers --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Offers in Your Price Lists</h2>
                    <span class="text-xs bg-{{ $result['offers']->count() > 0 ? 'emerald' : 'gray' }}-50
                                        text-{{ $result['offers']->count() > 0 ? 'emerald' : 'gray' }}-700
                                        px-3 py-1 rounded-full font-medium">
                        {{ $result['offers']->count() }} {{ Str::plural('offer', $result['offers']->count()) }}
                    </span>
                </div>

                @if($result['offers']->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left text-xs text-gray-400 uppercase tracking-wider">
                                    <th class="pb-2 pr-4">Article</th>
                                    <th class="pb-2 pr-4">Brand</th>
                                    <th class="pb-2 pr-4">Description</th>
                                    <th class="pb-2 pr-4">Price</th>
                                    <th class="pb-2 pr-4">Stock</th>
                                    <th class="pb-2">Supplier</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($result['offers'] as $offer)
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 pr-4 font-mono text-gray-800 font-medium">{{ $offer->article }}</td>
                                        <td class="py-2 pr-4 text-gray-600">{{ $offer->brand ?: '—' }}</td>
                                        <td class="py-2 pr-4 text-gray-500 max-w-xs truncate">{{ $offer->description ?: '—' }}</td>
                                        <td class="py-2 pr-4 font-semibold text-emerald-700">
                                            {{ number_format($offer->price, 2) }} {{ $offer->currency }}
                                        </td>
                                        <td class="py-2 pr-4">
                                            <span class="text-xs px-2 py-0.5 rounded-full
                                                {{ $offer->availability === 'in_stock' ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">
                                                {{ $offer->availability ?: 'unknown' }}
                                            </span>
                                        </td>
                                        <td class="py-2 text-gray-500 text-xs">{{ $offer->priceList->supplier ?: $offer->priceList->name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif(count($priceLists) === 0)
                    <div class="text-center py-8 text-gray-400">
                        <p class="text-sm">No price lists uploaded yet.</p>
                        <p class="text-sm mt-1">Upload your supplier CSV files below to start matching offers.</p>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-400">
                        <p class="text-sm">No matching offers found in your price lists.</p>
                        <p class="text-sm mt-1">The analogs above were retrieved from plan.parts — upload more price lists to find local offers.</p>
                    </div>
                @endif
            </div>
        @endif
    @endif

    {{-- Two-column layout: Upload + Price Lists --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Upload Form --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-1">Upload Price List</h2>
            <p class="text-xs text-gray-400 mb-4">
                CSV format:
                <code class="bg-gray-100 px-1 rounded">article, brand, price, currency, description, availability</code>
            </p>
            <form method="POST" action="{{ route('plan-parts.upload') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">CSV File</label>
                    <input type="file" name="csv_file" accept=".csv,.txt" required
                           class="w-full text-sm text-gray-600 border border-gray-200 rounded-lg px-3 py-2 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">List Name</label>
                        <input type="text" name="name" placeholder="My Supplier 2024" required
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Supplier <span class="text-gray-400">(optional)</span></label>
                        <input type="text" name="supplier" placeholder="SupplierName"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>
                <button type="submit"
                        class="w-full bg-gray-800 hover:bg-gray-900 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    Upload & Import
                </button>
            </form>
            <p class="text-xs text-gray-400 mt-3">
                <a href="{{ route('plan-parts.index') }}?download_template=1" class="text-blue-500 hover:underline">
                    Download CSV template
                </a>
            </p>
        </div>

        {{-- Price Lists table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">
                Uploaded Price Lists
                <span class="text-sm font-normal text-gray-400">({{ $priceLists->count() }})</span>
            </h2>
            @if($priceLists->count() > 0)
                <div class="space-y-2">
                    @foreach($priceLists as $list)
                        <div class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-2.5">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $list->name }}</p>
                                <p class="text-xs text-gray-400">
                                    {{ number_format($list->items_count) }} items
                                    @if($list->supplier) · {{ $list->supplier }} @endif
                                    · {{ $list->imported_at?->diffForHumans() }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('plan-parts.price-lists.destroy', $list->id) }}"
                                  onsubmit="return confirm('Delete this price list?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ml-3 text-xs text-red-400 hover:text-red-600 transition">
                                    Delete
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-400 text-sm text-center py-6">No price lists yet. Upload your first CSV file.</p>
            @endif
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-center text-xs text-gray-400 pb-4 space-y-1">
        <p>
            Powered by
            <a href="https://plan.parts" target="_blank" class="text-blue-500 hover:underline font-medium">plan.parts</a>
            — Aftermarket API for OEM/IAM auto parts cross-references.
        </p>
        <p>
            <a href="https://plan.parts/register" target="_blank" class="hover:underline">Register</a> ·
            <a href="https://plan.parts/docs/aftermarket-oem-oe-iam" target="_blank" class="hover:underline">API Docs</a> ·
            <a href="https://plan.parts/support" target="_blank" class="hover:underline">Support</a>
        </p>
    </div>

</div>
</body>
</html>
