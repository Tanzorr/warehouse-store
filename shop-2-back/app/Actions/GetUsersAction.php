<?php

namespace App\Actions;

use App\Contracts\QueryInterface;
use App\Contracts\ReadActionInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class GetUsersAction implements ReadActionInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        return User::filterBySearch($query->get('search'))
            ->orderBy('created_at', 'desc')
            ->paginate();
    }
}
