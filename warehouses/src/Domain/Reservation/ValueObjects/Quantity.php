<?php

namespace App\Domain\Reservation\ValueObjects;

final class Quantity
{
   public function __construct(
       public readonly int $value,
   ) {
       if ($value <= 0) {
           throw new \InvalidArgumentException('Reservation quantity must be positive, got '.$value);
       }
   }

   public function __toString():string
   {
       return (string) $this->value;
   }
}
