<?php

declare(strict_types=1);

namespace Test\StaticAnalysis;

use Exp\Decoder;

/** @var mixed * */
$mixedVar = $_GET['hi'] ?? \json_decode('{a: [1]}');

/** @psalm-check-type-exact $_1 = 'hi' */
$_1 = Decoder::literal('hi')->run($mixedVar)->unwrap();

/** @psalm-check-type-exact $_2 = 5 */
$_2 = Decoder::literal(5)->run($mixedVar)->unwrap();

/** @psalm-check-type-exact $_3 = true */
$_3 = Decoder::literal(true)->run($mixedVar)->unwrap();

/** @psalm-check-type-exact $_4 = false */
$_4 = Decoder::literal(false)->run($mixedVar)->unwrap();

/** @psalm-check-type-exact $_5 = Exp\Result\Result<'hi'> */
$_5 = Decoder::literal('hi')->run($mixedVar);

/** @psalm-check-type-exact $_6 = Exp\Result\Result<string> */
$_6 = Decoder::string()->run($mixedVar);
