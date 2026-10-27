<?php

namespace Tests\Unit\Domain\Shared\ValueObjects;

use App\Domain\Shared\ValueObjects\Quantity;
use Tests\TestCase;

class QuantityTest extends TestCase
{
    public function test_creates_quantity_with_valid_value(): void
    {
        $quantity = new Quantity(5);

        $this->assertSame(5, $quantity->value);
    }

    public function test_allows_zero(): void
    {
        $this->assertSame(0, (new Quantity(0))->value);
    }

    public function test_throws_for_negative_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Quantity(-1);
    }

    public function test_add_and_subtract(): void
    {
        $quantity = new Quantity(5);

        $this->assertSame(8, $quantity->add(new Quantity(3))->value);
        $this->assertSame(2, $quantity->subtract(new Quantity(3))->value);
    }
}
