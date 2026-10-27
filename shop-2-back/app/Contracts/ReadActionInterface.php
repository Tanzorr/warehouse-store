<?php

namespace App\Contracts;

interface ReadActionInterface
{
    public function handle(QueryInterface $query): mixed;
}
