<?php

declare(strict_types=1);

namespace Exp\Result;

/**
 * @psalm-internal Exp\Result
 *
 * @psalm-immutable
 */
final readonly class Error
{
    /**
     * @param non-empty-list<non-empty-string> $errors
     */
    public function __construct(
        public array $errors
    ) {
    }
}
