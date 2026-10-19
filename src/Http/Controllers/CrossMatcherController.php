<?php

namespace PlanParts\CrossMatcher\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use PlanParts\CrossMatcher\Models\PriceList;
use PlanParts\CrossMatcher\Services\AnalogsSearchService;
use PlanParts\CrossMatcher\Services\PriceListImporter;

class CrossMatcherController extends Controller
{
    public function __construct(
        private readonly AnalogsSearchService $searchService,
        private readonly PriceListImporter $importer,
    ) {}

    /**
     * Main search page.
     */
    public function index(Request $request)
    {
        $priceLists = PriceList::orderByDesc('imported_at')->get();
        $result     = null;

        if ($request->filled('article')) {
            $article = trim($request->input('article'));
            $brand   = trim($request->input('brand', '')) ?: null;

            $result = $this->searchService->search($article, $brand);
        }

        return view('plan-parts::search', compact('priceLists', 'result'));
    }

    /**
     * Upload a price list CSV.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:' . (config('plan-parts.upload.max_size_mb', 20) * 1024),
            'name'     => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
        ]);

        $file     = $request->file('csv_file');
        $path     = $file->store('plan-parts/price-lists', config('plan-parts.upload.disk', 'local'));
        $fullPath = Storage::disk(config('plan-parts.upload.disk', 'local'))->path($path);

        $priceList = $this->importer->import(
            filePath: $fullPath,
            name:     $request->input('name'),
            supplier: $request->input('supplier', ''),
        );

        return redirect()
            ->route('plan-parts.index')
            ->with('success', "Price list \"{$priceList->name}\" imported: {$priceList->items_count} items.");
    }

    /**
     * Delete a price list and all its items.
     */
    public function destroy(int $id)
    {
        $priceList = PriceList::findOrFail($id);
        $priceList->items()->delete();
        $priceList->delete();

        return redirect()
            ->route('plan-parts.index')
            ->with('success', "Price list \"{$priceList->name}\" deleted.");
    }

    /**
     * JSON endpoint for brand suggestions (used by search form autocomplete).
     */
    public function suggest(Request $request)
    {
        $article = trim($request->input('article', ''));

        if (strlen($article) < 3) {
            return response()->json([]);
        }

        $brands = $this->searchService->suggestBrands($article);

        return response()->json($brands);
    }
}
