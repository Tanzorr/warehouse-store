<?php

namespace App\Validator\Constraints;

use App\Entity\ProductReservationItem;
use App\Service\StockAvailabilityService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ReservationItemAmountValidator extends ConstraintValidator
{
    public function __construct(private readonly StockAvailabilityService $stockAvailabilityService)
    {
    }
    public function validate(mixed $value, Constraint $constraint): void
    {
       if(!$constraint instanceof ReservationItemAmount) {
           throw new UnexpectedTypeException($constraint, ReservationItemAmount::class);
       }

        if (!$value instanceof ProductReservationItem) {
            throw new UnexpectedValueException($value, ProductReservationItem::class);
        }

        $amount = $value->getAmount();

        if ($amount <= 0) {
            $this->context->buildViolation($constraint->message)
                ->atPath('amount')
                ->setParameter('{{ value }}', $amount)
                ->addViolation();
            return;
        }

        $available = $this->stockAvailabilityService->getAvailableStock($value->getProduct());
        if ($amount > $available) {
            $this->context->buildViolation($constraint->message)
                ->atPath('amount')
                ->setParameter('{{ value }}', $amount)
                ->setParameter('{{ available }}', $available)
                ->addViolation();
        }
    }
}
