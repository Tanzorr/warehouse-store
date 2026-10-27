<?php

namespace App\Services;

use App\Models\SharedAccess;
use Illuminate\Support\Collection;

class SharedAccessService
{
    public function getSharedEntityAccessUserIds(string $accessibleType, string $accessibleId): Collection
    {
        return SharedAccess::where('accessible_type', $accessibleType)
            ->where('accessible_id', $accessibleId)
            ->pluck('user_id');
    }
}
