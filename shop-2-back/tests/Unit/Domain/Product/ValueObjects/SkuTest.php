<?php

namespace Tests\Unit\Domain\Product\ValueObjects;

use App\Domain\Product\ValueObjects\Sku;
use Tests\TestCase;

class SkuTest extends TestCase
{
    public function test_creates_sku_with_valid_value(): void
    {
        $this->assertSame('ABC-123', (new Sku('ABC-123'))->value);
    }

    public function test_throws_for_empty_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Sku('   ');
    }

    public function test_throws_for_too_long_value(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Sku(str_repeat('a', 51));
    }

    public function test_equals(): void
    {
        $this->assertTrue((new Sku('ABC'))->equals(new Sku('ABC')));
        $this->assertFalse((new Sku('ABC'))->equals(new Sku('XYZ')));
    }
}
