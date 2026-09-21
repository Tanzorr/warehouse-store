<?php

namespace Tests\Unit\Domain\Shared\ValueObjects;

use App\Domain\Shared\ValueObjects\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_creates_money_with_valid_amount(): void
    {
        $money = new Money(19.99);

        $this->assertSame(19.99, $money->amount);
    }

    public function test_rounds_amount_to_two_decimals(): void
    {
        $money = new Money(19.999);

        $this->assertSame(20.0, $money->amount);
    }

    public function test_throws_for_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Money(-0.01);
    }

    public function test_is_greater_than(): void
    {
        $this->assertTrue((new Money(10))->isGreaterThan(new Money(5)));
        $this->assertFalse((new Money(5))->isGreaterThan(new Money(10)));
    }
}
