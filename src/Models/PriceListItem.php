<?php

namespace PlanParts\CrossMatcher\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceListItem extends Model
{
    protected $table = 'pp_price_list_items';

    protected $fillable = [
        'price_list_id',
        'article',
        'article_normalized',
        'brand',
        'brand_normalized',
        'price',
        'currency',
        'description',
        'availability',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    /**
     * Find items matching any of the given article numbers (normalized).
     *
     * @param  array<string>  $articles
     */
    public function scopeMatchingArticles(Builder $query, array $articles): Builder
    {
        $normalized = array_map(
            fn (string $a) => strtoupper(preg_replace('/[\s\-_.]/', '', $a)),
            $articles
        );

        return $query->whereIn('article_normalized', $normalized);
    }
}
