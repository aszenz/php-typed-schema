<?php

declare(strict_types=1);

namespace Exp\Result;

/**
 * @template-covariant T
 *
 * @psalm-internal Exp\Result
 *
 * @psalm-immutable
 */
final class Ok
{
    /**
     * @param T $value
     */
    public function __construct(
        public mixed $value
    ) {
    }

    /**
     * @template V
     *
     * @param callable(T): V $mapperFn
     *
     * @psalm-param pure-callable(T): V $mapperFn
     *
     * @return Ok<V>
     */
    public function map(callable $mapperFn): Ok
    {
        return new Ok($mapperFn($this->value));
    }
}
