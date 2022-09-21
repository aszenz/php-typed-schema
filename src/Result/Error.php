<?php

declare(strict_types=1);

namespace Exp\Result;

/**
 * @psalm-internal Exp\Result
 *
 * @psalm-immutable
 */
final class Error
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        public array $errors
    ) {
    }
}
