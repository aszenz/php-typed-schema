<?php

declare(strict_types=1);

namespace Test;

use Exp\Decoder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Decoder::class)]
#[UsesClass(\Exp\Result\Result::class)]
#[UsesClass(\Exp\Result\Ok::class)]
#[UsesClass(\Exp\Result\Error::class)]
final class DecoderTest extends TestCase
{
    public function testBool(): void
    {
        static::assertTrue(Decoder::bool()->run(true)->isOk());
        static::assertTrue(Decoder::bool()->run(false)->isOk());
        static::assertFalse(Decoder::bool()->run('false')->isOk());
        static::assertFalse(Decoder::bool()->run('0')->isOk());
        static::assertFalse(Decoder::bool()->run('1')->isOk());
        static::assertFalse(Decoder::bool()->run([])->isOk());
        static::assertFalse(Decoder::bool()->run(0.0)->isOk());
        static::assertFalse(Decoder::bool()->run(new \stdClass())->isOk());
    }

    public function testString(): void
    {
        static::assertTrue(Decoder::string()->run('hi')->isOk());
        static::assertTrue(Decoder::string()->run('')->isOk());
        static::assertFalse(Decoder::string()->run(false)->isOk());
        static::assertFalse(Decoder::string()->run(0)->isOk());
        static::assertFalse(Decoder::string()->run(1)->isOk());
        static::assertFalse(Decoder::string()->run([])->isOk());
        static::assertFalse(Decoder::string()->run(0.0)->isOk());
        static::assertFalse(Decoder::string()->run(new \stdClass())->isOk());
    }

    public function testInt(): void
    {
        static::assertTrue(Decoder::int()->run(1)->isOk());
        static::assertTrue(Decoder::int()->run(-100)->isOk());
        static::assertFalse(Decoder::int()->run(false)->isOk());
        static::assertFalse(Decoder::int()->run(0.0)->isOk());
        static::assertFalse(Decoder::int()->run(1.2)->isOk());
        static::assertFalse(Decoder::int()->run([])->isOk());
        static::assertFalse(Decoder::int()->run(-1.0)->isOk());
        static::assertFalse(Decoder::int()->run(new \stdClass())->isOk());
    }

    public function testFloat(): void
    {
        static::assertTrue(Decoder::float()->run(1.0)->isOk());
        static::assertTrue(Decoder::float()->run(-100.12)->isOk());
        static::assertFalse(Decoder::float()->run(false)->isOk());
        static::assertFalse(Decoder::float()->run(0)->isOk());
        static::assertFalse(Decoder::float()->run(-1)->isOk());
        static::assertFalse(Decoder::float()->run([])->isOk());
        static::assertFalse(Decoder::float()->run(-10123)->isOk());
        static::assertFalse(Decoder::float()->run(new \stdClass())->isOk());
    }

    public function testArray(): void
    {
        static::assertTrue(Decoder::array()->run([])->isOk());
        static::assertTrue(Decoder::array()->run([1, 2])->isOk());
        static::assertTrue(Decoder::array()->run(['hi' => 1, 'bye' => 2])->isOk());
        static::assertFalse(Decoder::array()->run(false)->isOk());
        static::assertFalse(Decoder::array()->run(0)->isOk());
        static::assertFalse(Decoder::array()->run(-1)->isOk());
        static::assertFalse(Decoder::array()->run(-10123)->isOk());
        static::assertFalse(Decoder::array()->run(new \stdClass())->isOk());
    }

    public function testList(): void
    {
        static::assertTrue(Decoder::list()->run([])->isOk());
        static::assertTrue(Decoder::list()->run([1, 2])->isOk());
        static::assertFalse(Decoder::list()->run(['hi' => 1, 'bye' => 2])->isOk());
        static::assertTrue(Decoder::list()->run([0 => 1, 1 => 21, 2 => 4])->isOk());
        static::assertFalse(Decoder::list()->run([0 => 1, 1 => 2, 4 => 4])->isOk());
        static::assertFalse(Decoder::list()->run(false)->isOk());
        static::assertFalse(Decoder::list()->run(0)->isOk());
        static::assertFalse(Decoder::list()->run(-1)->isOk());
        static::assertFalse(Decoder::list()->run(-10123)->isOk());
        static::assertFalse(Decoder::list()->run(new \stdClass())->isOk());
    }

    public function testNonEmptyList(): void
    {
        static::assertFalse(Decoder::nonEmptyList()->run([])->isOk());
        static::assertTrue(Decoder::nonEmptyList()->run([1, 2])->isOk());
    }

    public function testScalar(): void
    {
        static::assertTrue(Decoder::scalar()->run(12)->isOk());
        static::assertTrue(Decoder::scalar()->run(12.2)->isOk());
        static::assertTrue(Decoder::scalar()->run(false)->isOk());
        static::assertTrue(Decoder::scalar()->run(null)->isErr());
        static::assertTrue(Decoder::scalar()->run('')->isOk());
        static::assertTrue(Decoder::scalar()->run('abc')->isOk());
        static::assertTrue(Decoder::scalar()->run([1, 2])->isErr());
        static::assertTrue(Decoder::scalar()->run(new \DateTime())->isErr());
        static::assertTrue(Decoder::scalar()->run(new \stdClass())->isErr());
    }

    public function testIterable(): void
    {
        static::assertTrue(Decoder::iterable()->run([1, 2, 3])->isOk());
        static::assertTrue(Decoder::iterable()->run(['hi' => 'bye'])->isOk());
        static::assertTrue(Decoder::iterable()->run((fn(): \Generator => yield 'a')())->isOk());
        static::assertTrue(Decoder::iterable()->run(null)->isErr());
        static::assertTrue(Decoder::iterable()->run('123')->isErr());
        static::assertTrue(Decoder::iterable()->run(1)->isErr());
        static::assertTrue(Decoder::iterable()->run(null)->isErr());
        static::assertTrue(Decoder::iterable()->run('123')->isErr());
        static::assertTrue(Decoder::iterable()->run(1)->isErr());
    }

    public function testObject(): void
    {
        static::assertTrue(Decoder::object()->run(new \DateTime())->isOk());
        static::assertTrue(Decoder::object()->run(new \stdClass())->isOk());
        static::assertFalse(Decoder::object()->run(false)->isOk());
        static::assertFalse(Decoder::object()->run(0)->isOk());
        static::assertFalse(Decoder::object()->run(-1)->isOk());
        static::assertFalse(Decoder::object()->run([])->isOk());
        static::assertFalse(Decoder::object()->run(-10123)->isOk());
        static::assertTrue(Decoder::object(\DateTime::class)->run(new \DateTime())->isOk());
        static::assertFalse(Decoder::object(\DateTimeImmutable::class)->run(new \stdClass())->isOk());
        static::assertFalse(Decoder::object()->run(false)->isOk());
    }

    public function testLiteralWithSameValues(): void
    {
        static::assertTrue(Decoder::literal(5)->run(5)->isOk());
        static::assertFalse(Decoder::literal(5)->run(5)->isErr());
        static::assertSame(5, Decoder::literal(5)->run(5)->unwrap());

        static::assertTrue(Decoder::literal('hi')->run('hi')->isOk());
        static::assertFalse(Decoder::literal('hi')->run('hi')->isErr());
        static::assertSame('hi', Decoder::literal('hi')->run('hi')->unwrap());

        static::assertTrue(Decoder::literal(true)->run(true)->isOk());
        static::assertFalse(Decoder::literal(true)->run(true)->isErr());
        static::assertTrue(Decoder::literal(true)->run(true)->unwrap());
    }

    public function testLiteralWithDifferentValues(): void
    {
        static::assertTrue(Decoder::literal(5)->run(4)->isErr());
        static::assertFalse(Decoder::literal(5)->run(4)->isOk());
        static::assertTrue(Decoder::literal('hi')->run(4)->isErr());
        static::assertFalse(Decoder::literal('hi')->run('hii')->isOk());
        static::assertTrue(Decoder::literal(false)->run(true)->isErr());
        static::assertFalse(Decoder::literal(false)->run(true)->isOk());
    }

    public function testUnionOf(): void
    {
        static::assertTrue(Decoder::unionOf(Decoder::bool(), Decoder::int())->run(1)->isOk());
        static::assertTrue(Decoder::unionOf(Decoder::bool(), Decoder::int())->run(true)->isOk());
        static::assertFalse(Decoder::unionOf(Decoder::bool(), Decoder::int())->run('string')->isOk());
    }

    public function testOneOfWithSuccess(): void
    {
        $decoder = Decoder::oneOf(Decoder::bool()->map(fn(bool $value): int => $value ? 1 : 0), Decoder::int());
        static::assertTrue($decoder->run(1)->isOk());
        static::assertSame(0, $decoder->run(false)->unwrap());
        static::assertSame(1, $decoder->run(true)->unwrap());
    }

    public function testOneOfWithFailure(): void
    {
        $decoder = Decoder::oneOf(Decoder::bool()->map(fn(bool $value): int => $value ? 1 : 0), Decoder::int());
        $onFailure = $decoder->run(1.5);
        static::assertFalse($onFailure->isOk());
        static::assertTrue($onFailure->isErr());
        static::assertCount(2, $onFailure->unwrapError());
    }

    public function testNullable(): void
    {
        $decoder = Decoder::nullable(Decoder::string());
        static::assertTrue($decoder->run(null)->isOk());
        static::assertTrue($decoder->run('')->isOk());
        static::assertFalse($decoder->run(0)->isOk());
    }

    public function testNonEmptyString(): void
    {
        $decoder = Decoder::nonEmptyString();
        static::assertTrue($decoder->run('hello')->isOk());
        static::assertFalse($decoder->run('')->isOk());
        static::assertFalse($decoder->run('  ')->isOk());
        static::assertTrue($decoder->run('  ')->isErr());
        static::assertFalse($decoder->run(0)->isOk());
    }

    public function testDateString(): void
    {
        $decoder = Decoder::dateString('d-m-Y');
        static::assertTrue($decoder->run('13-12-1995')->isOk());
        static::assertTrue($decoder->run('13-12-1995')->unwrap() instanceof \DateTimeImmutable);
        static::assertSame('13-12-1995', $decoder->run('13-12-1995')->unwrap()->format('d-m-Y'));
        static::assertFalse($decoder->run('hello')->isOk());
        static::assertFalse($decoder->run(0)->isOk());
    }

    public function testNumeric(): void
    {
        $decoder = Decoder::numeric();
        static::assertTrue($decoder->run('0.0')->isOk());
        static::assertTrue($decoder->run('-123.1')->isOk());
        static::assertTrue($decoder->run(-123.1)->isOk());
        static::assertSame(-123.1, $decoder->run(-123.1)->unwrap());
        static::assertSame(123.1, $decoder->run('123.1')->unwrap());
        static::assertSame(123.132, $decoder->run('123.132')->unwrap());
        static::assertSame(12, $decoder->run('12')->unwrap());
        static::assertTrue($decoder->run(100)->isOk());
        static::assertTrue($decoder->run(-100)->isOk());
        static::assertTrue($decoder->run(0.12)->isOk());
        static::assertTrue($decoder->run(-5.12)->isOk());
        static::assertFalse($decoder->run('12a')->isOk());
        static::assertFalse($decoder->run('ba')->isOk());
        static::assertFalse($decoder->run('-ba')->isOk());
    }

    public function testNumericWithFormatter(): void
    {
        $decoder = Decoder::numeric(new \NumberFormatter('nl-NL', \NumberFormatter::DEFAULT_STYLE));
        static::assertTrue($decoder->run('231')->isOk());
        static::assertSame(-123.1, $decoder->run('-123,1')->unwrap());
        static::assertTrue($decoder->run('hi')->isErr());

        $decoder = Decoder::numeric(new \NumberFormatter('en-GB', \NumberFormatter::DEFAULT_STYLE));
        static::assertTrue($decoder->run('2,231.213')->isOk());
        static::assertFalse($decoder->run('2,1.213')->isOk());
        static::assertTrue($decoder->run('hi')->isErr());
        static::assertSame(-123.1, $decoder->run('-123.1')->unwrap());
    }

    public function testNumericWithFormatterAndBadString(): void
    {
        $decoder = Decoder::numeric(new \NumberFormatter('nl-NL', \NumberFormatter::DEFAULT_STYLE));
        static::assertTrue($decoder->run('abc')->isErr());
        static::assertFalse($decoder->run('cde')->isOk());
    }

    public function testNumericWithFormatterAndBadValue(): void
    {
        $decoder = Decoder::numeric(new \NumberFormatter('nl-NL', \NumberFormatter::DEFAULT_STYLE));
        static::assertTrue($decoder->run(false)->isErr());
        static::assertFalse($decoder->run(true)->isOk());
    }

    public function testPositiveInt(): void
    {
        $decoder = Decoder::positiveInt();
        static::assertTrue($decoder->run(1)->isOk());
        static::assertFalse($decoder->run(0)->isOk());
        static::assertFalse($decoder->run(-0)->isOk());
        static::assertFalse($decoder->run(121.12)->isOk());
        static::assertTrue($decoder->run(121)->isOk());
        static::assertFalse($decoder->run(0.0123)->isOk());
        static::assertFalse($decoder->run(-0.0123)->isOk());
    }

    public function testArrayKey(): void
    {
        $decoder = Decoder::arrayKey('foo');
        static::assertTrue($decoder->run(['foo' => 1])->isOk());
        static::assertFalse($decoder->run(['foo' => 1])->isErr());
        static::assertTrue($decoder->run(['foooo' => 2])->isErr());
        static::assertFalse($decoder->run(['foooo' => 2])->isOk());
        static::assertTrue($decoder->run(['foo' => 'hey'])->isOk());
        static::assertFalse($decoder->run(['foo' => 'hey'])->isErr());

        static::assertFalse($decoder->run(['boo' => 1])->isOk());
    }

    public function testArrayKeyWithValueType(): void
    {
        $decoder = Decoder::arrayKey('foo', Decoder::int());
        static::assertTrue($decoder->run(['foo' => 1])->isOk());
        static::assertFalse($decoder->run(['foooo' => 2])->isOk());
        static::assertFalse($decoder->run(['foo' => 'hey'])->isOk());
    }

    public function testOptionalArrayKey(): void
    {
        $decoder = Decoder::optionalArrayKey('foo');
        static::assertTrue($decoder->run(['foo' => 1])->isOk());
        static::assertFalse($decoder->run(['foo' => 1])->isErr());
        static::assertTrue($decoder->run(['foooo' => 2])->isOk());
        static::assertFalse($decoder->run(['foooo' => 2])->isErr());
        static::assertNull($decoder->run(['foooo' => 2])->unwrap());
    }

    public function testOptionalArrayKeyWithValueType(): void
    {
        $decoder = Decoder::optionalArrayKey('foo', Decoder::int());
        static::assertTrue($decoder->run(['foo' => 1])->isOk());
        static::assertFalse($decoder->run(['foo' => 1])->isErr());
        static::assertTrue($decoder->run(['foooo' => 2])->isOk());
        static::assertFalse($decoder->run(['foooo' => 2])->isErr());
        static::assertNull($decoder->run(['foooo' => 2])->unwrap());
    }

    public function testJsonWithCorrectJson(): void
    {
        $userJson = <<<JSON
            {
                "name":  "sarah",
                "dob":  "11-11-1985",
                "age":  26,
                "hobbies": [
                    {
                        "code": "f.p",
                        "desc": "elegeant programming"
                    }
                ]
            }
        JSON;

        $decodingResult = (new UserDecoder())()->run($userJson);

        static::assertTrue($decodingResult->isOk());
    }

    public function testJsonWithInCorrectJson(): void
    {
        $userJson = <<<JSON
            {
                "name"  "rar",
            }
        JSON;

        $decodingResult = (new UserDecoder())()->run($userJson);

        static::assertTrue($decodingResult->isErr());
    }

    public function testJsonWithNonStringValue(): void
    {
        $decodingResult = (new UserDecoder())()->run(false);

        static::assertTrue($decodingResult->isErr());
    }

    public function testArrayShapeToDto(): void
    {
        /** @var mixed */
        $arrayShape = ['id' => 1, 'quantity' => '123.20', 'order_date' => '02-02-2021'];

        $dtoDecoder = Decoder::map3(
            Decoder::arrayKey('id', Decoder::int()),
            Decoder::arrayKey('quantity', Decoder::numeric()),
            Decoder::arrayKey('order_date', Decoder::dateString('d-m-Y')),
            /** @psalm-pure */
            fn(int $id, float $qty, \DateTimeImmutable $date) => new Order($id, $qty, $date),
        );

        $dto = $dtoDecoder->run($arrayShape)->unwrap();

        static::assertSame(1, $dto->id);
        static::assertSame(123.20, $dto->qty);
    }

    public function testListOfArrayShapeToListOfDto(): void
    {
        /** @var mixed */
        $arrayShape = [
            ['id' => 1, 'quantity' => '123.20', 'order_date' => '02-02-2021'],
            ['id' => 2, 'quantity' => '3.20', 'order_date' => '03-04-2021'],
        ];

        $dtoDecoder = Decoder::map3(
            Decoder::arrayKey('id', Decoder::int()),
            Decoder::arrayKey('quantity', Decoder::numeric()),
            Decoder::arrayKey('order_date', Decoder::dateString('d-m-Y')),
            /** @psalm-pure */
            fn(int $id, float $qty, \DateTimeImmutable $date) => new Order($id, $qty, $date),
        );

        $listOfDtos = Decoder::list($dtoDecoder)->run($arrayShape)->unwrap();

        static::assertSame(1, $listOfDtos[0]->id ?? '');
        static::assertSame(3.20, $listOfDtos[1]->qty ?? '');
    }

    public function testMap(): void
    {
        $decoder = Decoder::bool()->map(fn(bool $value): int => $value ? 1 : 0);
        static::assertSame(1, $decoder->run(true)->unwrap());
        static::assertSame(0, $decoder->run(false)->unwrap());
    }

    public function testAndThen(): void
    {
        $decoder = Decoder::string()->andThen(fn(string $value): Decoder => 'hello' === $value || 'hallo' === $value
            ? Decoder::succeed(true)
            : Decoder::fail(['not expected value']));
        static::assertTrue($decoder->run('hello')->unwrap());
        static::assertTrue($decoder->run(false)->isErr());
    }

    public function testDictOf(): void
    {
        $decoder = Decoder::dictOf(Decoder::string());

        static::assertTrue($decoder->run(['bar' => 'foo'])->isOk());
        static::assertSame(['bar' => 'foo'], $decoder->run(['bar' => 'foo'])->unwrap());
        static::assertFalse($decoder->run('string')->isOk());
        static::assertTrue($decoder->run(['bar' => 2])->isErr());
    }

    public function testMap5(): void
    {
        $res = Decoder::map5(
            Decoder::string(),
            Decoder::string(),
            Decoder::string(),
            Decoder::string(),
            Decoder::string(),
            fn(string $a, string $b, string $c, string $d, string $e): string => $a . $b . $c . $d . $e,
        )->run('5');

        static::assertTrue($res->isOk());
        static::assertSame('55555', $res->unwrap());
    }

    public function testMap6(): void
    {
        $res = Decoder::map6(
            Decoder::string(),
            Decoder::string(),
            Decoder::string(),
            Decoder::string(),
            Decoder::string(),
            Decoder::string(),
            fn(string $a, string $b, string $c, string $d, string $e, string $f): string => $a . $b . $c . $d . $e . $f,
        )->run('a');

        static::assertTrue($res->isOk());
        static::assertSame('aaaaaa', $res->unwrap());
    }

    public function testArrayMap2(): void
    {
        $res = Decoder::arrayMap2(
            Decoder::string(),
            Decoder::string(),
            fn(string $a, string $b): string => $a . $b,
        )->run(['a', 'b']);

        static::assertTrue($res->isOk());
        static::assertSame('ab', $res->unwrap());
    }

    public function testArrayMap3(): void
    {
        $decoder = Decoder::arrayMap3(Decoder::string(), Decoder::bool(), Decoder::int(), fn(
            string $a,
            bool $b,
            int $c,
        ): string|int => $b ? $a : $c);

        static::assertTrue($decoder->run(['hey', false, 1])->isOk());
        static::assertSame(1, $decoder->run(['hey', false, 1])->unwrap());
        static::assertTrue($decoder->run([false, 1, 'hey'])->isErr());
    }
}

/**
 * @psalm-immutable
 */
final class Order
{
    public function __construct(
        public int $id,
        public float $qty,
        public \DateTimeImmutable $date,
    ) {}
}

/**
 * @psalm-immutable
 */
final class Hobby
{
    /**
     * @psalm-param non-empty-string $code
     */
    public function __construct(
        public string $code,
        public string $description,
    ) {}
}

/**
 * @psalm-immutable
 */
final class User
{
    /**
     * @psalm-param non-empty-string $name
     * @psalm-param positive-int $age
     * @psalm-param non-empty-list<Hobby> $hobbies
     */
    public function __construct(
        public string $name,
        public \DateTimeImmutable $dob,
        public int $age,
        public array $hobbies,
    ) {}
}

final class UserDecoder
{
    /**
     * @return Decoder<User>
     */
    public function __invoke(): Decoder
    {
        return Decoder::jsonDecode(Decoder::map4(
            Decoder::arrayKey('name', Decoder::nonEmptyString()),
            Decoder::arrayKey('dob', Decoder::dateString('d-m-Y')),
            Decoder::arrayKey('age', Decoder::positiveInt()),
            Decoder::arrayKey('hobbies', Decoder::nonEmptyList(self::hobbyDecoder())),
            /**
             * @psalm-param non-empty-string $user
             * @psalm-param positive-int $age
             * @psalm-param non-empty-list<Hobby> $hobbies
             */
            fn(string $user, \DateTimeImmutable $dob, int $age, array $hobbies): User => new User(
                $user,
                $dob,
                $age,
                $hobbies,
            ),
        ));
    }

    /**
     * @return Decoder<Hobby>
     */
    private static function hobbyDecoder(): Decoder
    {
        return Decoder::map2(
            Decoder::arrayKey('code', Decoder::nonEmptyString()),
            Decoder::arrayKey('desc', Decoder::string()),
            /**
             * @psalm-param non-empty-string $code
             */
            fn(string $code, string $description): Hobby => new Hobby($code, $description),
        );
    }
}
