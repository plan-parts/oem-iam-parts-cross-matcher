<?php

namespace PlanParts\CrossMatcher\Services;

use Illuminate\Support\Collection;
use PlanParts\CrossMatcher\Models\PriceListItem;
use PlanParts\CrossMatcher\PlanPartsClient;

class AnalogsSearchService
{
    public function __construct(
        private readonly PlanPartsClient $client,
    ) {}

    /**
     * Full search flow:
     *  1. Call /v1/aftermarket/analogs to find all analog article IDs
     *  2. For each analog, optionally call /crosses to expand OEM numbers
     *  3. Match everything against local price list items
     *
     * @return array{
     *     query: array{article: string, brand: string|null},
     *     analogs: array,
     *     offers: Collection,
     *     error: string|null
     * }
     */
    public function search(string $article, ?string $brand = null): array
    {
        $result = [
            'query'   => ['article' => $article, 'brand' => $brand],
            'analogs' => [],
            'crosses' => [],
            'offers'  => collect(),
            'error'   => null,
        ];

        try {
            // Step 1: get analogs
            $analogsResponse = $this->client->analogs($article, $brand);
            $analogs = $analogsResponse['data'] ?? $analogsResponse ?? [];
            $result['analogs'] = $analogs;

            // Collect all article numbers to search in price lists
            $articleNumbers = [$article];

            foreach ($analogs as $analog) {
                if (!empty($analog['article'])) {
                    $articleNumbers[] = $analog['article'];
                }
                if (!empty($analog['article_number'])) {
                    $articleNumbers[] = $analog['article_number'];
                }

                // Step 2: expand crosses for each analog art_id
                $artId = $analog['id'] ?? $analog['art_id'] ?? null;
                if ($artId && config('plan-parts.search.max_analog_depth', 1) >= 1) {
                    try {
                        $crossesResponse  = $this->client->crosses((string) $artId);
                        $crosses = $crossesResponse['data'] ?? $crossesResponse ?? [];
                        $result['crosses'] = array_merge($result['crosses'], $crosses);

                        foreach ($crosses as $cross) {
                            if (!empty($cross['article_number'])) {
                                $articleNumbers[] = $cross['article_number'];
                            }
                        }
                    } catch (\Throwable) {
                        // crosses are optional — skip silently
                    }
                }
            }

            // Step 3: find matching offers in local price lists
            $result['offers'] = PriceListItem::query()
                ->with('priceList')
                ->matchingArticles(array_unique($articleNumbers))
                ->orderBy('price')
                ->get();

        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Suggest brands for an article number (for UI autocomplete).
     */
    public function suggestBrands(string $article): array
    {
        if (config('plan-parts.search.skip_brand_lookup', false)) {
            return [];
        }

        try {
            $response = $this->client->search($article);
            $items = $response['data'] ?? $response ?? [];

            return collect($items)
                ->pluck('brand')
                ->filter()
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
