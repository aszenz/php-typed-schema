<?php

declare(strict_types=1);

namespace Exp;

use Exp\Result\Result;

/**
 * TODO:
 * 1. Better error handling and messages
 * 2. Decoding nested array key `at` support, maybe use symfony array accessor
 * 3. More mapping functions map1 to map8
 * 4. And map function to create inifinte mappings of more than 8
 * 5. Support for decoding into tuples
 * 6. Add support for lazy
 * 7. Support encoding.
 *
 * @template-covariant T
 *
 * @psalm-immutable
 */
final readonly class Decoder
{
    /**
     * @psalm-param pure-Closure(mixed): Result<T> $decodeFn
     */
    private function __construct(
        private \Closure $decodeFn
    ) {
    }

    /**
     * @template V
     *
     * @param callable(T): V $mapperFn
     *
     * @psalm-param pure-callable(T): V $mapperFn
     *
     * @psalm-return self<V>
     */
    public function map(callable $mapperFn): self
    {
        $decodeFn = $this->decodeFn;

        return new self(
            /**
             * @return Result<V>
             *
             * @psalm-pure
             */
            fn (mixed $value): Result => ($decodeFn)($value)->map($mapperFn)
        );
    }

    /**
     * @template V
     *
     * @param callable(T): self<V> $decoderFn
     *
     * @psalm-param pure-callable(T): self<V> $decoderFn
     *
     * @psalm-return self<V>
     */
    public function andThen(callable $decoderFn): self
    {
        return new self(
            /**
             * @var pure-Closure(mixed): Result<V>
             */
            $_ = fn (mixed $value): Result => $this->run($value)->andThen(
                /**
                 * @psalm-pure
                 *
                 * @param T $firstDecoderResult
                 *
                 * @return Result<V>
                 */
                fn ($firstDecoderResult): Result => $decoderFn($firstDecoderResult)->run($firstDecoderResult)
            )
        );
    }

    /**
     * @psalm-return Result<T>
     */
    public function run(mixed $value): Result
    {
        return ($this->decodeFn)($value);
    }

    /**
     * @psalm-pure
     *
     * @template V
     *
     * @psalm-param V $value
     *
     * @psalm-return self<V>
     */
    public static function succeed(mixed $value): self
    {
        return new self(
            fn (mixed $_): Result => Result::ok($value)
        );
    }

    /**
     * @psalm-pure
     *
     * @psalm-param non-empty-list<non-empty-string>|non-empty-string $err
     *
     * @psalm-return self<never>
     */
    public static function fail(array|string $err): self
    {
        return new self(
            fn (mixed $_): Result => Result::err($err)
        );
    }

    /**
     * @psalm-pure
     *
     * @psalm-return self<bool>
     */
    public static function bool(): self
    {
        return new self(
            /**
             * @return Result<bool>
             */
            function (mixed $value): Result {
                if (!is_bool($value)) {
                    return Result::err('Expected boolean value, got '.\get_debug_type($value));
                }

                return Result::ok($value);
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @psalm-return self<string>
     */
    public static function string(): self
    {
        return new self(
            /**
             * @return Result<string>
             */
            function (mixed $value): Result {
                if (!is_string($value)) {
                    return Result::err('Expected string value, got '.\get_debug_type($value));
                }

                return Result::ok($value);
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @psalm-return self<int>
     */
    public static function int(): self
    {
        return new self(
            /**
             * @return Result<int>
             */
            function (mixed $value): Result {
                if (!is_int($value)) {
                    return Result::err('Expected int value, got '.\get_debug_type($value));
                }

                return Result::ok($value);
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @psalm-return self<float>
     */
    public static function float(): self
    {
        return new self(
            /**
             * @return Result<float>
             */
            function (mixed $value): Result {
                if (!is_float($value)) {
                    return Result::err('Expected float value, got '.\get_debug_type($value));
                }

                return Result::ok($value);
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @template V
     *
     * @param ?self<V> $itemDecoder
     *
     * @psalm-return self<array<array-key, V>>
     */
    public static function array(self $itemDecoder = null): self
    {
        return new self(
            /**
             * @return Result<array<V>>
             */
            function (mixed $value) use ($itemDecoder): Result {
                if (!is_array($value)) {
                    return Result::err('Expected array value, got '.\get_debug_type($value));
                }

                return null === $itemDecoder
                    ? Result::ok($value)
                    : Result::combine(
                        \array_map(
                            /**
                             * @return Result<V>
                             */
                            fn (mixed $v): Result => $itemDecoder->run($v),
                            \array_values($value)
                        )
                    )
                ;
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @template V
     *
     * @psalm-param ?class-string<V> $class
     *
     * @psalm-return ($class is null ? self<object> : self<V>)
     */
    public static function object(string $class = null): self
    {
        return new self(
            function (mixed $value) use ($class): Result {
                if (!is_object($value)) {
                    return Result::err('Expected object value, got '.\get_debug_type($value));
                }
                if (null === $class) {
                    return Result::ok($value);
                }

                return $value instanceof $class ? Result::ok($value) : Result::err("Expected object of class $class got ".\get_debug_type($value));
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @template V1
     * @template V2
     *
     * @psalm-param self<V1> $decoder1
     * @psalm-param self<V2> $decoder2
     *
     * @psalm-return self<V1|V2>
     */
    public static function unionOf(self $decoder1, self $decoder2): self
    {
        return new self(
            /*
             * @return Result<V1|V2>
             */
            fn (mixed $value): Result => $decoder1->run($value)->or(fn () => $decoder2->run($value))
        );
    }

    /**
     * @psalm-pure
     *
     * @template V
     *
     * @psalm-param self<V> ...$decoders
     *
     * @psalm-return self<V>
     */
    public static function oneOf(self ...$decoders): self
    {
        return new self(
            /**
             * @return Result<V>
             */
            function (mixed $value) use ($decoders): Result {
                /**
                 * @var list<Result<never>>
                 */
                $badResults = [];
                foreach ($decoders as $decoder) {
                    $result = $decoder->run($value);
                    if ($result->isOk()) {
                        return $result;
                    }
                    $badResults[] = $result;
                }

                /**
                 * Psalm not able to infer this.
                 *
                 * @var Result<V>
                 */
                return Result::combine($badResults);
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @template V
     *
     * @psalm-param self<V> $decoder
     *
     * @psalm-return self<null|V>
     */
    public static function nullable(self $decoder): self
    {
        return new self(
            /**
             * @return Result<V|null>
             */
            function (mixed $value) use ($decoder): Result {
                if (is_null($value)) {
                    return Result::ok(null);
                }

                return $decoder->run($value);
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @template V of null|false|true|literal-string|literal-int
     *
     * @psalm-param V $literal
     *
     * @psalm-return self<V>
     */
    public static function literal($literal): self
    {
        return new self(
            /**
             * @psalm-return Result<V>
             */
            function (mixed $value) use ($literal): Result {
                return $value === $literal
                    ? Result::ok($literal)
                    : Result::err("Doesn't match");
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @psalm-return self<non-empty-string>
     */
    public static function nonEmptyString(): self
    {
        return self::string()->andThen(
            /**
             * @psalm-return self<non-empty-string>
             */
            function (string $value): Decoder {
                if ('' === $value) {
                    return Decoder::fail('Expected non empty string value, got ""');
                }
                if ('' === \trim($value)) {
                    return Decoder::fail("Expected non empty string value, got $value");
                }

                return Decoder::succeed($value);
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @psalm-return self<\DateTimeImmutable>
     */
    public static function dateString(string $format): self
    {
        return self::nonEmptyString()->andThen(
            /**
             * @psalm-pure
             *
             * @param non-empty-string $value
             *
             * @return self<\DateTimeImmutable>
             */
            function (string $value) use ($format): Decoder {
                $parsedDate = \DateTimeImmutable::createFromFormat($format, $value);
                if (false === $parsedDate) {
                    return Decoder::fail("Expected date string in format $format got $value");
                }

                return Decoder::succeed($parsedDate);
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @psalm-return self<int|float>
     */
    public static function numeric(\NumberFormatter $formatter = null): self
    {
        return new self(
            /**
             * @return Result<int|float>
             */
            function (mixed $value) use ($formatter): Result {
                if (null === $formatter) {
                    if (!is_numeric($value)) {
                        return Result::err('Expected numeric string value got '.\get_debug_type($value));
                    }

                    return Result::ok((string) ((int) $value) === $value ? (int) $value : (float) $value);
                }
                if (!is_int($value) && !is_float($value) && !is_string($value)) {
                    return Result::err('Expected numeric value got '.\get_debug_type($value));
                }
                $parsedNumber = $formatter->parse((string) $value);
                if (false === $parsedNumber) {
                    return Result::err('Not a valid number in this locale');
                }

                return Result::ok($parsedNumber);
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @psalm-return self<positive-int>
     */
    public static function positiveInt(): self
    {
        return self::int()->andThen(
            /**
             * @psalm-pure
             *
             * @psalm-return self<positive-int>
             */
            fn (int $number): self => $number > 0 ? self::succeed($number) : Decoder::fail('Not a positive integer')
        );
    }

    /**
     * @psalm-pure
     *
     * @template V
     *
     * @psalm-param self<V> $valueDecoder
     * @psalm-param array-key $key
     *
     * @psalm-return self<V>
     */
    public static function arrayKey($key, self $valueDecoder = null): self
    {
        return self::array()->andThen(
            /**
             * @psalm-pure
             *
             * @return self<V>
             */
            function (array $array) use ($key, $valueDecoder): Decoder {
                if (!\array_key_exists($key, $array)) {
                    return Decoder::fail("Key `$key` not present in array");
                }

                return null === $valueDecoder
                    ? Decoder::succeed($array[$key])
                    : $valueDecoder->run($array[$key])->match(
                        /**
                         * @param V $decodedValueAtKey
                         *
                         * @return self<V>
                         */
                        fn ($decodedValueAtKey): self => self::succeed($decodedValueAtKey),
                        /**
                         * @param non-empty-list<non-empty-string> $decoderErrs
                         *
                         * @return self<V>
                         */
                        fn (array $decoderErrs): self => self::fail($decoderErrs)
                    );
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @template V
     * @template DefaultValueType
     *
     * @psalm-param self<V> $valueDecoder
     * @psalm-param DefaultValueType $defaultValue
     * @psalm-param array-key $key
     *
     * @psalm-return self<DefaultValueType|V>
     */
    public static function optionalArrayKey($key, self $valueDecoder = null, $defaultValue = null): self
    {
        return self::array()->andThen(
            /**
             * @return self<DefaultValueType|V>
             *
             * @psalm-pure
             */
            function (array $array) use ($key, $valueDecoder, $defaultValue): self {
                if (!\array_key_exists($key, $array)) {
                    return Decoder::succeed($defaultValue);
                }

                return null === $valueDecoder
                    ? Decoder::succeed($array[$key])
                    : $valueDecoder->run($array[$key])->match(
                        /**
                         * @param V $decodedValueAtKey
                         *
                         * @return self<V>
                         */
                        fn ($decodedValueAtKey): self => self::succeed($decodedValueAtKey),
                        /**
                         * @param non-empty-list<non-empty-string> $decoderErrs
                         *
                         * @return self<V>
                         */
                        fn (array $decoderErrs): self => self::fail($decoderErrs)
                    );
            }
        );
    }

    /**
     * Uses associative php array mapping.
     *
     * @psalm-pure
     *
     * @template V
     *
     * @psalm-param self<V> $jsonDecoder
     *
     * @psalm-return self<V>
     */
    public static function jsonDecode(Decoder $jsonDecoder): self
    {
        return new self(
            /**
             * @return Result<V>
             */
            function (mixed $jsonString) use ($jsonDecoder): Result {
                if (!is_string($jsonString)) {
                    return Result::err('Value is not a json string');
                }
                try {
                    /**
                     * @var mixed $phpValue
                     */
                    $phpValue = \json_decode($jsonString, true, 512, \JSON_THROW_ON_ERROR);

                    return $jsonDecoder->run($phpValue);
                } catch (\JsonException $e) {
                    return Result::err('Json Exception: '.$e->getMessage());
                }
            }
        );
    }

    /**
     * @template V
     *
     * @psalm-pure
     *
     * @psalm-param self<V> $itemDecoder
     *
     * @psalm-return self<list<V>>
     */
    public static function list(self $itemDecoder = null): self
    {
        return null === $itemDecoder
            ? self::_list()
            : self::_list()->andThen(
                /**
                 * @psalm-param list<mixed> $list
                 *
                 * @psalm-pure
                 *
                 * @psalm-return self<list<V>>
                 */
                fn (array $list): Decoder => Result::combine(
                    \array_map(
                        /**
                         * @psalm-return Result<V>
                         */
                        fn (mixed $item): Result => ($itemDecoder->decodeFn)($item),
                        $list
                    )
                )->match(
                    /**
                     * @psalm-param list<V> $listOfDecodedValues
                     *
                     * @psalm-return self<list<V>>
                     */
                    fn (array $listOfDecodedValues): self => self::succeed($listOfDecodedValues),
                    /**
                     * @param non-empty-list<non-empty-string> $errors
                     *
                     * @return self<list<V>>
                     */
                    fn (array $errors): self => self::fail($errors)
                )
            );
    }

    /**
     * @template V
     *
     * @psalm-pure
     *
     * @psalm-param self<V> $itemDecoder
     *
     * @psalm-return self<non-empty-list<V>>
     */
    public static function nonEmptyList(self $itemDecoder = null): self
    {
        return null === $itemDecoder
            ? self::_nonEmptyList()
            : self::_nonEmptyList()->andThen(
                /**
                 * @psalm-param non-empty-list<mixed> $list
                 *
                 * @psalm-pure
                 *
                 * @psalm-return self<non-empty-list<V>>
                 */
                function (array $list) use ($itemDecoder): Decoder {
                    /**
                     * @psalm-var  Result<non-empty-list<V>>
                     */
                    $res = Result::combine(
                        \array_map(
                            /**
                             * @psalm-return Result<V>
                             */
                            fn (mixed $item): Result => ($itemDecoder->decodeFn)($item),
                            $list
                        )
                    );

                    return $res->match(
                        /**
                         * @psalm-param non-empty-list<V> $listOfDecodedValues
                         *
                         * @psalm-return self<non-empty-list<V>>
                         */
                        fn (array $listOfDecodedValues): self => self::succeed($listOfDecodedValues),
                        /**
                         * @param non-empty-list<non-empty-string> $errors
                         *
                         * @return self<non-empty-list<V>>
                         */
                        fn (array $errors): self => self::fail($errors)
                    );
                }
            );
    }

    /**
     * @template V
     *
     * @psalm-pure
     *
     * @psalm-param self<V> $itemDecoder
     *
     * @psalm-return self<array<string, V>>
     */
    public static function dictOf(Decoder $itemDecoder = null): self
    {
        return self::array()->andThen(
            /**
             * @param array<array-key, mixed> $arr
             *
             * @psalm-pure
             *
             * @psalm-return self<array<string, V>>
             */
            fn (array $arr): self => Result::map2(
                self::list(self::string())->run(\array_keys($arr)),
                self::list($itemDecoder)->run(\array_values($arr)),
                /**
                 * @param list<string> $keys
                 * @param list<V>      $vals
                 *
                 * @return array<string, V>
                 */
                fn (array $keys, array $vals): array => \array_combine($keys, $vals)
            )->match(
                /**
                 * @param array<string, V> $decodedVal
                 *
                 * @return self<array<string, V>>
                 */
                fn (array $decodedVal): self => self::succeed($decodedVal),
                /**
                 * @param non-empty-list<non-empty-string> $decodingErrs
                 *
                 * @return self<array<string, V>>
                 */
                fn (array $decodingErrs): self => self::fail($decodingErrs),
            )
        );
    }

    /**
     * @psalm-pure
     *
     * @template T1
     * @template T2
     * @template R
     *
     * @psalm-param self<T1> $decoder1
     * @psalm-param self<T2> $decoder2
     *
     * @param callable(T1, T2): R $mapperFn
     *
     * @psalm-param pure-callable(T1, T2): R $mapperFn
     *
     * @psalm-return self<R>
     */
    public static function map2(self $decoder1, self $decoder2, callable $mapperFn): self
    {
        return new self(
            /**
             * @return Result<R>
             */
            fn (mixed $value): Result => Result::map2(($decoder1->decodeFn)($value), ($decoder2->decodeFn)($value), $mapperFn)
        );
    }

    /**
     * @psalm-pure
     *
     * @template T1
     * @template T2
     * @template T3
     * @template R
     *
     * @psalm-param self<T1> $decoder1
     * @psalm-param self<T2> $decoder2
     * @psalm-param self<T3> $decoder3
     *
     * @param callable(T1, T2, T3): R $mapperFn
     *
     * @psalm-param pure-callable(T1, T2, T3): R $mapperFn
     *
     * @psalm-return self<R>
     */
    public static function map3(self $decoder1, self $decoder2, self $decoder3, callable $mapperFn): self
    {
        return new self(
            /**
             * @return Result<R>
             */
            fn (mixed $value): Result => Result::map3(
                ($decoder1->decodeFn)($value),
                ($decoder2->decodeFn)($value),
                ($decoder3->decodeFn)($value),
                $mapperFn
            )
        );
    }

    /**
     * @psalm-pure
     *
     * @template T1
     * @template T2
     * @template T3
     * @template T4
     * @template R
     *
     * @psalm-param self<T1> $decoder1
     * @psalm-param self<T2> $decoder2
     * @psalm-param self<T3> $decoder3
     * @psalm-param self<T4> $decoder4
     *
     * @param callable(T1, T2, T3, T4): R $mapperFn
     *
     * @psalm-param pure-callable(T1, T2, T3, T4): R $mapperFn
     *
     * @psalm-return self<R>
     */
    public static function map4(self $decoder1, self $decoder2, self $decoder3, self $decoder4, callable $mapperFn): self
    {
        return new self(
            /**
             * @return Result<R>
             */
            fn (mixed $value): Result => Result::map4(
                ($decoder1->decodeFn)($value),
                ($decoder2->decodeFn)($value),
                ($decoder3->decodeFn)($value),
                ($decoder4->decodeFn)($value),
                $mapperFn
            )
        );
    }

    /**
     * @psalm-pure
     *
     * @template T1
     * @template T2
     * @template T3
     * @template T4
     * @template T5
     * @template R
     *
     * @psalm-param self<T1> $decoder1
     * @psalm-param self<T2> $decoder2
     * @psalm-param self<T3> $decoder3
     * @psalm-param self<T4> $decoder4
     * @psalm-param self<T5> $decoder5
     *
     * @param callable(T1, T2, T3, T4, T5): R $mapperFn
     *
     * @psalm-param pure-callable(T1, T2, T3, T4, T5): R $mapperFn
     *
     * @psalm-return self<R>
     */
    public static function map5(self $decoder1, self $decoder2, self $decoder3, self $decoder4, self $decoder5, callable $mapperFn): self
    {
        return new self(
            /**
             * @return Result<R>
             */
            fn (mixed $value): Result => Result::map5(
                ($decoder1->decodeFn)($value),
                ($decoder2->decodeFn)($value),
                ($decoder3->decodeFn)($value),
                ($decoder4->decodeFn)($value),
                ($decoder5->decodeFn)($value),
                $mapperFn
            )
        );
    }

    /**
     * @psalm-pure
     *
     * @template T1
     * @template T2
     * @template T3
     * @template T4
     * @template T5
     * @template T6
     * @template R
     *
     * @psalm-param self<T1> $decoder1
     * @psalm-param self<T2> $decoder2
     * @psalm-param self<T3> $decoder3
     * @psalm-param self<T4> $decoder4
     * @psalm-param self<T5> $decoder5
     * @psalm-param self<T6> $decoder6
     *
     * @param callable(T1, T2, T3, T4, T5, T6): R $mapperFn
     *
     * @psalm-param pure-callable(T1, T2, T3, T4, T5, T6): R $mapperFn
     *
     * @psalm-return self<R>
     */
    public static function map6(self $decoder1, self $decoder2, self $decoder3, self $decoder4, self $decoder5, self $decoder6, callable $mapperFn): self
    {
        return new self(
            /**
             * @return Result<R>
             */
            fn (mixed $value): Result => Result::map6(
                ($decoder1->decodeFn)($value),
                ($decoder2->decodeFn)($value),
                ($decoder3->decodeFn)($value),
                ($decoder4->decodeFn)($value),
                ($decoder5->decodeFn)($value),
                ($decoder6->decodeFn)($value),
                $mapperFn
            )
        );
    }

    /**
     * @template V
     *
     * @psalm-pure
     *
     * @psalm-return self<iterable>
     */
    public static function iterable(): self
    {
        return new self(
            /**
             * @psalm-pure
             *
             * @psalm-return Result<iterable>
             */
            function (mixed $value): Result {
                return \is_iterable($value) ? Result::ok($value) : Result::err('Value of type '.\get_debug_type($value).' is not iterable');
            }
        );
    }

    /**
     * @return self<scalar>
     */
    public static function scalar(): self
    {
        return new self(
            /**
             * @psalm-return Result<scalar>
             */
            function (mixed $value): Result {
                return \is_scalar($value)
                    ? Result::ok($value)
                    : Result::err('Value of type '.\get_debug_type($value).' is not a scalar');
            }
        );
    }

    /**
     * @psalm-pure
     *
     * @template T1
     * @template T2
     * @template R
     *
     * @psalm-param self<T1> $decoder1
     * @psalm-param self<T2> $decoder2
     *
     * @param callable(T1, T2): R $mapperFn
     *
     * @psalm-param pure-callable(T1, T2): R $mapperFn
     *
     * @psalm-return self<R>
     */
    public static function arrayMap2(self $decoder1, self $decoder2, callable $mapperFn): self
    {
        return new self(
            fn (mixed $value): Result => self::map2(
                self::arrayKey(0, $decoder1),
                self::arrayKey(1, $decoder2),
                $mapperFn
            )->run($value)
        );
    }

    /**
     * @psalm-pure
     *
     * @template T1
     * @template T2
     * @template T3
     * @template R
     *
     * @psalm-param self<T1> $decoder1
     * @psalm-param self<T2> $decoder2
     * @psalm-param self<T3> $decoder3
     *
     * @param callable(T1, T2, T3): R $mapperFn
     *
     * @psalm-param pure-callable(T1, T2, T3): R $mapperFn
     *
     * @psalm-return self<R>
     */
    public static function arrayMap3(self $decoder1, self $decoder2, self $decoder3, callable $mapperFn): self
    {
        return new self(
            fn (mixed $value): Result => self::map3(
                self::arrayKey(0, $decoder1),
                self::arrayKey(1, $decoder2),
                self::arrayKey(2, $decoder3),
                $mapperFn
            )->run($value)
        );
    }

    /**
     * @psalm-pure
     *
     * @psalm-return self<non-empty-list<mixed>>
     */
    private static function _nonEmptyList(): self
    {
        return self::_list()->andThen(
            /**
             * @param list<mixed> $list
             *
             * @return self<non-empty-list<mixed>>
             */
            fn (array $list): Decoder => 0 === \count($list) ? Decoder::fail('Empty array') : Decoder::succeed($list)
        );
    }

    /**
     * @psalm-pure
     *
     * @psalm-return self<list<mixed>>
     */
    private static function _list(): self
    {
        return self::array()->andThen(
            /**
             * @return self<list<mixed>>
             */
            function (array $value): Decoder {
                if (!array_is_list($value)) {
                    return Decoder::fail('Array '.\print_r($value, true).' is not a list');
                }

                return Decoder::succeed($value);
            }
        );
    }
}
