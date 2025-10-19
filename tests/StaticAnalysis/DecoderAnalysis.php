<?php

declare(strict_types=1);

namespace Test\StaticAnalysis;

use Exp\Decoder;

/** @var mixed * */
$mixedVar = $_GET['hi'] ?? \json_decode('{a: [1]}');

/** @psalm-check-type-exact $_1 = 'hi' */
$_1 = Decoder::literal('hi')->run($mixedVar)->unwrap();

/** @psalm-check-type-exact $_2 = \5 */
$_2 = Decoder::literal(5)->run($mixedVar)->unwrap();

/** @psalm-check-type-exact $_3 = \true */
$_3 = Decoder::literal(true)->run($mixedVar)->unwrap();

/** @psalm-check-type-exact $_4 = \false */
$_4 = Decoder::literal(false)->run($mixedVar)->unwrap();

/** @psalm-check-type-exact $_5 = \Exp\Result\Result<'hi'> */
$_5 = Decoder::literal('hi')->run($mixedVar);

/** @psalm-check-type-exact $_6 = \Exp\Result\Result<string> */
$_6 = Decoder::string()->run($mixedVar);

/** @psalm-check-type-exact $_7 = \Exp\Result\Result<object> */
$_7 = Decoder::object()->run($mixedVar);

/** @psalm-check-type-exact $_8 = \Exp\Result\Result<\DateTimeImmutable> */
$_8 = Decoder::object(\DateTimeImmutable::class)->run($mixedVar);

/** @psalm-check-type-exact $_9 = \Exp\Result\Result<list<string>> */
$_9 = Decoder::list(Decoder::string())->run($mixedVar);

/** @psalm-check-type-exact $_10 = \Exp\Result\Result<list<bool>> */
$_10 = Decoder::list(Decoder::string())->map(fn(array $values): array => \array_map(
    fn(string $v): bool => (bool) $v,
    $values,
))->run($mixedVar);

/** @psalm-check-type-exact $_weightDecoder = \Exp\Decoder<\float> */
$_weightDecoder = Decoder::arrayKey('weight', Decoder::float());
/** @psalm-check-type-exact $_nameDecoder = \Exp\Decoder<\string> */
$_nameDecoder = Decoder::arrayKey('name', Decoder::string());
/** @psalm-check-type-exact $_linesDecoder = \Exp\Decoder<\list<\Test\StaticAnalysis\OrderItem>> */
$_linesDecoder = Decoder::list(Decoder::object(OrderItem::class));
/** @psalm-check-type-exact $_qtyDecoder = \Exp\Decoder<\float> */
$_qtyDecoder = Decoder::arrayKey('qty', Decoder::float());
/** @psalm-check-type-exact $_priceDecoder = \Exp\Decoder<\float> */
$_priceDecoder = Decoder::arrayKey('price', Decoder::float());
/** @psalm-check-type-exact $_orderItemDecoder = \Exp\Decoder<\Test\StaticAnalysis\OrderItem> */
$_orderItemDecoder = Decoder::map2(
    $_qtyDecoder,
    $_priceDecoder,
    fn($qty, $price) => (
        /**
         * @psalm-check-type-exact $qty = \float
         * @psalm-check-type-exact $price = \float
         */
        new OrderItem($qty, $price)
    ),
);
$_linesDecoder = Decoder::arrayKey('lines', Decoder::list($_orderItemDecoder));

/** @psalm-check-type-exact $_orderDecoder = \Exp\Decoder<\Test\StaticAnalysis\Order> */
$_orderDecoder = Decoder::map3(
    $_weightDecoder,
    $_nameDecoder,
    $_linesDecoder,
    fn($weight, $name, $lines) => (
        /**
         * NOTE: Psalm cannot assert $lines type this because it lacks higher order template types.
         * TODO(@aszenz):NOT_WORKING psalm-check-type-exact $lines = \list<Test\StaticAnalysis\OrderItem>.
         *
         * @psalm-check-type-exact $weight = \float
         * @psalm-check-type-exact $name = \string
         */
        new Order($weight, $name, $lines)
    ),
);
/** @psalm-check-type-exact $_listOrderDecoder = \list<\Test\StaticAnalysis\Order> */
$_listOrderDecoder = Decoder::list($_orderDecoder)->run($mixedVar)->unwrap();

/**
 * @psalm-immutable
 */
final class Order
{
    /**
     * @param float           $weight
     * @param string          $name
     * @param list<OrderItem> $lines
     */
    public function __construct(
        public float $weight,
        public string $name,
        public array $lines,
    ) {}
}

/**
 * @psalm-immutable
 */
final class OrderItem
{
    public function __construct(
        public float $qty,
        public float $price,
    ) {}
}
