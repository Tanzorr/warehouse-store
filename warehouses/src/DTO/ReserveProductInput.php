<?php

namespace App\DTO;

class ReserveProductInput
{
    public int $id;
    public int $amount;

    public function __construct(int $id, int $amount)
    {
        $this->id = $id;
        $this->amount = $amount;
    }
}
