<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'purchase_price',
        'sale_price',
        'stock',
        'sku',
        'category_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        return $query->when($keyword, fn ($q) => $q->where('name', 'LIKE', "%{$keyword}%")
            ->orWhere('description', 'LIKE', "%{$keyword}%"))
            ->orWhere('purchase_price', 'LIKE', "%{$keyword}%")
            ->orWhere('sale_price', 'LIKE', "%{$keyword}%");
    }

    public function scopeFilterByCategory(Builder $query, ?array $categoryIds): Builder
    {
        return $query->when(
            $categoryIds,
            fn($q) => $q->whereIn('category_id', $categoryIds)
        );
    }

    public function scopeFilterByTags(Builder $query, array $tagIds = []): Builder
    {
        return $query->when(! empty($tagIds), fn ($q) => $q->whereHas('tags', fn ($tq) => $tq->whereIn('tags.id', $tagIds)));
    }
}
