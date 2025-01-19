<?php

namespace TiMacDonald\Website;

use Closure;
use RuntimeException;

class Capture
{
    /**
     * @template TValue
     *
     * @param  (Closure(): TValue)  $closure
     * @return array{0: string, 1: TValue}
     */
    public function __invoke(Closure $closure): array
    {
        if (! ob_start()) {
            throw new RuntimeException('Unable to start output buffering.');
        }

        $returnValue = $closure();

        $content = ob_get_clean();

        if ($content === false) {
            throw new RuntimeException('Unable to get the output buffer contents.');
        }

        return [$content, $returnValue];
    }
}

