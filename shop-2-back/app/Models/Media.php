<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $fillable = ['user_id', 'file_path', 'file_name', 'mime_type', 'size'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFilterBySearch(Builder $query, $search = ''): Builder
    {
        if ($search) {
            $query->where('file_name', 'like', "%{$search}%")
                ->orWhere('mime_type', 'like', "%{$search}%");
        }

        return $query;
    }

    public function relatedEntities()
    {
        return $this->morphedByMany(MediaRelation::class, 'mediable');
    }
}
