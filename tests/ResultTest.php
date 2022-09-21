<?php

declare(strict_types=1);

namespace Test;

use Exp\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers Exp\Result\Result
 * @uses Exp\Result\Ok
 */
final class ResultTest extends TestCase
{
    public function testMap3(): void
    {
        $a1 = Result::ok(5);
        $a2 = Result::ok(5);
        $a3 = Result::ok(5);
        $z = Result::map3(
            $a1,
            $a2,
            $a3,
            fn (int $x, int $y, int $z): int => $x + $y + $z
        );
        self::assertEquals(15, $z->unwrap());
    }
}
