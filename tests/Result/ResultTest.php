<?php

declare(strict_types=1);

namespace Test\Result;

use Exp\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Result::class)]
#[UsesClass(\Exp\Result\Ok::class)]
#[UsesClass(\Exp\Result\Error::class)]
final class ResultTest extends TestCase
{
    public function testOkConstructor(): void
    {
        $val = Result::ok(5);
        static::assertTrue($val->isOk());
        static::assertFalse($val->isErr());
    }

    public function testErrConstructor(): void
    {
        $val = Result::err(['missing value']);
        static::assertFalse($val->isOk());
        static::assertTrue($val->isErr());
    }

    public function testUnwrapWithOk(): void
    {
        $val = Result::ok(5);
        static::assertSame(5, $val->unwrap());
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
        static::assertSame(['missing value'], $val->unwrapError());
    }

    public function testMatchWithOk(): void
    {
        $val = Result::ok(5);
        $output = $val->match(
            onOk: fn($value) => $value,
            onErr: fn($_) => null,
        );
        static::assertSame(5, $output);
    }

    public function testMatchWithErr(): void
    {
        $val = Result::err(['missing value']);
        $output = $val->match(
            onErr: fn($value) => $value,
            onOk: fn($_) => null,
        );
        static::assertSame(['missing value'], $output);
    }

    public function testMapWithOk(): void
    {
        $val = Result::ok(5);
        static::assertSame(50, $val->map(fn($value) => $value * 10)->unwrap());
    }

    public function testMapWithErr(): void
    {
        $val = Result::err(['missing value']);
        static::assertTrue($val->map(fn($_) => 1)->isErr());
    }

    public function testOrWithOk(): void
    {
        $val = Result::ok(1);
        $orExpr = $val->or(fn() => Result::ok(5));
        static::assertSame(1, $orExpr->unwrap());
        static::assertTrue($orExpr->isOk());
        static::assertFalse($orExpr->isErr());
    }

    public function testOrWithErr(): void
    {
        $val = Result::err(['missing']);
        $orExpr = $val->or(fn() => Result::ok(5));
        static::assertSame(5, $orExpr->unwrap());
        static::assertTrue($orExpr->isOk());
        static::assertFalse($orExpr->isErr());
    }

    public function testOrWithBothErr(): void
    {
        $val = Result::err(['missing']);
        static::assertFalse($val->or(fn() => Result::err('problem'))->isOk());
        static::assertTrue($val->or(fn() => Result::err('problem'))->isErr());
        static::assertSame(['missing', 'problem'], $val->or(fn() => Result::err('problem'))->unwrapError());
    }

    public function testAndThenWithOk(): void
    {
        $val = Result::ok(1);
        static::assertSame(5, $val->andThen(fn($_) => Result::ok(5))->unwrap());
    }

    public function testAndThenWithOkAndErr(): void
    {
        $val = Result::ok(1);
        static::assertTrue($val->andThen(fn($_) => Result::err(['missing']))->isErr());
    }

    public function testAndThenWithErrAndOk(): void
    {
        $val = Result::err(['missing']);
        static::assertTrue($val->andThen(fn($_) => Result::ok(5))->isErr());
    }

    public function testAndThenWithErrAndErr(): void
    {
        $val = Result::err(['missing']);
        static::assertTrue($val->andThen(fn($_) => Result::err(['missing']))->isErr());
    }

    public function testCombineWithOks(): void
    {
        $combinedRes = Result::combine([
            Result::ok(5),
            Result::ok(1),
            Result::ok(4),
        ]);
        static::assertSame([5, 1, 4], $combinedRes->unwrap());
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
        $z = Result::map2($a1, $a2, fn(int $x, int $y): int => $x + $y);
        static::assertSame(10, $z->unwrap());
    }

    public function testMap2WithOkAndErr(): void
    {
        $z = Result::map2(Result::ok(5), Result::err(['missing']), fn(int $x, int $y): int => $x + $y);
        static::assertTrue($z->isErr());
        $z2 = Result::map2(Result::err('missing'), Result::ok(5), fn($_, $__) => 1);
        static::assertTrue($z2->isErr());
    }

    public function testMap2WithErrAndErr(): void
    {
        $z = Result::map2(Result::err('problems'), Result::err(['missing']), fn(int $x, int $y): int => $x + $y);
        static::assertTrue($z->isErr());
        static::assertSame(['problems', 'missing'], $z->unwrapError());
    }

    public function testMap3WithOk(): void
    {
        $a1 = Result::ok(5);
        $a2 = Result::ok(5);
        $a3 = Result::ok(5);
        $z = Result::map3($a1, $a2, $a3, fn(int $x, int $y, int $z): int => $x + $y + $z);
        static::assertSame(15, $z->unwrap());
    }

    public function testMap4WithOk(): void
    {
        $z = Result::map4(
            Result::ok(5),
            Result::ok(5),
            Result::ok(5),
            Result::ok(5),
            fn(int $x, int $y, int $z, int $zz): int => $x + $y + $z + $zz,
        );
        static::assertSame(20, $z->unwrap());
    }

    public function testMap5WithOk(): void
    {
        $z = Result::map5(
            Result::ok(5),
            Result::ok(5),
            Result::ok(5),
            Result::ok(5),
            Result::ok(5),
            fn(int $x, int $y, int $z, int $zx, int $zz): int => $x + $y + $z + $zx + $zz,
        );
        static::assertSame(25, $z->unwrap());
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
            fn(int $x, int $y, int $z, int $x2, int $y2, int $z2): int => $x + $y + $z + $x2 + $y2 + $z2,
        );
        static::assertSame(30, $z->unwrap());
    }
}
