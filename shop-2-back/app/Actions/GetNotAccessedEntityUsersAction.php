<?php

namespace App\Actions;

use App\Contracts\QueryInterface;
use App\Contracts\ReadActionInterface;

use App\Models\User;
use Illuminate\Support\Collection;

class GetNotAccessedEntityUsersAction implements ReadActionInterface
{
    public function handle(QueryInterface $query): Collection
    {
        return User::filterBySearch($query->get('search'))
            ->whereNotIn('id', $query->get('accessed_user_ids'))
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }
}
