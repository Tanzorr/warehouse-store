<?php

// src/Validator/Constraints/ReservationItemAmount.php
namespace App\Validator\Constraints;

use App\Constants\ReservationStatusMessage;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ReservationItemAmount extends Constraint
{
    public string $message = ReservationStatusMessage::ERROR_WRONG_AMOUNT;

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}

