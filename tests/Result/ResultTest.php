<?php

declare(strict_types=1);

namespace Test\Result;

use Exp\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Exp\Result\Result
 *
 * @uses \Exp\Result\Ok
 * @uses \Exp\Result\Error
 */
final class ResultTest extends TestCase
{
    public function testOkConstructor(): void
    {
        $val = Result::ok(5);
        self::assertTrue($val->isOk());
        self::assertFalse($val->isErr());
    }

    public function testErrConstructor(): void
    {
        $val = Result::err(['missing value']);
        self::assertFalse($val->isOk());
        self::assertTrue($val->isErr());
    }

    public function testUnwrapWithOk(): void
    {
        $val = Result::ok(5);
        self::assertEquals(5, $val->unwrap());
    }

    public function testUnwrapWithErr(): void
    {
        $val = Result::err(['missing value']);
        $this->expectException(\Error::class);
        $val->unwrap();
    }

    public function testUnwrapErrorWithOk(): void
    {
        $val = Result::ok(5);
        $this->expectException(\Error::class);
        $val->unwrapError();
    }

    public function testUnwrapErrorWithErr(): void
    {
        $val = Result::err(['missing value']);
        self::assertSame(['missing value'], $val->unwrapError());
    }

    public function testMatchWithOk(): void
    {
        $val = Result::ok(5);
        $output = $val->match(
            onOk: fn ($value) => $value,
            onErr: fn ($_) => null
        );
        self::assertEquals(5, $output);
    }

    public function testMatchWithErr(): void
    {
        $val = Result::err(['missing value']);
        $output = $val->match(
            onErr: fn ($value) => $value,
            onOk: fn ($_) => null
        );
        self::assertEquals(['missing value'], $output);
    }

    public function testMapWithOk(): void
    {
        $val = Result::ok(5);
        self::assertEquals(
            50,
            $val->map(
                fn ($value) => $value * 10
            )->unwrap()
        );
    }

    public function testMapWithErr(): void
    {
        $val = Result::err(['missing value']);
        self::assertTrue(
            $val->map(fn ($_) => 1)->isErr()
        );
    }

    public function testOrWithOk(): void
    {
        $val = Result::ok(1);
        $orExpr = $val->or(fn () => Result::ok(5));
        self::assertEquals(
            1,
            $orExpr->unwrap()
        );
        self::assertTrue($orExpr->isOk());
        self::assertFalse($orExpr->isErr());
    }

    public function testOrWithErr(): void
    {
        $val = Result::err(['missing']);
        $orExpr = $val->or(fn () => Result::ok(5));
        self::assertEquals(
            5,
            $orExpr->unwrap()
        );
        self::assertTrue($orExpr->isOk());
        self::assertFalse($orExpr->isErr());
    }

    public function testOrWithBothErr(): void
    {
        $val = Result::err(['missing']);
        self::assertFalse(
            $val->or(fn () => Result::err('problem'))->isOk()
        );
        self::assertTrue(
            $val->or(fn () => Result::err('problem'))->isErr()
        );
        self::assertEquals(
            ['missing', 'problem'],
            $val->or(fn () => Result::err('problem'))->unwrapError()
        );
    }

    public function testAndThenWithOk(): void
    {
        $val = Result::ok(1);
        self::assertEquals(
            5,
            $val->andThen(fn ($_) => Result::ok(5))->unwrap()
        );
    }

    public function testAndThenWithOkAndErr(): void
    {
        $val = Result::ok(1);
        self::assertTrue(
            $val->andThen(fn ($_) => Result::err(['missing']))->isErr()
        );
    }

    public function testAndThenWithErrAndOk(): void
    {
        $val = Result::err(['missing']);
        self::assertTrue(
            $val->andThen(fn ($_) => Result::ok(5))->isErr()
        );
    }

    public function testAndThenWithErrAndErr(): void
    {
        $val = Result::err(['missing']);
        self::assertTrue(
            $val->andThen(fn ($_) => Result::err(['missing']))->isErr()
        );
    }

    public function testCombineWithOks(): void
    {
        $combinedRes = Result::combine([
             Result::ok(5),
             Result::ok(1),
             Result::ok(4),
        ]);
        self::assertEquals(
            [5, 1, 4],
            $combinedRes->unwrap()
        );
    }

    public function testCombineWithErrs(): void
    {
        $combinedRes = Result::combine([
            Result::ok(1),
            Result::ok(4),
            Result::err(['missing']),
        ]);
        $this->expectException(\Error::class);
        $combinedRes->unwrap();
    }

    public function testMap2WithOk(): void
    {
        $a1 = Result::ok(5);
        $a2 = Result::ok(5);
        $z = Result::map2(
            $a1,
            $a2,
            fn (int $x, int $y): int => $x + $y
        );
        self::assertEquals(10, $z->unwrap());
    }

    public function testMap2WithOkAndErr(): void
    {
        $z = Result::map2(
            Result::ok(5),
            Result::err(['missing']),
            fn (int $x, int $y): int => $x + $y
        );
        self::assertTrue($z->isErr());
        $z2 = Result::map2(
            Result::err('missing'),
            Result::ok(5),
            fn ($_, $__) => 1
        );
        self::assertTrue($z2->isErr());
    }

    public function testMap2WithErrAndErr(): void
    {
        $z = Result::map2(
            Result::err('problems'),
            Result::err(['missing']),
            fn (int $x, int $y): int => $x + $y
        );
        self::assertTrue($z->isErr());
        self::assertEquals(['problems', 'missing'], $z->unwrapError());
    }

    public function testMap3WithOk(): void
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

    public function testMap4WithOk(): void
    {
        $z = Result::map4(
            Result::ok(5),
            Result::ok(5),
            Result::ok(5),
            Result::ok(5),
            fn (int $x, int $y, int $z, int $zz): int => $x + $y + $z + $zz
        );
        self::assertEquals(20, $z->unwrap());
    }

    public function testMap5WithOk(): void
    {
        $z = Result::map5(
            Result::ok(5),
            Result::ok(5),
            Result::ok(5),
            Result::ok(5),
            Result::ok(5),
            fn (int $x, int $y, int $z, int $zx, int $zz): int => $x + $y + $z + $zx + $zz
        );
        self::assertEquals(25, $z->unwrap());
    }

    public function testMap6WithOk(): void
    {
        $a1 = Result::ok(5);
        $a2 = Result::ok(5);
        $a3 = Result::ok(5);
        $z = Result::map6(
            $a1,
            $a2,
            $a3,
            $a1,
            $a2,
            $a3,
            fn (int $x, int $y, int $z, int $x2, int $y2, int $z2): int => $x + $y + $z + $x2 + $y2 + $z2
        );
        self::assertEquals(30, $z->unwrap());
    }
}
