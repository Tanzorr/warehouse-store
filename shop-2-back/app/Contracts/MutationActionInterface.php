<?php

namespace App\Contracts;

interface MutationActionInterface
{
    public function handle(array $data): mixed;
}
