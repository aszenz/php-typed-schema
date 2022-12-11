<?php

declare(strict_types=1);

namespace Test;

use Exp\Decoder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Exp\Decoder
 *
 * @uses \Exp\Result\Result
 * @uses \Exp\Result\Ok
 * @uses \Exp\Result\Error
 */
final class DecoderTest extends TestCase
{
    public function testBool(): void
    {
        self::assertTrue(Decoder::bool()->run(true)->isOk());
        self::assertTrue(Decoder::bool()->run(false)->isOk());
        self::assertFalse(Decoder::bool()->run('false')->isOk());
        self::assertFalse(Decoder::bool()->run('0')->isOk());
        self::assertFalse(Decoder::bool()->run('1')->isOk());
        self::assertFalse(Decoder::bool()->run([])->isOk());
        self::assertFalse(Decoder::bool()->run(0.0)->isOk());
        self::assertFalse(Decoder::bool()->run(new \stdClass())->isOk());
    }

    public function testString(): void
    {
        self::assertTrue(Decoder::string()->run('hi')->isOk());
        self::assertTrue(Decoder::string()->run('')->isOk());
        self::assertFalse(Decoder::string()->run(false)->isOk());
        self::assertFalse(Decoder::string()->run(0)->isOk());
        self::assertFalse(Decoder::string()->run(1)->isOk());
        self::assertFalse(Decoder::string()->run([])->isOk());
        self::assertFalse(Decoder::string()->run(0.0)->isOk());
        self::assertFalse(Decoder::string()->run(new \stdClass())->isOk());
    }

    public function testInt(): void
    {
        self::assertTrue(Decoder::int()->run(1)->isOk());
        self::assertTrue(Decoder::int()->run(-100)->isOk());
        self::assertFalse(Decoder::int()->run(false)->isOk());
        self::assertFalse(Decoder::int()->run(0.0)->isOk());
        self::assertFalse(Decoder::int()->run(1.2)->isOk());
        self::assertFalse(Decoder::int()->run([])->isOk());
        self::assertFalse(Decoder::int()->run(-1.0)->isOk());
        self::assertFalse(Decoder::int()->run(new \stdClass())->isOk());
    }

    public function testFloat(): void
    {
        self::assertTrue(Decoder::float()->run(1.0)->isOk());
        self::assertTrue(Decoder::float()->run(-100.12)->isOk());
        self::assertFalse(Decoder::float()->run(false)->isOk());
        self::assertFalse(Decoder::float()->run(0)->isOk());
        self::assertFalse(Decoder::float()->run(-1)->isOk());
        self::assertFalse(Decoder::float()->run([])->isOk());
        self::assertFalse(Decoder::float()->run(-10123)->isOk());
        self::assertFalse(Decoder::float()->run(new \stdClass())->isOk());
    }

    public function testArray(): void
    {
        self::assertTrue(Decoder::array()->run([])->isOk());
        self::assertTrue(Decoder::array()->run([1, 2])->isOk());
        self::assertTrue(Decoder::array()->run(['hi' => 1, 'bye' => 2])->isOk());
        self::assertFalse(Decoder::array()->run(false)->isOk());
        self::assertFalse(Decoder::array()->run(0)->isOk());
        self::assertFalse(Decoder::array()->run(-1)->isOk());
        self::assertFalse(Decoder::array()->run(-10123)->isOk());
        self::assertFalse(Decoder::array()->run(new \stdClass())->isOk());
    }

    public function testObject(): void
    {
        self::assertTrue(Decoder::object()->run(new \DateTime())->isOk());
        self::assertTrue(Decoder::object()->run(new \stdClass())->isOk());
        self::assertFalse(Decoder::object()->run(false)->isOk());
        self::assertFalse(Decoder::object()->run(0)->isOk());
        self::assertFalse(Decoder::object()->run(-1)->isOk());
        self::assertFalse(Decoder::object()->run([])->isOk());
        self::assertFalse(Decoder::object()->run(-10123)->isOk());
    }

    public function testLiteralWithSameValues(): void
    {
        self::assertTrue(Decoder::literal(5)->run(5)->isOk());
        self::assertFalse(Decoder::literal(5)->run(5)->isErr());
        self::assertSame(5, Decoder::literal(5)->run(5)->unwrap());

        self::assertTrue(Decoder::literal('hi')->run('hi')->isOk());
        self::assertFalse(Decoder::literal('hi')->run('hi')->isErr());
        self::assertSame('hi', Decoder::literal('hi')->run('hi')->unwrap());

        self::assertTrue(Decoder::literal(true)->run(true)->isOk());
        self::assertFalse(Decoder::literal(true)->run(true)->isErr());
        self::assertTrue(Decoder::literal(true)->run(true)->unwrap());
    }

    public function testLiteralWithDifferentValues(): void
    {
        self::assertTrue(Decoder::literal(5)->run(4)->isErr());
        self::assertFalse(Decoder::literal(5)->run(4)->isOk());
        self::assertTrue(Decoder::literal('hi')->run(4)->isErr());
        self::assertFalse(Decoder::literal('hi')->run('hii')->isOk());
        self::assertTrue(Decoder::literal(false)->run(true)->isErr());
        self::assertFalse(Decoder::literal(false)->run(true)->isOk());
    }

    public function testObjectOf(): void
    {
        self::assertTrue(Decoder::objectOf(\DateTime::class)->run(new \DateTime())->isOk());
        self::assertFalse(Decoder::objectOf(\DateTimeImmutable::class)->run(new \stdClass())->isOk());
        self::assertFalse(Decoder::object()->run(false)->isOk());
    }

    public function testUnionOf(): void
    {
        self::assertTrue(Decoder::unionOf(Decoder::bool(), Decoder::int())->run(1)->isOk());
        self::assertTrue(Decoder::unionOf(Decoder::bool(), Decoder::int())->run(true)->isOk());
        self::assertFalse(Decoder::unionOf(Decoder::bool(), Decoder::int())->run('string')->isOk());
    }

    public function testOneOf(): void
    {
        $decoder = Decoder::oneOf(
            Decoder::bool()->map(fn (bool $value): int => $value ? 1 : 0),
            Decoder::int()
        );
        self::assertTrue($decoder->run(1)->isOk());
        self::assertSame(0, $decoder->run(false)->unwrap());
        self::assertSame(1, $decoder->run(true)->unwrap());
    }

    public function testNullable(): void
    {
        $decoder = Decoder::nullable(Decoder::string());
        self::assertTrue($decoder->run(null)->isOk());
        self::assertTrue($decoder->run('')->isOk());
        self::assertFalse($decoder->run(0)->isOk());
    }

    public function testNonEmptyString(): void
    {
        $decoder = Decoder::nonEmptyString();
        self::assertTrue($decoder->run('hello')->isOk());
        self::assertFalse($decoder->run('')->isOk());
        self::assertFalse($decoder->run(0)->isOk());
    }

    public function testDateString(): void
    {
        $decoder = Decoder::dateString('d-m-Y');
        self::assertTrue($decoder->run('13-12-1995')->isOk());
        self::assertTrue($decoder->run('13-12-1995')->unwrap() instanceof \DateTimeImmutable);
        self::assertSame('13-12-1995', $decoder->run('13-12-1995')->unwrap()->format('d-m-Y'));
        self::assertFalse($decoder->run('hello')->isOk());
        self::assertFalse($decoder->run(0)->isOk());
    }

    public function testNumeric(): void
    {
        $decoder = Decoder::numeric();
        self::assertTrue($decoder->run('0.0')->isOk());
        self::assertTrue($decoder->run('-123.1')->isOk());
        self::assertTrue($decoder->run(-123.1)->isOk());
        self::assertSame(-123.1, $decoder->run(-123.1)->unwrap());
        self::assertSame(123.1, $decoder->run('123.1')->unwrap());
        self::assertSame(123.132, $decoder->run('123.132')->unwrap());
        self::assertSame(12, $decoder->run('12')->unwrap());
        self::assertTrue($decoder->run(100)->isOk());
        self::assertTrue($decoder->run(-100)->isOk());
        self::assertTrue($decoder->run(0.12)->isOk());
        self::assertTrue($decoder->run(-5.12)->isOk());
        self::assertFalse($decoder->run('12a')->isOk());
        self::assertFalse($decoder->run('ba')->isOk());
        self::assertFalse($decoder->run('-ba')->isOk());
    }

    public function testPositiveInt(): void
    {
        $decoder = Decoder::positiveInt();
        self::assertTrue($decoder->run(1)->isOk());
        self::assertFalse($decoder->run(0)->isOk());
        self::assertFalse($decoder->run(-0)->isOk());
        self::assertFalse($decoder->run(121.12)->isOk());
        self::assertTrue($decoder->run(121)->isOk());
        self::assertFalse($decoder->run(0.0123)->isOk());
        self::assertFalse($decoder->run(-0.0123)->isOk());
    }

    public function testJson(): void
    {
        $userJson = <<<JSON
            {
                "name":  "asrar",
                "dob":  "14-12-1995",
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

        self::assertTrue($decodingResult->isOk());
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
            fn (int $id, float $qty, \DateTimeImmutable $date) => new Order($id, $qty, $date)
        );

        $dto = $dtoDecoder->run($arrayShape)->unwrap();

        self::assertEquals(1, $dto->id);
        self::assertEquals(123.20, $dto->qty);
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
            fn (int $id, float $qty, \DateTimeImmutable $date) => new Order($id, $qty, $date)
        );

        $listOfDtos = Decoder::listOf($dtoDecoder)->run($arrayShape)->unwrap();

        self::assertEquals(1, $listOfDtos[0]->id ?? '');
        self::assertEquals(3.20, $listOfDtos[1]->qty ?? '');
    }

    public function testMap(): void
    {
        $decoder = Decoder::bool()->map(fn (bool $value): int => $value ? 1 : 0);
        self::assertEquals(
            1,
            $decoder->run(true)->unwrap()
        );
        self::assertEquals(
            0,
            $decoder->run(false)->unwrap()
        );
    }

    public function testAndThen(): void
    {
        $decoder = Decoder::string()->andThen(
            fn (string $value): Decoder => 'hello' === $value || 'hallo' === $value ? Decoder::succeed(true) : Decoder::fail([])
        );
        self::assertTrue(
            $decoder->run('hello')->unwrap()
        );
        self::assertTrue(
            $decoder->run(false)->isErr()
        );
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
        public \DateTimeImmutable $date
    ) {
    }
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
    ) {
    }
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
        public array $hobbies
    ) {
    }
}

final class UserDecoder
{
    /**
     * @return Decoder<User>
     */
    public function __invoke(): Decoder
    {
        return Decoder::jsonDecode(
            Decoder::map4(
                Decoder::arrayKey('name', Decoder::nonEmptyString()),
                Decoder::arrayKey('dob', Decoder::dateString('d-m-Y')),
                Decoder::arrayKey('age', Decoder::positiveInt()),
                Decoder::arrayKey('hobbies', Decoder::nonEmptyListOf(self::hobbyDecoder())),
                /**
                 * @psalm-param non-empty-string $user
                 * @psalm-param positive-int $age
                 * @psalm-param non-empty-list<Hobby> $hobbies
                 */
                fn (string $user, \DateTimeImmutable $dob, int $age, array $hobbies): User => new User(
                    $user,
                    $dob,
                    $age,
                    $hobbies
                )
            )
        );
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
            fn (string $code, string $description): Hobby => new Hobby($code, $description)
        );
    }
}
