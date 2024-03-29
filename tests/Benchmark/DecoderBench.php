<?php

declare(strict_types=1);

namespace Test\Benchmark;

use Exp\Decoder;

final readonly class DecoderBench
{
    /**
     * @Revs(1000)
     *
     * @Iterations(5)
     */
    public function benchOneOf(): void
    {
        $decoder = Decoder::oneOf(Decoder::bool(), Decoder::int(), Decoder::float());
        $decoder->run(5.0);
    }
}
