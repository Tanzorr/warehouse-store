<?php

namespace App\DTO;


use Symfony\Component\Validator\Constraints as Assert;
class ReservationStatusInput
{
    #[Assert\NotBlank]
    public string $transition;
}
