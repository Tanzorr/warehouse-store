<?php

namespace Tests\Unit\Domain\Shared\ValueObjects;

use App\Domain\Identity\ValueObjects\CategoryId;
use App\Domain\Identity\ValueObjects\UserId;
use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Product\ValueObjects\ProductId;
use Tests\TestCase;

class IntIdTest extends TestCase
{
    public function test_creates_id_with_positive_value(): void
    {
        $this->assertSame(1, (new ProductId(1))->value);
        $this->assertSame(2, (new OrderId(2))->value);
        $this->assertSame(3, (new UserId(3))->value);
        $this->assertSame(4, (new CategoryId(4))->value);
    }

    public function test_throws_for_zero_or_negative_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProductId(0);
    }

    public function test_equals_compares_type_and_value(): void
    {
        $this->assertTrue((new ProductId(1))->equals(new ProductId(1)));
        $this->assertFalse((new ProductId(1))->equals(new ProductId(2)));
    }
}
