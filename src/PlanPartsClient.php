<?php

namespace PlanParts\CrossMatcher;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use PlanParts\CrossMatcher\Exceptions\PlanPartsApiException;

class PlanPartsClient
{
    public function __construct(
        private readonly string $token,
        private readonly string $baseUrl = 'https://plan.parts/api',
        private readonly int $timeout = 15,
    ) {}

    /**
     * Find OEM/IAM analog articles by article number and optional brand.
     *
     * Endpoint: GET /v1/aftermarket/analogs
     * Cost: 2 tokens per call
     *
     * @see https://plan.parts/docs/aftermarket-oem-oe-iam
     *
     * @param  string       $article  Article number (IAM, OEM, or EAN)
     * @param  string|null  $brand    Brand name (e.g. "BOSCH", "MANN"). If omitted, all brands are searched.
     * @return array
     */
    public function analogs(string $article, ?string $brand = null): array
    {
        $params = ['article' => $article];

        if ($brand !== null) {
            $params['brand'] = $brand;
        }

        return $this->get('/v1/aftermarket/analogs', $params);
    }

    /**
     * Get OEM and cross-reference numbers for a specific article ID.
     *
     * Endpoint: GET /v1/aftermarket/articles/{art_id}/crosses
     * Cost: 2 tokens per call
     *
     * @see https://plan.parts/docs/aftermarket-oem-oe-iam
     *
     * @param  string  $artId  Article ID returned by analogs() or search()
     * @return array
     */
    public function crosses(string $artId): array
    {
        return $this->get("/v1/aftermarket/articles/{$artId}/crosses");
    }

    /**
     * Search articles by number (IAM, OEM, EAN).
     * Useful for resolving a brand when only article number is known.
     *
     * Endpoint: GET /v1/aftermarket/search
     * Cost: 2 tokens per call
     *
     * @param  string  $article
     * @return array
     */
    public function search(string $article): array
    {
        return $this->get('/v1/aftermarket/search', ['article' => $article]);
    }

    /**
     * Autocomplete suggestions for article search (1 token).
     *
     * @param  string  $query
     * @return array
     */
    public function suggest(string $query): array
    {
        return $this->get('/v1/aftermarket/search/suggest', ['query' => $query]);
    }

    /**
     * Check API availability (free, no tokens consumed).
     */
    public function health(): array
    {
        return $this->get('/v1/aftermarket/health');
    }

    private function get(string $path, array $query = []): array
    {
        if (empty($this->token)) {
            throw new PlanPartsApiException(
                'API token is not configured. Set PLAN_PARTS_TOKEN in your .env file. ' .
                'Get your token at https://plan.parts'
            );
        }

        $response = Http::timeout($this->timeout)
            ->withToken($this->token)
            ->get(rtrim($this->baseUrl, '/') . $path, $query);

        $this->handleErrors($response, $path);

        return $response->json() ?? [];
    }

    private function handleErrors(Response $response, string $path): void
    {
        if ($response->successful()) {
            return;
        }

        $status  = $response->status();
        $message = $response->json('message') ?? $response->body();

        match (true) {
            $status === 401 => throw new PlanPartsApiException(
                'Invalid API token. Register at https://plan.parts to get a valid token.'
            ),
            $status === 402 => throw new PlanPartsApiException(
                'Insufficient tokens. Top up your balance at https://plan.parts'
            ),
            $status === 422 => throw new PlanPartsApiException(
                "Validation error on {$path}: {$message}"
            ),
            $status === 429 => throw new PlanPartsApiException(
                'Rate limit exceeded. Please slow down your requests.'
            ),
            default => throw new PlanPartsApiException(
                "API error {$status} on {$path}: {$message}"
            ),
        };
    }
}
