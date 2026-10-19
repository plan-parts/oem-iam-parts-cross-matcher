<?php

namespace PlanParts\CrossMatcher\Facades;

use Illuminate\Support\Facades\Facade;
use PlanParts\CrossMatcher\PlanPartsClient;

/**
 * @method static array analogs(string $article, ?string $brand = null)
 * @method static array crosses(string $artId)
 * @method static array search(string $article)
 * @method static array suggest(string $query)
 * @method static array health()
 *
 * @see \PlanParts\CrossMatcher\PlanPartsClient
 */
class PlanParts extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PlanPartsClient::class;
    }
}
