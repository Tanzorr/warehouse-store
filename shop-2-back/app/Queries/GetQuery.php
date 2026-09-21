<?php

namespace App\Queries;

use App\Contracts\QueryInterface;

class GetQuery implements QueryInterface
{
    public function __construct(private array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    public function get(string $key): mixed
    {
        return $this->parameters[$key] ?? null;
    }
}
