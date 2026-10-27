<?php

namespace App\Contracts;

interface MutationInterface
{
    public function getParameter(mixed $parameters): mixed;
}
