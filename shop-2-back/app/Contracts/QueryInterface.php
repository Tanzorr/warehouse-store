<?php

namespace App\Contracts;

interface QueryInterface
{
    public function get(string $key): mixed;
}
