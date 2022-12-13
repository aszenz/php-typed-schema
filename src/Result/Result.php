<?php

declare(strict_types=1);

namespace Exp\Result;

/**
 * @template-covariant ValType
 *
 * @psalm-immutable
 */
final class Result
{
    /**
     * @psalm-param Ok<ValType>|Error $v
     */
    private function __construct(
        private Ok|Error $v
    ) {
    }

    /**
     * @psalm-pure
     *
     * @template T
     *
     * @psalm-param Ok<T>|Error $v
     *
     * @psalm-return Result<T>
     */
    public static function new(
        Ok|Error $v
    ): self {
        return new self($v);
    }

    /**
     * @psalm-pure
     *
     * @template T
     *
     * @psalm-param T $value
     *
     * @psalm-return self<T>
     */
    public static function ok(mixed $value): self
    {
        return new self(new Ok($value));
    }

    /**
     * @psalm-pure
     *
     * @psalm-param non-empty-list<non-empty-string>|non-empty-string $errs
     *
     * @psalm-return self<never>
     */
    public static function err(array|string $errs): self
    {
        /**
         * Psalm not able to infer this.
         *
         * @var self<never>
         */
        return new self(
            new Error(
                \is_string($errs) ? [$errs] : $errs
            )
        );
    }

    /**
     * @psalm-assert-if-true Ok<ValType> $this->v
     */
    public function isOk(): bool
    {
        return $this->v instanceof Ok;
    }

    /**
     * @psalm-assert-if-true Error $this->v
     */
    public function isErr(): bool
    {
        return $this->v instanceof Error;
    }

    /**
     * @psalm-return ValType
     *
     * @throws \Error
     */
    public function unwrap()
    {
        if ($this->v instanceof Error) {
            throw new \Error('Error');
        }

        return $this->v->value;
    }

    /**
     * @psalm-return non-empty-list<non-empty-string>
     *
     * @throws \Error
     */
    public function unwrapError(): array
    {
        if ($this->v instanceof Ok) {
            throw new \Error('Result is Ok no error present');
        }

        return $this->v->errors;
    }

    /**
     * @template OnOkReturn
     * @template OnErrReturn
     *
     * @param callable(ValType): OnOkReturn $onOk
     *
     * @psalm-param pure-callable(ValType): OnOkReturn $onOk
     *
     * @param callable(list<non-empty-string>): OnErrReturn $onErr
     *
     * @psalm-param pure-callable(non-empty-list<non-empty-string>): OnErrReturn $onErr
     *
     * @psalm-return OnOkReturn|OnErrReturn
     */
    public function match(callable $onOk, callable $onErr)
    {
        if ($this->v instanceof Error) {
            return $onErr($this->v->errors);
        }

        return $onOk($this->v->value);
    }

    /**
     * @template V
     *
     * @param callable(ValType): V $mapperFn
     *
     * @psalm-param pure-callable(ValType): V $mapperFn
     *
     * @psalm-return Result<V>
     */
    public function map(callable $mapperFn): Result
    {
        if ($this->v instanceof Error) {
            return self::err($this->v->errors);
        }

        return self::ok($this->v->map($mapperFn)->value);
    }

    /**
     * @template X
     *
     * @param callable():Result<X> $getResult
     *
     * @psalm-param pure-callable():Result<X> $getResult
     *
     * @psalm-return Result<X|ValType>
     */
    public function or(callable $getResult): Result
    {
        if ($this->v instanceof Ok) {
            return $this;
        }
        $result = $getResult();

        return $result->v instanceof Error ? Result::err(
            \array_merge($this->v->errors, $result->v->errors)
        ) : $result;
    }

    /**
     * @template V
     *
     * @param callable(ValType): Result<V> $mapperFn
     *
     * @psalm-param pure-callable(ValType): Result<V> $mapperFn
     *
     * @psalm-return Result<V>
     */
    public function andThen(callable $mapperFn): Result
    {
        if ($this->v instanceof Error) {
            return self::err($this->v->errors);
        }

        return $this->v->map($mapperFn)->value;
    }

    /**
     * @psalm-pure
     *
     * @template T
     *
     * @psalm-param list<Result<T>> $results
     *
     * @psalm-return Result<list<T>>
     */
    public static function combine(array $results): Result
    {
        /**
         * @var Result<list<T>>
         */
        $empty = Result::ok([]);

        return \array_reduce(
            $results,
            /**
             * @psalm-param Result<list<T>> $overallResult
             * @psalm-param Result<T> $currentResult
             *
             * @psalm-pure
             *
             * @psalm-return Result<list<T>>
             */
            function (Result $overallResult, Result $currentResult): Result {
                return Result::map2(
                    $overallResult,
                    $currentResult,
                    /**
                     * @psalm-param list<T> $previousValues
                     * @psalm-param T $currentValue
                     *
                     * @psalm-return list<T>
                     */
                    fn (array $previousValues, $currentValue): array => [...$previousValues, $currentValue]
                );
            },
            $empty
        );
    }

    /**
     * @psalm-pure
     *
     * @template T
     * @template V
     * @template K
     *
     * @psalm-param Result<T> $result1
     * @psalm-param Result<V> $result2
     *
     * @param callable(T, V):K $mapperFn
     *
     * @psalm-param pure-callable(T, V):K $mapperFn
     *
     * @psalm-return Result<K>
     */
    public static function map2(self $result1, self $result2, callable $mapperFn): self
    {
        return self::map2_($result1, $result2)->map(fn (array $v) => $mapperFn($v[0], $v[1]));
    }

    /**
     * @psalm-pure
     *
     * @template T
     * @template V
     * @template K
     * @template Z
     *
     * @psalm-param Result<T> $result1
     * @psalm-param Result<V> $result2
     * @psalm-param Result<K> $result3
     *
     * @param callable(T, V, K):Z $mapperFn
     *
     * @psalm-param pure-callable(T, V, K):Z $mapperFn
     *
     * @psalm-return Result<Z>
     */
    public static function map3(self $result1, self $result2, self $result3, callable $mapperFn): self
    {
        return self::map3_($result1, $result2, $result3)->map(fn (array $v) => $mapperFn($v[0], $v[1], $v[2]));
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
     * @psalm-param Result<T1> $result1
     * @psalm-param Result<T2> $result2
     * @psalm-param Result<T3> $result3
     * @psalm-param Result<T4> $result4
     *
     * @param callable(T1, T2, T3, T4):R $mapperFn
     *
     * @psalm-param pure-callable(T1, T2, T3, T4):R $mapperFn
     *
     * @psalm-return Result<R>
     */
    public static function map4(self $result1, self $result2, self $result3, self $result4, callable $mapperFn): self
    {
        return self::map4_($result1, $result2, $result3, $result4)->map(fn (array $v) => $mapperFn($v[0], $v[1], $v[2], $v[3]));
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
     * @psalm-param Result<T1> $result1
     * @psalm-param Result<T2> $result2
     * @psalm-param Result<T3> $result3
     * @psalm-param Result<T4> $result4
     * @psalm-param Result<T5> $result5
     * @psalm-param pure-callable(T1, T2, T3, T4, T5):R $mapperFn
     *
     * @param callable(T1, T2, T3, T4, T5):R $mapperFn
     *
     * @psalm-return Result<R>
     */
    public static function map5(self $result1, self $result2, self $result3, self $result4, self $result5, callable $mapperFn): self
    {
        return self::map5_($result1, $result2, $result3, $result4, $result5)
        ->map(
            /**
             * @psalm-param array{T1, T2, T3, T4, T5} $out
             */
            fn (array $out) => $mapperFn($out[0], $out[1], $out[2], $out[3], $out[4])
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
     * @psalm-param Result<T1> $result1
     * @psalm-param Result<T2> $result2
     * @psalm-param Result<T3> $result3
     * @psalm-param Result<T4> $result4
     * @psalm-param Result<T5> $result5
     * @psalm-param Result<T6> $result6
     *
     * @param callable(T1, T2, T3, T4, T5, T6):R $mapperFn
     *
     * @psalm-param pure-callable(T1, T2, T3, T4, T5, T6):R $mapperFn
     *
     * @psalm-return Result<R>
     */
    public static function map6(self $result1, self $result2, self $result3, self $result4, self $result5, self $result6, callable $mapperFn): self
    {
        return self::map6_($result1, $result2, $result3, $result4, $result5, $result6)
        ->map(
            /**
             * @psalm-param array{T1, T2, T3, T4, T5, T6} $out
             */
            fn (array $out) => $mapperFn($out[0], $out[1], $out[2], $out[3], $out[4], $out[5])
        );
    }

    /**
     * @template V
     *
     * @param Result<callable(ValType):V> $resultGen
     *
     * @psalm-param Result<pure-callable(ValType):V> $resultGen
     *
     * @psalm-return Result<V>
     */
    private function andMap(self $resultGen): self
    {
        return self::map2(
            $this,
            $resultGen,
            /**
             * @psalm-param pure-callable(ValType):V $func
             * @psalm-param ValType $arg
             */
            fn ($arg, callable $func) => $func($arg)
        );
    }

    /**
     * @psalm-pure
     *
     * @template T1
     * @template T2
     *
     * @psalm-param Result<T1> $result1
     * @psalm-param Result<T2> $result2
     *
     * @psalm-return Result<array{T1, T2}>
     */
    private static function map2_(self $result1, self $result2): self
    {
        if ($result1->v instanceof Ok && $result2->v instanceof Ok) {
            return self::ok([$result1->v->value, $result2->v->value]);
        }
        if ($result1->v instanceof Ok && $result2->v instanceof Error) {
            return self::err($result2->v->errors);
        }
        if ($result1->v instanceof Error && $result2->v instanceof Ok) {
            return self::err($result1->v->errors);
        }
        if ($result1->v instanceof Error && $result2->v instanceof Error) {
            return self::err(\array_merge($result1->v->errors, $result2->v->errors));
        }
        throw new \LogicException();
    }

    /**
     * @psalm-pure
     *
     * @template T1
     * @template T2
     * @template T3
     *
     * @psalm-param Result<T1> $result1
     * @psalm-param Result<T2> $result2
     * @psalm-param Result<T3> $result3
     *
     * @psalm-return Result<array{T1, T2, T3}>
     */
    private static function map3_(self $result1, self $result2, self $result3): self
    {
        return self::map2_(
            self::map2_($result1, $result2),
            $result3
        )
        ->map(
            /**
             * @psalm-param array{array{T1, T2}, T3} $x
             *
             * @psalm-return array{T1, T2, T3}
             */
            fn (array $x): array => [$x[0][0], $x[0][1], $x[1]]
        );
    }

    /**
     * @psalm-pure
     *
     * @template T1
     * @template T2
     * @template T3
     * @template T4
     *
     * @psalm-param Result<T1> $result1
     * @psalm-param Result<T2> $result2
     * @psalm-param Result<T3> $result3
     * @psalm-param Result<T4> $result4
     *
     * @psalm-return Result<array{T1, T2, T3, T4}>
     */
    private static function map4_(self $result1, self $result2, self $result3, self $result4): self
    {
        return self::map2_(
            self::map3_($result1, $result2, $result3),
            $result4
        )
        ->map(
            /**
             * @psalm-param array{array{T1, T2, T3}, T4} $x
             *
             * @psalm-return array{T1, T2, T3, T4}
             */
            fn (array $x): array => [$x[0][0], $x[0][1], $x[0][2], $x[1]]
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
     *
     * @psalm-param Result<T1> $result1
     * @psalm-param Result<T2> $result2
     * @psalm-param Result<T3> $result3
     * @psalm-param Result<T4> $result4
     * @psalm-param Result<T5> $result5
     *
     * @psalm-return Result<array{T1, T2, T3, T4, T5}>
     */
    private static function map5_(self $result1, self $result2, self $result3, self $result4, self $result5): self
    {
        return self::map2_(
            self::map4_($result1, $result2, $result3, $result4),
            $result5
        )
        ->map(
            /**
             * @psalm-param array{array{T1, T2, T3, T4}, T5} $x
             *
             * @psalm-return array{T1, T2, T3, T4, T5}
             */
            fn (array $x): array => [$x[0][0], $x[0][1], $x[0][2], $x[0][3], $x[1]]
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
     *
     * @psalm-param Result<T1> $result1
     * @psalm-param Result<T2> $result2
     * @psalm-param Result<T3> $result3
     * @psalm-param Result<T4> $result4
     * @psalm-param Result<T5> $result5
     * @psalm-param Result<T6> $result6
     *
     * @psalm-return Result<array{T1, T2, T3, T4, T5, T6}>
     */
    private static function map6_(self $result1, self $result2, self $result3, self $result4, self $result5, self $result6): self
    {
        return self::map2_(
            self::map5_($result1, $result2, $result3, $result4, $result5),
            $result6
        )
        ->map(
            /**
             * @psalm-param array{array{T1, T2, T3, T4, T5}, T6} $x
             *
             * @psalm-return array{T1, T2, T3, T4, T5, T6}
             */
            fn (array $x): array => [$x[0][0], $x[0][1], $x[0][2], $x[0][3], $x[0][4], $x[1]]
        );
    }
}
